<?php

namespace App\Services;

use App\Models\Block;
use App\Models\BlockTransferLog;
use App\Models\Program;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BlockManagementService
{
    /**
     * Validate and execute manual transfer of one or more students to another block.
     * Registrar/admin only. Enforces: same program, same year_level, capacity, one block per student.
     *
     * @param  array<int>  $studentIds
     * @throws InvalidArgumentException
     */
    public function transferStudents(
        int $fromBlockId,
        int $toBlockId,
        array $studentIds,
        ?int $initiatedBy = null,
        ?string $reason = null
    ): array {
        $studentIds = array_unique(array_filter($studentIds));
        if (empty($studentIds)) {
            throw new InvalidArgumentException('No students selected.');
        }

        $fromBlock = Block::findOrFail($fromBlockId);
        $toBlock = Block::findOrFail($toBlockId);

        $this->validateTransfer($fromBlock, $toBlock, $studentIds);

        $fromProgramId = $fromBlock->program_id ?? $this->resolveProgramId($fromBlock->program);
        $toProgramId = $toBlock->program_id ?? $this->resolveProgramId($toBlock->program);
        $isCrossProgram = false;
        if ($fromProgramId !== null && $toProgramId !== null && (int) $fromProgramId !== (int) $toProgramId) {
            $isCrossProgram = true;
        } elseif (($fromProgramId === null) !== ($toProgramId === null)) {
            $isCrossProgram = true;
        } elseif ($fromProgramId === null && $fromBlock->program != $toBlock->program) {
            $isCrossProgram = true;
        }

        $students = User::query()
            ->whereIn('id', $studentIds)
            ->where('block_id', $fromBlockId)
            ->where('role', User::ROLE_STUDENT)
            ->get();

        if ($students->count() !== count($studentIds)) {
            throw new InvalidArgumentException('One or more students do not belong to the source block or are not students.');
        }

        $cap = $toBlock->effectiveMaxCapacity();
        $current = (int) $toBlock->current_size;
        if ($current + $students->count() > $cap) {
            throw new InvalidArgumentException("Target block cannot exceed max capacity ({$cap}). Current: {$current}, moving: {$students->count()}.");
        }

        return DB::transaction(function () use ($fromBlock, $toBlock, $students, $initiatedBy, $reason, $isCrossProgram) {
            foreach ($students as $student) {
                $updates = [
                    'block_id' => $toBlock->id,
                    'year_level' => $toBlock->year_level ?? $student->year_level,
                    'semester' => $toBlock->semester ?? $student->semester,
                    'course' => $toBlock->program ?? $student->course,
                ];
                if ($isCrossProgram) {
                    $updates['student_type'] = 'Irregular';
                    $updates['status_color'] = 'yellow';
                    $updates['previous_program'] = $fromBlock->program ?? $student->course ?? null;
                }
                $student->update($updates);

                BlockTransferLog::create([
                    'student_id' => $student->id,
                    'from_block_id' => $fromBlock->id,
                    'to_block_id' => $toBlock->id,
                    'transfer_type' => BlockTransferLog::TYPE_MANUAL,
                    'initiated_by' => $initiatedBy,
                    'reason' => $reason,
                ]);
            }

            $fromBlock->decrement('current_size', $students->count());
            $toBlock->increment('current_size', $students->count());

            return ['moved' => $students->count(), 'student_ids' => $students->pluck('id')->all()];
        });
    }

    /**
     * @param  array<int>  $studentIds
     * @throws InvalidArgumentException
     */
    protected function validateTransfer(Block $fromBlock, Block $toBlock, array $studentIds): void
    {
        if ($fromBlock->id === $toBlock->id) {
            throw new InvalidArgumentException('Source and target block cannot be the same.');
        }

        if (! $fromBlock->is_active || ! $toBlock->is_active) {
            throw new InvalidArgumentException('Blocks must be active.');
        }

        if ($fromBlock->year_level !== $toBlock->year_level || $fromBlock->semester !== $toBlock->semester) {
            throw new InvalidArgumentException('Cannot transfer across different year level or semester.');
        }
    }

    /**
     * Resolve program name or code to program_id (for blocks that store program as string).
     */
    protected function resolveProgramId(?string $program): ?int
    {
        if ($program === null || trim($program) === '') {
            return null;
        }
        $p = Program::query()
            ->where('program_name', trim($program))
            ->orWhere('code', trim($program))
            ->first(['id']);

        return $p ? (int) $p->id : null;
    }

    public function logTransfer(
        int $studentId,
        ?int $fromBlockId,
        int $toBlockId,
        string $type,
        ?int $initiatedBy = null,
        ?string $reason = null,
        ?array $metadata = null
    ): BlockTransferLog {
        return BlockTransferLog::create([
            'student_id' => $studentId,
            'from_block_id' => $fromBlockId,
            'to_block_id' => $toBlockId,
            'transfer_type' => $type,
            'initiated_by' => $initiatedBy,
            'reason' => $reason,
            'metadata' => $metadata,
        ]);
    }
}
