<?php

namespace App\Services;

use App\Models\ResultUpload;
use App\Models\ResultRoot;
use App\Models\GradingSystem;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ResultService
{
    /**
     * Process CSV file and create ResultUpload
     */
    public function processCsvUpload($file, $resultRootId, $classId, $subjectId, $userId)
    {
        // Store the file
        $filePath = $file->store('result_uploads', 'public');
        
        // Process the CSV
        $csvPath = Storage::disk('public')->path($filePath);
        $processedData = $this->processCsvFile($csvPath, $resultRootId);
        
        // Create or update the ResultUpload
        return ResultUpload::updateOrCreate(
            [
                'result_root_id' => $resultRootId,
                'class_id' => $classId,
                'subject_id' => $subjectId,
                'entry_type' => 'csv',
            ],
            [
                'file_path' => $filePath,
                'card_items' => $processedData,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]
        );
    }
    
    /**
     * Create manual result entry
     */
    public function createManualEntry($data, $resultRootId, $classId, $subjectId, $userId)
    {
        $resultRoot = ResultRoot::findOrFail($resultRootId);
        $examColumns = $resultRoot->exam_score_columns ?? [];
        $gradingSystemId = $resultRoot->grading_system_id;
        
        // Process manual data
        $processedData = $this->processManualData($data, $examColumns, $gradingSystemId);
        
        // Create or update the ResultUpload
        return ResultUpload::updateOrCreate(
            [
                'result_root_id' => $resultRootId,
                'class_id' => $classId,
                'subject_id' => $subjectId,
                'entry_type' => 'manual',
            ],
            [
                'file_path' => null, // No file for manual entries
                'card_items' => $processedData,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]
        );
    }
    
    /**
     * Process manual entry data
     */
    private function processManualData($data, $examColumns, $gradingSystemId)
    {
        $processedData = [];
        $totalScores = [];
        $gradingSystem = GradingSystem::find($gradingSystemId)?->grading_system;
        
        foreach ($data['students'] as $studentId => $studentScores) {
            $scoresArray = [];
            $totalScore = 0;
            
            // Process each exam column
            foreach ($examColumns as $column) {
                $label = $column['label'];
                $maxScore = (int)$column['overall_score'];
                $score = isset($studentScores[$label]) ? (int)$studentScores[$label] : 0;
                
                // Validate score doesn't exceed max
                $score = min($score, $maxScore);
                $score = max($score, 0); // Ensure not negative
                
                $scoresArray[$label] = (string)$score;
                $totalScore += $score;
            }
            
            // Get grade and remark
            $gradingInfo = $this->getGradeFromScore($totalScore, $gradingSystem);
            
            $processedData[$studentId] = [
                'scores' => $scoresArray,
                'total' => $totalScore,
                'grade' => $gradingInfo['grade'],
                'remark' => $gradingInfo['remark'],
                'average' => '', // Will be calculated after all students
                'highest' => '',
                'lowest' => '',
                'position' => '',
            ];
            
            $totalScores[$studentId] = $totalScore;
        }
        
        // Calculate statistics
        $this->calculateStatistics($processedData, $totalScores);
        
        return $processedData;
    }
    
    /**
     * Process CSV file (existing logic adapted)
     */
    private function processCsvFile($csvPath, $resultRootId)
    {
        $gradingSystem = ResultRoot::find($resultRootId)?->gradingSystem;
        if (!$gradingSystem) {
            Log::error("No grading system found for result_root_id: " . $resultRootId);
            return [];
        }
        
        $gradingRules = $gradingSystem->grading_system;
        $processedData = [];
        $totalScores = [];
        
        if (($handle = fopen($csvPath, 'r')) !== false) {
            $headers = fgetcsv($handle);
            
            while (($data = fgetcsv($handle)) !== false) {
                $row = array_combine($headers, $data);
                $studentId = $row['Student_ID'];
                unset($row['Student_ID']);
                
                $totalScore = array_sum(array_map('intval', $row));
                $totalScores[$studentId] = $totalScore;
                
                $gradingInfo = $this->getGradeFromScore($totalScore, $gradingRules);
                
                $processedData[$studentId] = [
                    'scores' => $row,
                    'total' => $totalScore,
                    'grade' => $gradingInfo['grade'],
                    'remark' => $gradingInfo['remark'],
                    'average' => '',
                    'highest' => '',
                    'lowest' => '',
                    'position' => '',
                ];
            }
            fclose($handle);
        }
        
        $this->calculateStatistics($processedData, $totalScores);
        
        return $processedData;
    }
    
    /**
     * Calculate statistics for all students
     */
    private function calculateStatistics(&$processedData, $totalScores)
    {
        if (empty($processedData)) {
            return;
        }
        
        $averageScore = $this->calculateAverage($totalScores);
        $highestScore = max($totalScores);
        $lowestScore = min($totalScores);
        
        // Sort for positions
        arsort($totalScores);
        $rank = 1;
        $previousScore = null;
        $positionMapping = [];
        
        foreach ($totalScores as $studentId => $score) {
            if ($score !== $previousScore) {
                $positionMapping[$studentId] = $rank;
            } else {
                $positionMapping[$studentId] = $rank - 1;
            }
            $previousScore = $score;
            $rank++;
        }
        
        // Update all students with statistics
        foreach ($processedData as $studentId => &$studentData) {
            $studentData['average'] = $averageScore;
            $studentData['highest'] = $highestScore;
            $studentData['lowest'] = $lowestScore;
            $studentData['position'] = $this->getPositionSuffix($positionMapping[$studentId] ?? 0);
        }
    }
    
    /**
     * Helper methods (same as before)
     */
    private function calculateAverage($totalScores)
    {
        if (count($totalScores) === 0) {
            return 0;
        }
        return round(array_sum($totalScores) / count($totalScores), 2);
    }
    
    private function getGradeFromScore($score, $gradingSystem)
    {
        foreach ($gradingSystem as $gradeRule) {
            if ($score >= $gradeRule['min_score'] && $score <= $gradeRule['max_score']) {
                return [
                    'grade' => $gradeRule['grade'],
                    'remark' => $gradeRule['remark'],
                ];
            }
        }
        
        return [
            'grade' => 'F',
            'remark' => 'Failed',
        ];
    }
    
    private function getPositionSuffix($position)
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
    
    /**
     * Get students for manual entry
     */
    public function getStudentsForClass($classId)
    {
        return User::whereHas('student', function ($query) use ($classId) {
            $query->where('student_class', $classId);
        })->with('student')->get();
    }
    
    /**
     * Get existing manual entry for editing
     */
    public function getManualEntry($resultRootId, $classId, $subjectId)
    {
        return ResultUpload::where('result_root_id', $resultRootId)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('entry_type', 'manual')
            ->first();
    }
}