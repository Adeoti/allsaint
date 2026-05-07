<?php

namespace App\Http\Controllers;

use App\Models\ResultRoot;
use App\Models\ResultUpload;
use App\Models\TeacherRemark;
use App\Models\HOSRemark;
use Illuminate\Http\Request;

class ReportCardController extends Controller
{
    /**
     * Display the report cards for a given ResultRoot.
     */
    public function show(ResultRoot $record)
    {
        // Authorize if needed – you can add policies or gates here
        // $this->authorize('view', $record);

        // Fetch school details from helper (ensure it's loaded via a helper file or service)
        $schoolDetails = getSchoolDetails();

        // Get all result uploads for this result root
        $resultUploads = ResultUpload::where('result_root_id', $record->id)->get();

        // Get teacher remarks keyed by student_id
        $teacherRemarks = TeacherRemark::where('result_root_id', $record->id)
            ->get()
            ->keyBy('student_id');

        // Get HOS remarks keyed by student_id
        $hosRemarks = HOSRemark::where('result_root_id', $record->id)
            ->get()
            ->keyBy('student_id');

        return view('report-cards.show', compact(
            'record',
            'resultUploads',
            'schoolDetails',
            'teacherRemarks',
            'hosRemarks'
        ));
    }
}