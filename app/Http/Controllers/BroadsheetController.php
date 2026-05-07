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
    
    public function downloadPdf(Broadsheet $broadcast)
    {
        if (!$broadcast->generated_data) {
            return back()->with('error', 'Broadsheet data not generated yet.');
        }

        $schoolDetails = getSchoolDetails();
        $classInfo = \App\Models\SchoolClass::find($broadcast->class_id);
        
        $data = [
            'broadsheet' => $broadcast,
            'broadsheetData' => $broadcast->generated_data,
            'schoolDetails' => $schoolDetails,
            'classInfo' => $classInfo,
        ];

        $pdf = Pdf::loadView('pdf.broadsheet', $data);
        
        $filename = str_replace(' ', '_', $broadcast->name) . '_' . date('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
    }
}