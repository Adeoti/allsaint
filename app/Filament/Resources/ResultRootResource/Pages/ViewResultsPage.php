<?php

namespace App\Filament\Resources\ResultRootResource\Pages;

use App\Models\ResultUpload;
use App\Filament\Resources\ResultRootResource;
use App\Models\HOSRemark;
use App\Models\ResultRoot;
use App\Models\TeacherRemark;
use Filament\Resources\Pages\Page;

class ViewResultsPage extends Page
{
    protected static string $resource = ResultRootResource::class;

    protected static string $view = 'filament.resources.result-root-resource.pages.view-results-page';

    public $resultUploads;
    public ResultRoot $record;
    public $schoolDetails;
    public $teacherRemarks;
    public $hosRemarks;

    public function mount(ResultRoot $record)
    {
        $this->schoolDetails = getSchoolDetails();
        $this->record = $record;

        $this->resultUploads = ResultUpload::where('result_root_id', $record->id)->get();

        $this->teacherRemarks = TeacherRemark::where('result_root_id', $record->id)
            ->get()
            ->keyBy('student_id');

        $this->hosRemarks = HOSRemark::where('result_root_id', $record->id)
            ->get()
            ->keyBy('student_id');
    }

    public function getTitle(): string
    {
        return '';
    }
}
