<?php

namespace App\Http\Controllers;

use App\Models\Broadsheet;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class BroadsheetController extends Controller
{
    public function view(Broadsheet $record)
    {
        $schoolDetails = getSchoolDetails();
        $classInfo = \App\Models\SchoolClass::find($record->class_id);


        // Load or generate broadsheet data
        if (!$record->generated_data) {
            $record->generateBroadsheetData();
            $record->refresh();
        }

        return view('filament.resources.broadsheet-resource.pages.view-broadsheet', [
            'record' => $record,
            'broadsheetData' => $record->generated_data,
            'schoolDetails' => $schoolDetails,
            'classInfo' => $classInfo,
        ]);
    }

    public function regenerate(Broadsheet $record, Request $request)
    {
        try {
            $record->generateBroadsheetData();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Broadsheet regenerated successfully!'
                ]);
            }

            return back()->with('success', 'Broadsheet regenerated successfully!');
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function downloadPdf(Broadsheet $record)
    {
        if (!$record->generated_data) {
            return back()->with('error', 'Broadsheet data not generated yet.');
        }

        $schoolDetails = getSchoolDetails();
        $classInfo = \App\Models\SchoolClass::find($record->class_id);

        $data = [
            'broadsheet' => $record,
            'broadsheetData' => $record->generated_data,
            'schoolDetails' => $schoolDetails,
            'classInfo' => $classInfo,
        ];

        $pdf = Pdf::loadView('pdf.broadsheet', $data);
        $filename = str_replace(' ', '_', $record->name) . '_' . date('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    public function downloadCsv(Broadsheet $record)
    {
        if (!$record->generated_data) {
            return back()->with('error', 'Broadsheet data not generated yet.');
        }

        $schoolDetails = getSchoolDetails();
        $classInfo = \App\Models\SchoolClass::find($record->class_id);
        $data = $record->generated_data;

        $filename = str_replace(' ', '_', $record->name) . '_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($data, $schoolDetails, $classInfo, $record) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [$schoolDetails['school_name'] ?? 'School Name']);
            fputcsv($file, [$schoolDetails['school_address'] ?? '']);
            fputcsv($file, ['Broadsheet: ' . $record->name]);
            fputcsv($file, [
                'Class: ' . ($classInfo->name ?? 'N/A'),
                'Term: ' . ($record->term ?? 'N/A'),
                'Students: ' . ($data['total_students'] ?? 0),
            ]);
            fputcsv($file, [
                'Generated: ' . \Carbon\Carbon::parse($data['generated_at'] ?? now())->format('F j, Y h:i A'),
            ]);
            fputcsv($file, []);

            $headerRow1 = ['S/No', 'Student Name'];
            $headerRow2 = ['', ''];
            foreach ($data['subjects'] ?? [] as $subjectId => $subjectName) {
                $headerRow1[] = $subjectName;
                $headerRow1[] = '';
                $headerRow2[] = 'Score';
                $headerRow2[] = 'Grade';
            }
            $headerRow1 = array_merge($headerRow1, ['Total', 'Average %', 'Position', 'Remarks']);
            $headerRow2 = array_merge($headerRow2, ['', '', '', '']);
            fputcsv($file, $headerRow1);
            fputcsv($file, $headerRow2);

            foreach ($data['students'] ?? [] as $student) {
                $row = [$student['sno'] ?? '', $student['name'] ?? ''];
                foreach ($data['subjects'] ?? [] as $subjectId => $subjectName) {
                    $row[] = $student['subjects'][$subjectId]['score'] ?? '';
                    $row[] = $student['subjects'][$subjectId]['grade'] ?? '';
                }
                $average = $student['average'] ?? 0;
                $row[] = $student['total'] ?? '';
                $row[] = $average;
                $row[] = $student['position'] ?? '';
                $row[] = $average >= 70 ? 'Excellent' : ($average >= 50 ? 'Good' : 'Improve');
                fputcsv($file, $row);
            }

            if (!empty($data['students'])) {
                $classTotal = array_sum(array_column($data['students'], 'total'));
                $classAverage = round($classTotal / count($data['students']), 1);
                $highestTotal = max(array_column($data['students'], 'total'));
                $lowestTotal = min(array_column($data['students'], 'total'));

                $summaryRow = ['', 'CLASS SUMMARY'];
                foreach ($data['subjects'] ?? [] as $subjectId => $subjectName) {
                    $scores = [];
                    foreach ($data['students'] as $student) {
                        if (isset($student['subjects'][$subjectId]['score'])) {
                            $scores[] = $student['subjects'][$subjectId]['score'];
                        }
                    }
                    $summaryRow[] = count($scores) ? round(array_sum($scores) / count($scores), 1) : 0;
                    $summaryRow[] = '';
                }
                $summaryRow[] = $classTotal;
                $summaryRow[] = $classAverage;
                $summaryRow[] = '';
                $summaryRow[] = "H:$highestTotal L:$lowestTotal";

                fputcsv($file, $summaryRow);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }
    
}
