<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockChangeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'current_block_id',
        'requested_block_id',
        'replacement_student_id',
        'requested_shift',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function currentBlock()
    {
        return $this->belongsTo(Block::class, 'current_block_id');
    }

    public function requestedBlock()
    {
        return $this->belongsTo(Block::class, 'requested_block_id');
    }

    public function replacementStudent()
    {
        return $this->belongsTo(User::class, 'replacement_student_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}

