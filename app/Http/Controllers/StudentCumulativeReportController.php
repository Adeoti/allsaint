<?php

namespace App\Http\Controllers;

use App\Models\ResultRoot;
use App\Services\CumulativeReportService;
use Illuminate\Http\Request;

class StudentCumulativeReportController extends Controller
{
    public function __construct(protected CumulativeReportService $cumulativeReportService)
    {
    }

    /**
     * Show the selection form for the logged-in student: pick academic session only.
     * Class is implicit (their own class).
     */
    public function select()
    {
        $sessions = ResultRoot::academicSessionOptions();

        return view('cumulative-reports.student-select', compact('sessions'));
    }

    public function go(Request $request)
    {
        $validated = $request->validate([
            'academic_session' => ['required', 'string'],
        ]);

        return redirect()->route('student.cumulative-reports.show', [
            'academic_session' => $validated['academic_session'],
        ]);
    }

    /**
     * Display the cumulative report for the logged-in student only.
     */
    public function show(Request $request)
    {
        $validated = $request->validate([
            'academic_session' => ['required', 'string'],
        ]);

        $user = auth()->user();
        $classId = $user->student_class;

        if (!$classId) {
            abort(403, 'No class is assigned to your account.');
        }

        $schoolDetails = getSchoolDetails();

        $data = $this->cumulativeReportService->buildForStudent(
            $validated['academic_session'],
            (int) $classId,
            $user->id
        );

        return view('cumulative-reports.student-show', [
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