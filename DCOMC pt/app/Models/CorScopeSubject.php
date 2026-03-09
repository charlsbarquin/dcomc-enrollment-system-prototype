<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorScopeSubject extends Model
{
    protected $fillable = ['cor_scope_id', 'subject_id'];

    public function corScope(): BelongsTo
    {
        return $this->belongsTo(CorScope::class, 'cor_scope_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
