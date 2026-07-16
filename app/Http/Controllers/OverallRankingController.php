<?php

namespace App\Http\Controllers;

use App\Models\ResultRoot;
use App\Models\ResultUpload;
use App\Models\SchoolClass;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class OverallRankingController extends Controller
{
    public function select()
    {
        $sessions = ResultRoot::orderByDesc('academic_session')
            ->pluck('academic_session')
            ->unique()
            ->values();

        return view('overall-ranking.select', compact('sessions'));
    }

    public function getClasses(Request $request)
    {
        $request->validate(['academic_session' => 'required|string']);

        $rootIds = ResultRoot::where('academic_session', $request->academic_session)->pluck('id');

        $classIds = ResultUpload::whereIn('result_root_id', $rootIds)
            ->distinct()
            ->pluck('class_id');

        $classes = SchoolClass::whereIn('id', $classIds)->orderBy('name')->get(['id', 'name']);

        return response()->json($classes);
    }

    /**
     * Term order used for column display, matching ResultRoot::termOptions().
     */
    private function termOrder(): array
    {
        return ['1st Term', '2nd Term', '3rd Term'];
    }

    private function buildRanking(Request $request): ?array
    {
        $request->validate([
            'academic_session' => 'required|string',
            'class_id' => 'required|exists:classes,id',
        ]);

        $class = SchoolClass::findOrFail($request->class_id);

        // All result roots (terms) for this session, in chronological term order
        $roots = ResultRoot::where('academic_session', $request->academic_session)->get();

        if ($roots->isEmpty()) {
            return null;
        }

        $termOrder = $this->termOrder();
        $roots = $roots->sortBy(fn ($r) => array_search($r->term, $termOrder))->values();

        // studentId => ['name' => ..., 'terms' => ['1st Term' => total, ...], 'overall' => x]
        $studentData = [];
        $termsPresent = [];       // which terms actually have data for this class
        $termExpected = [];       // term => expected max for that term
        $overallExpected = 0;

        foreach ($roots as $root) {
            $uploads = ResultUpload::where('result_root_id', $root->id)
                ->where('class_id', $class->id)
                ->get();

            if ($uploads->isEmpty()) {
                continue; // no data uploaded for this term/class yet
            }

            $termsPresent[] = $root->term;

            $examColumns = $root->exam_score_columns ?? [];
            $subjectMax = 0;
            foreach ($examColumns as $col) {
                $subjectMax += (float) ($col['overall_score'] ?? 0);
            }
            if ($subjectMax <= 0) {
                $subjectMax = 100; // sane fallback if a root has no configured columns
            }

            $subjectsCount = $uploads->count();
            $termExpectedValue = $subjectMax * $subjectsCount;
            $termExpected[$root->term] = $termExpectedValue;
            $overallExpected += $termExpectedValue;

            // Sum each student's total across all subjects for this term
            foreach ($uploads as $upload) {
                $cardItems = is_array($upload->card_items)
                    ? $upload->card_items
                    : json_decode($upload->card_items, true);
                $cardItems = $cardItems ?? [];

                foreach ($cardItems as $studentId => $item) {
                    if (!isset($studentData[$studentId])) {
                        $student = User::find($studentId);
                        if (!$student) {
                            continue;
                        }
                        $studentData[$studentId] = [
                            'name' => $student->name,
                            'terms' => [],
                        ];
                    }

                    $total = is_numeric($item['total'] ?? null) ? (float) $item['total'] : 0;
                    $studentData[$studentId]['terms'][$root->term] =
                        ($studentData[$studentId]['terms'][$root->term] ?? 0) + $total;
                }
            }
        }

        if (empty($studentData)) {
            return null;
        }

        // Finalize: fill missing terms with 0, compute overall total
        foreach ($studentData as $studentId => &$data) {
            $overallTotal = 0;
            foreach ($termsPresent as $term) {
                $data['terms'][$term] = $data['terms'][$term] ?? 0;
                $overallTotal += $data['terms'][$term];
            }
            $data['overall_total'] = $overallTotal;
        }
        unset($data);

        // Rank by overall total, standard competition ranking
        uasort($studentData, fn ($a, $b) => $b['overall_total'] <=> $a['overall_total']);

        $rank = 0;
        $position = 0;
        $previousScore = null;
        foreach ($studentData as $studentId => &$data) {
            $position++;
            if ($data['overall_total'] !== $previousScore) {
                $rank = $position;
                $previousScore = $data['overall_total'];
            }
            $data['rank'] = $rank;
        }
        unset($data);

        return [
            'class' => $class,
            'academicSession' => $request->academic_session,
            'termsPresent' => $termsPresent,
            'termExpected' => $termExpected,
            'overallExpected' => $overallExpected,
            'students' => $studentData,
        ];
    }

    public function show(Request $request)
    {
        $data = $this->buildRanking($request);

        if (!$data) {
            return back()->with('error', 'No results found for that Session and Class.');
        }

        $schoolDetails = getSchoolDetails();

        return view('overall-ranking.show', array_merge($data, ['schoolDetails' => $schoolDetails]));
    }

    public function downloadPdf(Request $request)
    {
        $data = $this->buildRanking($request);

        if (!$data) {
            return back()->with('error', 'No results found for that Session and Class.');
        }

        $schoolDetails = getSchoolDetails();

        $pdf = Pdf::loadView('pdf.overall-ranking', array_merge($data, ['schoolDetails' => $schoolDetails]));

        $filename = str_replace(' ', '_', $data['class']->name . '_Overall_Ranking_' . $data['academicSession'])
            . '_' . date('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    public function downloadCsv(Request $request)
    {
        $data = $this->buildRanking($request);

        if (!$data) {
            return back()->with('error', 'No results found for that Session and Class.');
        }

        $schoolDetails = getSchoolDetails();

        $filename = str_replace(' ', '_', $data['class']->name . '_Overall_Ranking_' . $data['academicSession'])
            . '_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($data, $schoolDetails) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [$schoolDetails['school_name'] ?? 'School Name']);
            fputcsv($file, ['Overall Ranking Report']);
            fputcsv($file, ['Session: ' . $data['academicSession'], 'Class: ' . $data['class']->name]);
            fputcsv($file, ['Generated: ' . now()->format('F j, Y h:i A')]);
            fputcsv($file, []);

            $header = ['Position', 'Student Name'];
            foreach ($data['termsPresent'] as $term) {
                $header[] = $term . ' Total';
            }
            $header[] = 'Overall Total (Expected ' . $data['overallExpected'] . ')';
            fputcsv($file, $header);

            foreach ($data['students'] as $row) {
                $line = [$row['rank'], $row['name']];
                foreach ($data['termsPresent'] as $term) {
                    $line[] = $row['terms'][$term];
                }
                $line[] = $row['overall_total'];
                fputcsv($file, $line);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }
}