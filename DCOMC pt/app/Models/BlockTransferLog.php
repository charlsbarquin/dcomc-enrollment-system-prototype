<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockTransferLog extends Model
{
    public const TYPE_MANUAL = 'manual';

    public const TYPE_AUTO_REBALANCE = 'auto_rebalance';

    public const TYPE_PROMOTION = 'promotion';

    public const TYPE_SHIFT_OUT = 'shift_out';

    public const TYPE_ADMIN_CORRECTION = 'admin_correction';

    protected $fillable = [
        'student_id',
        'from_block_id',
        'to_block_id',
        'transfer_type',
        'initiated_by',
        'reason',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function fromBlock(): BelongsTo
    {
        return $this->belongsTo(Block::class, 'from_block_id');
    }

    public function toBlock(): BelongsTo
    {
        return $this->belongsTo(Block::class, 'to_block_id');
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }
}
