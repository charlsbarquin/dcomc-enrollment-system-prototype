<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Block extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'program_id',
        'program',
        'major',
        'year_level',
        'section_name',
        'semester',
        'shift',
        'school_year_label',
        'gender_group',
        'capacity',
        'max_capacity',
        'max_students',
        'current_size',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function students()
    {
        return $this->hasMany(User::class);
    }

    /** Irregular students assigned to this block (many-to-many via pivot). */
    public function studentBlockAssignments()
    {
        return $this->hasMany(StudentBlockAssignment::class);
    }

    public function schedules()
    {
        return $this->hasMany(ClassSchedule::class);
    }

    public function program(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function transferLogsTo(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BlockTransferLog::class, 'to_block_id');
    }

    public function transferLogsFrom(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BlockTransferLog::class, 'from_block_id');
    }

    /** Effective capacity cap (max_capacity or capacity or 50). */
    public function effectiveMaxCapacity(): int
    {
        return (int) ($this->attributes['max_capacity'] ?? $this->capacity ?? $this->max_students ?? 50);
    }

    /**
     * Count of students in this block for the given school year (label).
     * Includes users with block_id = this block and users linked via student_block_assignments.
     */
    public function currentCountForSchoolYear(?string $schoolYear): int
    {
        if ($schoolYear === null || $schoolYear === '') {
            return (int) $this->current_size;
        }
        $base = \App\Models\User::query()
            ->where('role', \App\Models\User::ROLE_STUDENT)
            ->where('school_year', $schoolYear)
            ->where(function ($q) {
                $q->where('block_id', $this->id)
                    ->orWhereHas('blockAssignments', fn ($a) => $a->where('block_id', $this->id));
            });
        return $base->count();
    }

    /**
     * For each block id, count students in that school year (block_id or student_block_assignments).
     * @return array<int, int> block_id => count
     */
    public static function currentCountsByBlockForSchoolYear(array $blockIds, ?string $schoolYear): array
    {
        if (empty($blockIds) || $schoolYear === null || $schoolYear === '') {
            return array_fill_keys(array_map('intval', $blockIds), 0);
        }
        $blockIds = array_map('intval', $blockIds);
        $direct = \App\Models\User::query()
            ->where('role', \App\Models\User::ROLE_STUDENT)
            ->where('school_year', $schoolYear)
            ->whereIn('block_id', $blockIds)
            ->selectRaw('block_id, COUNT(*) as c')
            ->groupBy('block_id')
            ->pluck('c', 'block_id')
            ->map(fn ($c) => (int) $c)
            ->all();
        $viaAssignments = \App\Models\StudentBlockAssignment::query()
            ->whereIn('block_id', $blockIds)
            ->whereHas('user', fn ($u) => $u->where('role', \App\Models\User::ROLE_STUDENT)->where('school_year', $schoolYear))
            ->selectRaw('block_id, COUNT(*) as c')
            ->groupBy('block_id')
            ->pluck('c', 'block_id')
            ->map(fn ($c) => (int) $c)
            ->all();
        $result = [];
        foreach ($blockIds as $id) {
            $result[$id] = ($direct[$id] ?? 0) + ($viaAssignments[$id] ?? 0);
        }
        return $result;
    }
}

