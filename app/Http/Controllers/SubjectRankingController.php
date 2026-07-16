<?php

namespace App\Http\Controllers;

use App\Models\ResultRoot;
use App\Models\ResultUpload;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class SubjectRankingController extends Controller
{
    /**
     * Selection form: choose Result Root -> Class -> Subject.
     */
    public function select()
    {
        $resultRoots = ResultRoot::orderByDesc('id')->get();

        return view('subject-ranking.select', compact('resultRoots'));
    }

    /**
     * AJAX: classes that have uploads for the chosen result root.
     */
    public function getClasses(Request $request)
    {
        $request->validate(['result_root_id' => 'required|exists:result_roots,id']);

        $classIds = ResultUpload::where('result_root_id', $request->result_root_id)
            ->distinct()
            ->pluck('class_id');

        $classes = SchoolClass::whereIn('id', $classIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($classes);
    }

    /**
     * AJAX: subjects that have uploads for the chosen result root + class.
     */
    public function getSubjects(Request $request)
    {
        $request->validate([
            'result_root_id' => 'required|exists:result_roots,id',
            'class_id' => 'required|exists:classes,id',
        ]);

        $subjectIds = ResultUpload::where('result_root_id', $request->result_root_id)
            ->where('class_id', $request->class_id)
            ->distinct()
            ->pluck('subject_id');

        $subjects = Subject::whereIn('id', $subjectIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($subjects);
    }

    /**
     * Build the ranked list shared by the screen view, PDF, and CSV.
     * Returns null if no matching upload/data exists.
     */
    private function buildRanking(Request $request): ?array
    {
        $request->validate([
            'result_root_id' => 'required|exists:result_roots,id',
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
        ]);

        $resultRoot = ResultRoot::findOrFail($request->result_root_id);
        $class = SchoolClass::findOrFail($request->class_id);
        $subject = Subject::findOrFail($request->subject_id);

        $resultUpload = ResultUpload::where('result_root_id', $resultRoot->id)
            ->where('class_id', $class->id)
            ->where('subject_id', $subject->id)
            ->first();

        if (!$resultUpload) {
            return null;
        }

        $cardItems = is_array($resultUpload->card_items)
            ? $resultUpload->card_items
            : json_decode($resultUpload->card_items, true);

        $cardItems = $cardItems ?? [];

        // Build student rows
        $students = [];
        foreach ($cardItems as $studentId => $item) {
            $student = User::find($studentId);
            if (!$student) {
                continue;
            }

            $total = is_numeric($item['total'] ?? null) ? (float) $item['total'] : 0;

            $students[] = [
                'student_id' => $studentId,
                'name' => $student->name,
                'total' => $item['total'] ?? 'N/A',
                'total_sort' => $total,
                'average' => $item['average'] ?? 'N/A',
                'grade' => $item['grade'] ?? 'N/A',
                'remark' => $item['remark'] ?? 'N/A',
            ];
        }

        // Sort highest total first
        usort($students, fn ($a, $b) => $b['total_sort'] <=> $a['total_sort']);

        // Standard competition ranking (equal scores share a rank; next rank skips accordingly)
        $rank = 0;
        $previousScore = null;
        $position = 0;
        foreach ($students as &$row) {
            $position++;
            if ($row['total_sort'] !== $previousScore) {
                $rank = $position;
                $previousScore = $row['total_sort'];
            }
            $row['rank'] = $rank;
        }
        unset($row);

        return [
            'resultRoot' => $resultRoot,
            'class' => $class,
            'subject' => $subject,
            'students' => $students,
        ];
    }

    public function show(Request $request)
    {
        $data = $this->buildRanking($request);

        if (!$data) {
            return back()->with('error', 'No results found for that Term, Class, and Subject combination.');
        }

        $schoolDetails = getSchoolDetails();

        return view('subject-ranking.show', array_merge($data, [
            'schoolDetails' => $schoolDetails,
        ]));
    }

    public function downloadPdf(Request $request)
    {
        $data = $this->buildRanking($request);

        if (!$data) {
            return back()->with('error', 'No results found for that Term, Class, and Subject combination.');
        }

        $schoolDetails = getSchoolDetails();

        $pdf = Pdf::loadView('pdf.subject-ranking', array_merge($data, [
            'schoolDetails' => $schoolDetails,
        ]));

        $filename = str_replace(' ', '_', $data['subject']->name . '_' . $data['class']->name . '_Ranking')
            . '_' . date('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    public function downloadCsv(Request $request)
    {
        $data = $this->buildRanking($request);

        if (!$data) {
            return back()->with('error', 'No results found for that Term, Class, and Subject combination.');
        }

        $schoolDetails = getSchoolDetails();

        $filename = str_replace(' ', '_', $data['subject']->name . '_' . $data['class']->name . '_Ranking')
            . '_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($data, $schoolDetails) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [$schoolDetails['school_name'] ?? 'School Name']);
            fputcsv($file, ['Subject Ranking Report']);
            fputcsv($file, [
                'Term: ' . ($data['resultRoot']->term ?? 'N/A'),
                'Session: ' . ($data['resultRoot']->academic_session ?? 'N/A'),
                'Class: ' . $data['class']->name,
                'Subject: ' . $data['subject']->name,
            ]);
            fputcsv($file, ['Generated: ' . now()->format('F j, Y h:i A')]);
            fputcsv($file, []);

            fputcsv($file, ['Position', 'Student Name', 'Total', 'Average', 'Grade', 'Remark']);

            foreach ($data['students'] as $row) {
                fputcsv($file, [
                    $row['rank'],
                    $row['name'],
                    $row['total'],
                    $row['average'],
                    $row['grade'],
                    $row['remark'],
                ]);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }
}