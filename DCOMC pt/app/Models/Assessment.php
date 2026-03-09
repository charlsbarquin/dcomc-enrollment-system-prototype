<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'school_year',
        'semester',
        'tuition_fee',
        'misc_fee',
        'other_fee',
        'total_assessed',
        'income_classification',
        'assessment_status',
        'unifast_eligible',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'unifast_eligible' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}

