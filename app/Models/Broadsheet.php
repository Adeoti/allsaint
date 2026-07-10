<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;

class Broadsheet extends Model
{
    protected $fillable = [
        'name',
        'term',
        'class_id',
        'result_root_ids',
        'generated_data',
        'description',
        'created_by',
    ];

    protected $casts = [
        'result_root_ids' => 'array',
        'generated_data' => 'array',
    ];

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function resultRoots(): BelongsToMany
    {
        return ResultRoot::whereIn('id', $this->result_root_ids ?? []);
    }

    public function getResultRootsAttribute()
    {
        if (!$this->result_root_ids) {
            return collect();
        }
        return ResultRoot::whereIn('id', $this->result_root_ids)->get();
    }

    // Generate broadsheet data
    public function generateBroadsheetData()
    {
        $classId = $this->class_id;
        $resultRootIds = $this->result_root_ids ?? [];
        
        if (empty($resultRootIds)) {
            return null;
        }

        // Get all students in the class
        $students = User::whereHas('student', function ($query) use ($classId) {
            $query->where('student_class', $classId);
        })->with('student')->get();

        // Get all result uploads for the selected result roots and class
        $resultUploads = ResultUpload::whereIn('result_root_id', $resultRootIds)
            ->where('class_id', $classId)
            ->get();

        // Get all subjects from the result uploads
        $subjectIds = $resultUploads->pluck('subject_id')->unique();
        $subjects = Subject::whereIn('id', $subjectIds)->get();

        // Initialize data structure
        $broadsheetData = [];
        $subjectTotals = [];

        // Process each student
        foreach ($students as $student) {
            $studentId = $student->id;
            $studentData = [
                'sno' => 0,
                'student_id' => $studentId,
                'name' => $student->name,
                'roll_number' => $student->student->roll_number ?? '',
                'subjects' => [],
                'total' => 0,
                'position' => 0,
            ];

            $studentTotal = 0;
            $subjectsWithScores = 0; // Track how many subjects the student actually has scores for

            // Process each subject
            foreach ($subjects as $subject) {
                $subjectId = $subject->id;
                
                // Find result uploads for this student and subject
                $subjectUploads = $resultUploads->where('subject_id', $subjectId);
                
                $subjectTotal = 0;
                $subjectCount = 0;

                foreach ($subjectUploads as $upload) {
                    $cardItems = json_decode($upload->card_items, true);
                    if (isset($cardItems[$studentId])) {
                        $subjectTotal += $cardItems[$studentId]['total'] ?? 0;
                        $subjectCount++;
                    }
                }

                // Calculate average if multiple uploads exist for the same subject
                if ($subjectCount > 0) {
                    $subjectScore = $subjectTotal / $subjectCount;
                    $studentData['subjects'][$subjectId] = [
                        'subject_id' => $subjectId,
                        'subject_name' => $subject->name,
                        'score' => round($subjectScore, 2),
                        'grade' => $this->calculateGrade($subjectScore),
                    ];

                    $studentTotal += $subjectScore;
                    $subjectsWithScores++; // Only count subjects where student has scores
                } else {
                    // Student doesn't have scores for this subject
                    $studentData['subjects'][$subjectId] = [
                        'subject_id' => $subjectId,
                        'subject_name' => $subject->name,
                        'score' => 0,
                        'grade' => $this->calculateGrade(0),
                    ];
                }
            }

            $studentData['total'] = round($studentTotal, 2);
            
            $studentData['average'] = $subjectsWithScores > 0 ? round($studentTotal / $subjectsWithScores, 2) : 0;
            
            $broadsheetData[] = $studentData;
        }

        // Sort by total score in descending order
        usort($broadsheetData, function($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        // Assign positions (handle ties)
        $position = 1;
        $previousTotal = null;
        $samePositionCount = 0;

        foreach ($broadsheetData as $index => &$student) {
            $student['sno'] = $index + 1;
            
            if ($previousTotal === null || $student['total'] < $previousTotal) {
                $position = $index + 1;
                $samePositionCount = 0;
            } else {
                $samePositionCount++;
            }

            $student['position'] = $this->formatPosition($position);
            $previousTotal = $student['total'];
        }

        // Store the generated data
        $this->generated_data = [
            'students' => $broadsheetData,
            'subjects' => $subjects->pluck('name', 'id')->toArray(),
            'generated_at' => now()->toDateTimeString(),
            'total_students' => count($broadsheetData),
            'class_name' => $this->schoolClass->name ?? 'Unknown',
            'term' => $this->term,
        ];

        $this->save();

        return $this->generated_data;
    }

    private function calculateGrade($score)
    {
        // You can customize this based on your grading system
        if ($score >= 80) return 'A';
        if ($score >= 70) return 'B';
        if ($score >= 60) return 'C';
        if ($score >= 50) return 'D';
        if ($score >= 40) return 'E';
        return 'F';
    }


    private function formatPosition($position)
    {
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

    // booted methods = created_by = Auth::id()
    protected static function booted()
    {
        static::creating(function ($broadsheet) {
            if (auth()->check()) {
                $broadsheet->created_by = auth()->id();
            }
        });
    }

}