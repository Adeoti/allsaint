<?php

namespace App\Models;

use App\Models\User;
use App\Models\ResultRoot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ResultUpload extends Model
{
    //

    public function resultRoot()
    {
        return $this->belongsTo(ResultRoot::class);
    }
    public function class()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }


    protected $fillable = [
        'result_root_id',
        'file_path',
        'card_items',
        'subject_id',
        'class_id',
        'entry_type',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'card_items' => 'array',
        'file_path' => 'array'
    ];



    protected static function booted()
    {
        static::saved(function (ResultUpload $record) {
            $record->processCsvFile();
        });
    }


    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function processCsvFile()
    {
        // Ensure there's a file path before processing
        if (!$this->file_path) {
            return;
        }

        // Path to the CSV file
        $csvPath = Storage::disk('public')->path($this->file_path);
        Log::info($csvPath);

        // Retrieve the grading system based on the result_root_id
        $gradingSystem = ResultRoot::find($this->result_root_id)?->gradingSystem;
        if (!$gradingSystem) {
            Log::error("No grading system found for result_root_id: " . $this->result_root_id);
            return;
        }

        // Now you can directly use the grading_system property as it is already decoded
        $gradingSystem = $gradingSystem->grading_system;

        // Initialize an array to hold the processed data
        $processedData = [];
        $totalScores = []; // Array to store total scores for calculating highest, lowest, and average

        // Open and read the CSV file
        if (($handle = fopen($csvPath, 'r')) !== false) {
            // Read header row for column labels
            $headers = fgetcsv($handle);

            // Process each row in the CSV
            while (($data = fgetcsv($handle)) !== false) {
                $row = array_combine($headers, $data);
                $studentId = $row['Student_ID'];
                unset($row['Student_ID']);

                $totalScore = array_sum(array_map('intval', $row));
                $totalScores[$studentId] = $totalScore;

                $gradingInfo = $this->getGradeFromScore($totalScore, $gradingSystem);

                $processedData[$studentId] = [
                    'scores' => $row,
                    'total' => $totalScore,
                    'grade' => $gradingInfo['grade'],
                    'remark' => $gradingInfo['remark'],
                    'average' => count($row) > 0 ? round($totalScore / count($row), 2) : 0,  // per-student now
                    'highest' => '',
                    'lowest' => '',
                    'position' => '',
                ];
            }
            fclose($handle);
        }

        // Calculate average, highest, and lowest scores
        $averageScore = $this->calculateAverage($totalScores);
        $highestScoreStudent = array_search(max($totalScores), $totalScores);
        // $lowestScoreStudent = array_search(min($totalScores), $totalScores);

        // Filter out zero values before finding the minimum
        $filteredScores = array_filter($totalScores, function ($score) {
            return $score > 0;
        });

        $lowestScoreStudent = array_search(min($filteredScores), $totalScores); // Use filtered scores



        // Sort scores in descending order for ranking
        arsort($totalScores); // Keep keys for mapping back to students

        // Assign positions
        $rank = 1;
        $previousScore = null;
        $positionMapping = [];
        foreach ($totalScores as $studentId => $score) {
            if ($score !== $previousScore) {
                $positionMapping[$studentId] = $rank; // New rank for different score
            } else {
                $positionMapping[$studentId] = $rank - 1; // Same rank for ties
            }
            $previousScore = $score;
            $rank++;
        }

        // Set the highest, lowest, and average values in the processed data
        foreach ($processedData as $studentId => &$studentData) {
            $studentData['highest'] = $processedData[$highestScoreStudent]['total'];
            $studentData['lowest'] = $processedData[$lowestScoreStudent]['total'];
            $studentData['position'] = $this->getPositionSuffix($positionMapping[$studentId]);
        }

        // Save the JSON structure to card_items
        // We will now encode it as a properly formatted JSON
        $this->card_items = json_encode($processedData, JSON_PRETTY_PRINT);
        $this->saveQuietly(); // Save without triggering the saved event again
    }

    public function calculateAverage($totalScores)
    {
        // Calculate the average score
        $sum = array_sum($totalScores);
        $count = count($totalScores);

        // Avoid division by zero
        if ($count === 0) {
            return 0;
        }

        return round($sum / $count, 2); // Round to 2 decimal places
    }

    public function getGradeFromScore($score, $gradingSystem)
    {
        // Loop through the grading system and find the corresponding grade and remark
        foreach ($gradingSystem as $gradeRule) {
            if ($score >= $gradeRule['min_score'] && $score <= $gradeRule['max_score']) {
                return [
                    'grade' => $gradeRule['grade'],
                    'remark' => $gradeRule['remark'],
                ];
            }
        }

        // If no grade matches, return a default grade and remark
        return [
            'grade' => 'F',
            'remark' => 'Failed',
        ];
    }


    public function getPositionSuffix($position)
    {
        // Return the position with the correct ordinal suffix
        $suffix = 'th';
        if (!in_array(($position % 100), [11, 12, 13])) {
            switch ($position % 10) {
                case 1:
                    $suffix = 'st';
                    break;
                case 2:
                    $suffix = 'nd';
                    break;
                case 3:
                    $suffix = 'rd';
                    break;
            }
        }
        return $position . $suffix;
    }
}
