<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Major extends Model
{
    protected $fillable = [
        'program',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function majorsByProgram(): array
    {
        return static::query()
            ->where('is_active', true)
            ->where('program', '!=', 'Bachelor of Elementary Education')
            ->orderBy('program')
            ->orderBy('name')
            ->get(['program', 'name'])
            ->groupBy('program')
            ->map(fn ($rows) => $rows->pluck('name')->values()->all())
            ->toArray();
    }
}

