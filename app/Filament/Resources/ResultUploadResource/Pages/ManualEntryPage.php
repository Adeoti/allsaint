<?php

namespace App\Filament\Resources\ResultUploadResource\Pages;

use Filament\Pages\Page;
use App\Models\ResultRoot;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use App\Models\ResultUpload;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ManualEntryPage extends Page
{
    protected static string $view = 'filament.resources.result-upload-resource.pages.manual-entry';
    
    public $resultRootId;
    public $classId;
    public $subjectId;
    public $students = [];
    public $examColumns = [];
    public $existingData = [];
    public $classList = [];
    public $subjectList = [];
    public $resultRootList = [];

    public function mount(Request $request): void
    {
        $this->resultRootList = ResultRoot::orderBy('created_at', 'desc')->get();
        $this->classList = SchoolClass::all();
        $this->subjectList = Subject::all();
        
        $this->resultRootId = $request->get('result_root_id');
        $this->classId = $request->get('class_id');
        $this->subjectId = $request->get('subject_id');
        
        if ($this->resultRootId && $this->classId && $this->subjectId) {
            $this->loadData();
        }
    }

    private function loadData(): void
    {
        if ($this->resultRootId && $this->classId && $this->subjectId) {
            // Get students in the class
            $this->students = User::whereHas('student', function ($query) {
                $query->where('student_class', $this->classId);
            })->with('student')->get();
            
            // Get result root and exam columns
            $resultRoot = ResultRoot::find($this->resultRootId);
            $this->examColumns = $resultRoot->exam_score_columns ?? [];
            
            // Get existing manual entry
            $existingEntry = ResultUpload::where('result_root_id', $this->resultRootId)
                ->where('class_id', $this->classId)
                ->where('subject_id', $this->subjectId)
                ->where('entry_type', 'manual')
                ->first();

            // Log existing data load
            Log::info('Loading existing manual entry data', [
                'result_root_id' => $this->resultRootId,
                'class_id' => $this->classId,
                'subject_id' => $this->subjectId,
                'existing_entry_found' => $existingEntry ? true : false,
                'processed_by' => Auth::id(),
            ]);
            
            $this->existingData = $existingEntry ? json_decode($existingEntry->card_items, true) : [];
        }
    }

    public function saveManualEntry(Request $request)
    {
        $validated = $request->validate([
            'result_root_id' => 'required|exists:result_roots,id',
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'scores' => 'required|array',
            'scores.*' => 'array',
        ]);

        try {
            DB::beginTransaction();
            
            $resultRoot = ResultRoot::find($validated['result_root_id']);
            $examColumns = $resultRoot->exam_score_columns ?? [];
            $gradingSystem = $resultRoot->gradingSystem;
            $gradingRules = $gradingSystem ? $gradingSystem->grading_system : [];
            
            $processedData = [];
            $totalScores = [];
            
            // Process each student's scores
            foreach ($validated['scores'] as $studentId => $studentScores) {
                $scoresArray = [];
                $totalScore = 0;
                
                foreach ($examColumns as $column) {
                    $label = $column['label'];
                    $maxScore = (int) $column['overall_score'];
                    
                    // Create the CSV format key: "3rd CA - 20"
                    $csvKey = $label . ' - ' . $maxScore;
                    
                    // First check if score exists with CSV format key
                    if (isset($studentScores[$csvKey])) {
                        $score = (float) $studentScores[$csvKey];
                    } 
                    // Fallback to old format (just label) for backward compatibility
                    elseif (isset($studentScores[$label])) {
                        $score = (float) $studentScores[$label];
                    } else {
                        $score = 0;
                    }
                    
                    // Validate score doesn't exceed max
                    $score = min($score, $maxScore);
                    $score = max($score, 0);
                    
                    // Store using CSV format key: "3rd CA - 20"
                    $scoresArray[$csvKey] = (string) $score;
                    $totalScore += $score;
                }
                
                // Determine grade and remark
                $grade = 'F';
                $remark = 'Failed';
                
                foreach ($gradingRules as $rule) {
                    if ($totalScore >= $rule['min_score'] && $totalScore <= $rule['max_score']) {
                        $grade = $rule['grade'];
                        $remark = $rule['remark'];
                        break;
                    }
                }
                
                $processedData[$studentId] = [
                    'scores' => $scoresArray, // This now has keys like "3rd CA - 20"
                    'total' => $totalScore,
                    'grade' => $grade,
                    'remark' => $remark,
                    'average' => '',
                    'highest' => '',
                    'lowest' => '',
                    'position' => '',
                ];
                
                $totalScores[$studentId] = $totalScore;
            }
            
            // Calculate statistics
            $this->calculateStatistics($processedData, $totalScores);
            
            // Create or update the manual entry
            ResultUpload::updateOrCreate(
                [
                    'result_root_id' => $validated['result_root_id'],
                    'class_id' => $validated['class_id'],
                    'subject_id' => $validated['subject_id'],
                    'entry_type' => 'manual',
                ],
                [
                    'file_path' => null,
                    'card_items' => json_encode($processedData),
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]
            );
            
            DB::commit();

            // Log the result
            Log::info('Manual entry saved', [
                'result_root_id' => $validated['result_root_id'],
                'class_id' => $validated['class_id'],
                'subject_id' => $validated['subject_id'],
                'scores' => $validated['scores'],
                'processed_by' => Auth::id(),
            ]);
            
           return redirect()->back()->with('success', 'Manual entry saved successfully!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving manual entry', [
                'error' => $e->getMessage(),
                'result_root_id' => $validated['result_root_id'] ?? null,
                'class_id' => $validated['class_id'] ?? null,
                'subject_id' => $validated['subject_id'] ?? null,
                'processed_by' => Auth::id(),
            ]);
            return redirect()
                ->route('filament.admin.resources.result-uploads.index')
                ->with('error', 'Error saving manual entry: ' . $e->getMessage());
        }
    }
    
    private function calculateStatistics(&$processedData, $totalScores): void
    {
        if (empty($processedData)) {
            return;
        }
        
        $average = count($totalScores) > 0 ? array_sum($totalScores) / count($totalScores) : 0;
        $highest = count($totalScores) > 0 ? max($totalScores) : 0;
        $lowest = count($totalScores) > 0 ? min($totalScores) : 0;
        
        // Sort for positions
        arsort($totalScores);
        $position = 1;
        $previousScore = null;
        $positionMapping = [];
        
        foreach ($totalScores as $studentId => $score) {
            if ($score !== $previousScore) {
                $positionMapping[$studentId] = $position;
            } else {
                $positionMapping[$studentId] = $position - 1;
            }
            $previousScore = $score;
            $position++;
        }
        
        // Update all students
        foreach ($processedData as $studentId => &$studentData) {
            $studentData['average'] = round($average, 2);
            $studentData['highest'] = $highest;
            $studentData['lowest'] = $lowest;
            $studentData['position'] = $this->getPositionSuffix($positionMapping[$studentId] ?? 0);
        }
    }
    
    private function getPositionSuffix($position): string
    {
        if ($position == 0) return 'N/A';
        
        $suffix = 'th';
        if (!in_array(($position % 100), [11, 12, 13])) {
            switch ($position % 10) {
                case 1: $suffix = 'st'; break;
                case 2: $suffix = 'nd'; break;
                case 3: $suffix = 'rd'; break;
            }
        }
        return $position . $suffix;
    }

    public function getTitle(): string
    {
        return 'Manual Result Entry';
    }
}