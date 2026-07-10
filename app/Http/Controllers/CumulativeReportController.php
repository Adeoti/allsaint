<?php

namespace App\Http\Controllers;

use App\Models\ResultRoot;
use App\Models\SchoolClass;
use App\Services\CumulativeReportService;
use Illuminate\Http\Request;

class CumulativeReportController extends Controller
{
    public function __construct(protected CumulativeReportService $cumulativeReportService)
    {
    }

    /**
     * Show the selection form: pick academic session + class.
     */
    public function select()
    {
        $sessions = ResultRoot::academicSessionOptions();

        // Only show classes that actually have at least one result root with uploads,
        // so teachers don't pick an empty class.
        $classes = SchoolClass::orderBy('name')->get();

        return view('cumulative-reports.select', compact('sessions', 'classes'));
    }

    /**
     * Handle the form submission and redirect to the report page.
     */
    public function go(Request $request)
    {
        $validated = $request->validate([
            'academic_session' => ['required', 'string'],
            'class_id' => ['required', 'integer', 'exists:classes,id'],
        ]);

        return redirect()->route('cumulative-reports.show', [
            'academic_session' => $validated['academic_session'],
            'class_id' => $validated['class_id'],
        ]);
    }

    /**
     * Display the cumulative report for an entire class.
     */
    public function show(Request $request)
    {
        $validated = $request->validate([
            'academic_session' => ['required', 'string'],
            'class_id' => ['required', 'integer', 'exists:classes,id'],
        ]);

        $schoolDetails = getSchoolDetails();

        $data = $this->cumulativeReportService->build(
            $validated['academic_session'],
            (int) $validated['class_id']
        );

        return view('cumulative-reports.show', [
            'schoolDetails' => $schoolDetails,
            'session' => $data['session'],
            'class' => $data['class'],
            'allTerms' => $data['all_terms'],
            'termsPresent' => $data['terms_present'],
            'roots' => $data['roots'],
            'subjectOrder' => $data['subject_order'],
            'thirdTermHeaders' => $data['third_term_headers'],
            'students' => $data['students'],
            'teacherRemarks' => $data['teacherRemarks'],
            'hosRemarks' => $data['hosRemarks'],
            'thirdTermRoot' => $data['thirdTermRoot'],
        ]);
    }
}