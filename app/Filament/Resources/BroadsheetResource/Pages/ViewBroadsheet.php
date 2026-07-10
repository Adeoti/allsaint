<?php

namespace App\Filament\Resources\BroadsheetResource\Pages;

use Filament\Pages\Page;
use App\Models\Broadsheet;
use App\Models\SchoolClass;
use Illuminate\Http\Request;

class ViewBroadsheet extends Page
{
    protected static string $view = 'filament.resources.broadsheet-resource.pages.view-broadsheet';

    public Broadsheet $record;
    public $broadsheetData;
    public $subjects;
    public $schoolDetails;
    public $classInfo;

    public function mount(Request $request, Broadsheet $record): void
    {
        $this->record = $record;
        $this->schoolDetails = getSchoolDetails();
        $this->classInfo = SchoolClass::find($record->class_id);

        // Load or generate broadsheet data
        if ($record->generated_data) {
            $this->broadsheetData = $record->generated_data;
        } else {
            // Auto-generate if not already generated
            $this->broadsheetData = $record->generateBroadsheetData();
        }
    }

    public function getTitle(): string
    {
        return $this->record->name;
    }

    public function regenerate(Request $request, Broadsheet $record)
    {
        try {
            $record->generateBroadsheetData();

            return response()->json([
                'success' => true,
                'message' => 'Broadsheet regenerated successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
