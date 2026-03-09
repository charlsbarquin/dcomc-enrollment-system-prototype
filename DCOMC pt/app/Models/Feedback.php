<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    protected $table = 'feedback';

    protected $fillable = [
        'user_id',
        'role',
        'subject',
        'message',
        'priority',
    ];

    protected $casts = [
        'priority' => 'integer',
    ];

    public const PRIORITY_MIN = 1;
    public const PRIORITY_MAX = 5;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function priorityLabel(int $priority): string
    {
        $labels = [
            1 => 'Least important',
            2 => 'Low',
            3 => 'Medium',
            4 => 'High',
            5 => 'Very important',
        ];
        return $labels[$priority] ?? 'Medium';
    }
}
