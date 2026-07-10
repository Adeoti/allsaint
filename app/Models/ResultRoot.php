<?php

namespace App\Models;

use App\Models\GradingSystem;
use Illuminate\Database\Eloquent\Model;

class ResultRoot extends Model
{
    //

    protected $fillable = [
        'name',
        'description',
        'branch_ids',
        'exam_score_columns',
        'grading_system_id',
        'next_term',
        'section_address',
        'teacher_id',
        'term',
        'academic_session',
        'total_school_days',
    ];

    protected $casts = [
        'branch_ids' => 'array',
        'exam_score_columns' => 'array',
    ];

    /**
     * Fixed list of terms used across the app (Filament form, cumulative grouping, etc).
     * Order matters: this is the chronological order used to sort cumulative columns.
     */
    public static function termOptions(): array
    {
        return [
            '1st Term' => '1st Term',
            '2nd Term' => '2nd Term',
            '3rd Term' => '3rd Term',
        ];
    }

    /**
     * Fixed list of academic sessions, e.g. "2025/2026" up to "2049/2050".
     */
    public static function academicSessionOptions(): array
    {
        $sessions = [];
        for ($year = 2025; $year <= 2049; $year++) {
            $label = $year . '/' . ($year + 1);
            $sessions[$label] = $label;
        }
        return $sessions;
    }

    public function resultUploads()
    {
        return $this->hasMany(ResultUpload::class, 'result_root_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function gradingSystem()
    {
        return $this->belongsTo(GradingSystem::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'result_root_id');
    }
}