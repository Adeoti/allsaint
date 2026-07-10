<?php

namespace App\Services;

use App\Models\ResultRoot;
use App\Models\ResultUpload;
use App\Models\Subject;
use App\Models\SchoolClass;
use App\Models\User;
use App\Models\HOSRemark;
use App\Models\TeacherRemark;
use App\Models\StudentSkillBehaviour;

class CumulativeReportService
{
    /**
     * The full mark for one term/subject sitting, used to compute the
     * percentage shown next to each term column and the annual summary
     * (e.g. 100% per term sat, 300% for a full 3-term annual summary).
     *
     * This assumes every term's total is graded out of 100 — the standard
     * convention regardless of how individual CA/Exam columns are split.
     * Adjust this single constant if your school uses a different full mark.
     */
    public const FULL_MARK_PER_TERM = 100;

    /**
     * Build the full cumulative dataset for a given academic session + class.
     *
     * Returns an array shaped like:
     * [
     *     'session' => '2025/2026',
     *     'class' => SchoolClass,
     *     'all_terms' => ['1st Term', '2nd Term', '3rd Term'],
     *     'terms_present' => [...], // terms that actually have a ResultRoot for this session+class
     *     'roots' => [ '1st Term' => ResultRoot|null, ... ],
     *     'third_term_headers' => ['1st CA', '2nd CA', '3rd Term Exam'], // dynamic score columns from the 3rd term CSV
     *     'students' => [
     *         studentId => [
     *             'info' => User,
     *             'subjects' => [
     *                 subjectName => [
     *                     'per_term' => ['1st Term' => 70, '2nd Term' => 65, '3rd Term' => 80],
     *                     'terms_sat' => 3,
     *                     'annual_summary' => 215,
     *                     'annual_average' => 71.67,
     *                     'third_term_scores' => ['1st CA' => 15, '2nd CA' => 15, '3rd Term Exam' => 50],
     *                     'third_term_remark' => 'Credit',
     *                 ],
     *             ],
     *             'overall' => [
     *                 'per_term' => ['1st Term' => 350, ...],
     *                 'annual_summary' => 700,
     *                 'annual_average' => 70.0,
     *                 'total_obtainable' => 1000, // subjects x 100 x terms_sat (per student)
     *                 'overall_average_percent' => 70.0, // (annual_summary x 100) / total_obtainable
     *             ],
     *             'third_term_skills' => [...],
     *             'third_term_behaviours' => [...],
     *         ],
     *     ],
     *     'teacherRemarks' => Collection keyed by student_id (from the 3rd term root, if present),
     *     'hosRemarks' => Collection keyed by student_id (from the 3rd term root, if present),
     * ]
     */
    public function build(string $academicSession, int $classId): array
    {
        $allTerms = array_keys(ResultRoot::termOptions());

        $class = SchoolClass::find($classId);

        // Find the ResultRoot for each term in this session/class.
        // A "root" here represents one term's sitting. We match by academic_session + term,
        // and we only consider roots that actually have uploads tied to this class.
        $roots = [];
        foreach ($allTerms as $term) {
            $root = ResultRoot::where('academic_session', $academicSession)
                ->where('term', $term)
                ->whereHas('resultUploads', function ($q) use ($classId) {
                    $q->where('class_id', $classId);
                })
                ->first();

            $roots[$term] = $root;
        }

        $termsPresent = array_keys(array_filter($roots));

        $students = [];
        $subjectOrder = []; // preserve a stable subject display order across terms
        $thirdTermHeaders = []; // dynamic CSV score column names from the 3rd term, e.g. ['1st CA', '2nd CA', 'Exam']

        foreach ($termsPresent as $term) {
            $root = $roots[$term];

            $uploads = ResultUpload::where('result_root_id', $root->id)
                ->where('class_id', $classId)
                ->get();

            foreach ($uploads as $upload) {
                $subject = Subject::find($upload->subject_id);
                $subjectName = $subject->name ?? 'No Subject';

                if (!in_array($subjectName, $subjectOrder)) {
                    $subjectOrder[] = $subjectName;
                }

                $cardItems = is_array($upload->card_items)
                    ? $upload->card_items
                    : json_decode($upload->card_items, true);

                if (!is_array($cardItems)) {
                    continue;
                }

                foreach ($cardItems as $studentId => $result) {
                    $student = User::find($studentId);
                    if (!$student) {
                        continue;
                    }

                    if (!isset($students[$studentId])) {
                        $students[$studentId] = [
                            'info' => $student,
                            'subjects' => [],
                        ];
                    }

                    if (!isset($students[$studentId]['subjects'][$subjectName])) {
                        $students[$studentId]['subjects'][$subjectName] = [
                            'per_term' => array_fill_keys($allTerms, null),
                            'third_term_scores' => [],
                            'third_term_remark' => null,
                        ];
                    }

                    $total = $result['total'] ?? null;
                    $students[$studentId]['subjects'][$subjectName]['per_term'][$term] = $total;

                    // Capture the 3rd term's dynamic score breakdown (1st CA, 2nd CA, Exam, etc.)
                    // and its per-subject remark, used for the dedicated 3rd Term block in the table.
                    if ($term === '3rd Term') {
                        $scores = $result['scores'] ?? [];
                        if (is_array($scores)) {
                            foreach (array_keys($scores) as $header) {
                                if (!in_array($header, $thirdTermHeaders)) {
                                    $thirdTermHeaders[] = $header;
                                }
                            }
                        }

                        $students[$studentId]['subjects'][$subjectName]['third_term_scores'] = $scores;
                        $students[$studentId]['subjects'][$subjectName]['third_term_remark'] = $result['remark'] ?? null;
                    }
                }
            }
        }

        // Compute annual summary/average per subject, and overall per-term + annual totals per student.
        foreach ($students as $studentId => &$studentData) {
            $overallPerTerm = array_fill_keys($allTerms, 0);
            $overallTermsSat = array_fill_keys($allTerms, 0);

            foreach ($studentData['subjects'] as $subjectName => &$subjectData) {
                $sum = 0;
                $termsSat = 0;

                foreach ($allTerms as $term) {
                    $value = $subjectData['per_term'][$term];
                    if ($value !== null && $value !== '' && $value !== 'N/A') {
                        $sum += (float) $value;
                        $termsSat++;
                        $overallPerTerm[$term] += (float) $value;
                        $overallTermsSat[$term] = 1; // mark term as having at least one subject (used for overall avg)
                    }
                }

                $subjectData['terms_sat'] = $termsSat;
                $subjectData['annual_summary'] = $termsSat > 0 ? $sum : null;
                $subjectData['annual_average'] = $termsSat > 0 ? round($sum / $termsSat, 2) : null;

                // Percentage shown under each term column header is always
                // "out of FULL_MARK_PER_TERM" for any term that was sat (100%),
                // and the annual summary percentage scales with how many
                // terms were sat (e.g. 200% for 2 terms, 300% for 3 terms).
                $subjectData['per_term_percent'] = [];
                foreach ($allTerms as $term) {
                    $subjectData['per_term_percent'][$term] = $subjectData['per_term'][$term] !== null
                        ? self::FULL_MARK_PER_TERM
                        : null;
                }
                $subjectData['annual_summary_percent'] = $termsSat > 0
                    ? $termsSat * self::FULL_MARK_PER_TERM
                    : null;
            }

            // Overall (all-subjects) summary per term and annual
            $annualOverallSum = 0;
            $annualOverallTermsSat = 0;
            $overallPerTermPercent = [];
            foreach ($allTerms as $term) {
                if ($overallTermsSat[$term] > 0) {
                    $annualOverallSum += $overallPerTerm[$term];
                    $annualOverallTermsSat++;
                    $overallPerTermPercent[$term] = self::FULL_MARK_PER_TERM;
                } else {
                    $overallPerTerm[$term] = null;
                    $overallPerTermPercent[$term] = null;
                }
            }

            // Total obtainable marks across all subjects and all terms the student actually sat,
            // e.g. 10 subjects x 100 x 3 terms sat = 3000. Used for the Overall Average percentage.
            $subjectCount = count($studentData['subjects']);
            $totalObtainable = $subjectCount * self::FULL_MARK_PER_TERM * $annualOverallTermsSat;

            $studentData['overall'] = [
                'per_term' => $overallPerTerm,
                'per_term_percent' => $overallPerTermPercent,
                'annual_summary' => $annualOverallTermsSat > 0 ? $annualOverallSum : null,
                'annual_summary_percent' => $annualOverallTermsSat > 0 ? $annualOverallTermsSat * self::FULL_MARK_PER_TERM : null,
                'annual_average' => $annualOverallTermsSat > 0 ? round($annualOverallSum / $annualOverallTermsSat, 2) : null,
                'total_obtainable' => $totalObtainable > 0 ? $totalObtainable : null,
                'overall_average_percent' => $totalObtainable > 0
                    ? round(($annualOverallSum * 100) / $totalObtainable, 2)
                    : null,
            ];
        }
        unset($studentData, $subjectData);

        // Remarks and skills/behaviours come specifically from the 3rd Term root,
        // since the cumulative report's "Comments, Skills & Behaviours" section
        // reflects the final term of the session, not just "whichever is latest".
        $thirdTermRoot = $roots['3rd Term'] ?? null;

        $teacherRemarks = $thirdTermRoot
            ? TeacherRemark::where('result_root_id', $thirdTermRoot->id)->get()->keyBy('student_id')
            : collect();

        $hosRemarks = $thirdTermRoot
            ? HOSRemark::where('result_root_id', $thirdTermRoot->id)->get()->keyBy('student_id')
            : collect();

        if ($thirdTermRoot) {
            foreach ($students as $studentId => &$studentData) {
                $usb = StudentSkillBehaviour::with('scores.category')
                    ->where('student_id', $studentId)
                    ->whereHas('skillBehaviour', function ($q) use ($thirdTermRoot) {
                        $q->where('result_root_id', $thirdTermRoot->id);
                    })
                    ->first();

                $skills = [];
                $behaviours = [];

                if ($usb) {
                    foreach ($usb->scores as $score) {
                        if ($score->category->type === 'skill') {
                            $skills[] = $score;
                        } elseif ($score->category->type === 'behavior') {
                            $behaviours[] = $score;
                        }
                    }
                }

                $studentData['third_term_skills'] = $skills;
                $studentData['third_term_behaviours'] = $behaviours;
            }
            unset($studentData);
        } else {
            foreach ($students as $studentId => &$studentData) {
                $studentData['third_term_skills'] = [];
                $studentData['third_term_behaviours'] = [];
            }
            unset($studentData);
        }

        // Rank students by overall annual summary (highest first) for position purposes, if needed later.
        uasort($students, function ($a, $b) {
            return ($b['overall']['annual_summary'] ?? 0) <=> ($a['overall']['annual_summary'] ?? 0);
        });

        return [
            'session' => $academicSession,
            'class' => $class,
            'all_terms' => $allTerms,
            'terms_present' => $termsPresent,
            'roots' => $roots,
            'subject_order' => $subjectOrder,
            'third_term_headers' => $thirdTermHeaders,
            'students' => $students,
            'teacherRemarks' => $teacherRemarks,
            'hosRemarks' => $hosRemarks,
            'thirdTermRoot' => $thirdTermRoot,
        ];
    }

    /**
     * Convenience helper to build the dataset filtered down to a single student.
     */
    public function buildForStudent(string $academicSession, int $classId, int $studentId): array
    {
        $data = $this->build($academicSession, $classId);

        if (isset($data['students'][$studentId])) {
            $data['students'] = [$studentId => $data['students'][$studentId]];
        } else {
            $data['students'] = [];
        }

        return $data;
    }
}