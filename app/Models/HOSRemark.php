<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HOSRemark extends Model
{
    protected $table = "hos_remarks";
    protected $fillable = [
        'remark',
        'student_id',
        'hos_id',
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
     * Get the HOS that owns the remark.
     */
    public function hos(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hos_id');
    }

    /**
     * Get the result root that owns the remark.
     */
    public function resultRoot(): BelongsTo
    {
        return $this->belongsTo(ResultRoot::class);
    }
}