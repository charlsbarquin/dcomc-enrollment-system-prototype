<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * Set or reset a student account password (e.g. for test accounts).
 * Usage: php artisan students:set-password teststudent@dcomc.edu.ph NewPassword123
 */
class SetStudentPasswordCommand extends Command
{
    protected $signature = 'students:set-password
                            {identifier : Email or School ID of the student}
                            {password : New password to set}';

    protected $description = "Set or reset a student's password (by email or school_id)";

    public function handle(): int
    {
        $identifier = trim((string) $this->argument('identifier'));
        $password = (string) $this->argument('password');

        if (strlen($password) < 6) {
            $this->error('Password must be at least 6 characters.');

            return self::FAILURE;
        }

        $user = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->where(function ($q) use ($identifier) {
                $q->where('email', $identifier)
                    ->orWhere('school_id', $identifier);
            })
            ->first();

        if (! $user) {
            $this->error("No student found with email or school_id: {$identifier}");

            return self::FAILURE;
        }

        $user->password = Hash::make($password);
        $user->save();

        $this->info("Password updated for: {$user->email} ({$user->name})");
        $this->line('They can now log in at the Student Portal (/) with:');
        $this->table(
            ['Field', 'Value'],
            [
                ['Email or School ID', $user->email],
                ['Password', $password],
            ]
        );

        return self::SUCCESS;
    }
}
