<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorScopeFee extends Model
{
    protected $fillable = ['cor_scope_id', 'fee_id'];

    public function corScope(): BelongsTo
    {
        return $this->belongsTo(CorScope::class, 'cor_scope_id');
    }

    public function fee(): BelongsTo
    {
        return $this->belongsTo(Fee::class, 'fee_id');
    }
}
