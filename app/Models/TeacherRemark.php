<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherRemark extends Model
{
    protected $fillable = [
        'remark',
        'student_id',
        'teacher_id',
        'result_root_id'
    ];

    /**
     * Get the student that owns the remark.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the teacher that owns the remark.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get the result root that owns the remark.
     */
    public function resultRoot(): BelongsTo
    {
        return $this->belongsTo(ResultRoot::class);
    }
}