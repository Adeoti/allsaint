<?php

namespace App\Filament\Student\Resources\ResultUploadResource\Pages;

use App\Filament\Student\Resources\ResultUploadResource;
use App\Models\ResultRoot;
use App\Models\ResultUpload;
use App\Models\TeacherRemark;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;

class StudentMyViewResult extends Page
{
    protected static string $resource = ResultUploadResource::class;

    protected static string $view = 'filament.student.resources.result-upload-resource.pages.student-my-view-result';

    public ResultRoot $record;
    public $schoolDetails;
    public $resultUploads;
    public $resultRoot;
    public $teacherRemark;
    public $hosRemark;

    public function mount(ResultRoot $record)
    {
        $this->schoolDetails = getSchoolDetails();
        $this->record = $record;

        // Fetch result uploads for the specific result root record
        $this->resultUploads = ResultUpload::where('result_root_id', $this->record->id)->get();

        // Load teacher remark for the logged-in student for this result root
        $this->teacherRemark = TeacherRemark::where('student_id', Auth::id())
            ->where('result_root_id', $record->id)
            ->first();

        $this->hosRemark = \App\Models\HOSRemark::where('student_id', Auth::id())
            ->where('result_root_id', $record->id)
            ->first();
    }

    public function getTitle(): string
    {
        return '';
    }
}
