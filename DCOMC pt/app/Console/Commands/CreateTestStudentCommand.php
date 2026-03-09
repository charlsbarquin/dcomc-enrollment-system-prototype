<?php

namespace App\Console\Commands;

use App\Models\AcademicSemester;
use App\Models\AcademicYearLevel;
use App\Models\SchoolYear;
use App\Models\User;
use App\Services\BlockAssignmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * Creates a single test student account for testing.
 * Usage: php artisan students:create-test
 * Credentials are printed after creation (default password: Student123).
 */
class CreateTestStudentCommand extends Command
{
    protected $signature = 'students:create-test
                            {--email= : Use this email instead of auto-generated}
                            {--password= : Password (default: Student123)}';

    protected $description = 'Create one test student account for testing (prints login credentials)';

    public function handle(): int
    {
        $yearLevels = AcademicYearLevel::where('is_active', true)->orderBy('name')->pluck('name');
        $semesters = AcademicSemester::where('is_active', true)->orderBy('name')->pluck('name');
        $schoolYears = SchoolYear::orderByDesc('start_year')->pluck('label');

        $yearLevel = $yearLevels->first();
        $semester = $semesters->first();
        $schoolYear = $schoolYears->first();

        if (! $yearLevel || ! $semester) {
            $this->error('No active year level or semester found. Run AcademicReferenceSeeder or create them in Settings.');

            return self::FAILURE;
        }

        $firstName = 'Test';
        $lastName = 'Student';
        $password = $this->option('password') ?: config('app.manual_registration_default_password', 'Student123');
        $emailOption = $this->option('email');

        if ($emailOption) {
            if (User::where('email', $emailOption)->exists()) {
                $this->error("A user with email {$emailOption} already exists.");

                return self::FAILURE;
            }
            $email = $emailOption;
            $schoolId = $email;
        } else {
            $schoolId = $this->generateNextSchoolId();
            $email = $this->generateStudentEmail($schoolId, $firstName, $lastName);
        }

        $user = User::create([
            'name' => $lastName . ', ' . $firstName,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => User::ROLE_STUDENT,
            'school_id' => $schoolId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'middle_name' => null,
            'gender' => 'Male',
            'civil_status' => 'Single',
            'citizenship' => 'Filipino',
            'course' => 'Bachelor of Elementary Education',
            'major' => null,
            'year_level' => $yearLevel,
            'semester' => $semester,
            'school_year' => $schoolYear,
            'shift' => 'day',
            'student_type' => 'Regular',
            'student_status' => 'Enrolled',
            'units_enrolled' => 0,
            'profile_completed' => true,
        ]);

        $blockService = app(BlockAssignmentService::class);
        $blockService->assignStudentToBlock($user, $yearLevel, $semester, null);

        $this->info('Test student created successfully.');
        $this->table(
            ['Field', 'Value'],
            [
                ['Email / School ID', $user->email],
                ['Password', $password],
                ['Name', $user->name],
                ['Year Level', $user->year_level],
                ['Semester', $user->semester],
                ['Block', $user->block_id && $user->block ? $user->block->code : ($user->block_id ?: '—')],
            ]
        );
        $this->line('You can log in at: ' . url('/login'));

        return self::SUCCESS;
    }

    private function generateNextSchoolId(): string
    {
        $year = (string) now()->year;
        $prefix = 'DCOMC-' . $year . '-';
        $max = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->where('school_id', 'like', $prefix . '%')
            ->get(['school_id'])
            ->max(fn ($u) => (int) preg_replace('/^DCOMC-\d+-/', '', $u->school_id ?? '0'));
        $next = ($max ?? 0) + 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function generateStudentEmail(string $schoolId, string $firstName, string $lastName): string
    {
        $base = str_replace([' ', '-'], '', strtolower($lastName . '.' . $firstName));
        $base = preg_replace('/[^a-z0-9]/', '', $base) ?: 'student';
        $candidate = $base . '@dcomc.edu.ph';
        if (! User::where('email', $candidate)->exists()) {
            return $candidate;
        }
        $suffix = preg_replace('/^DCOMC-\d+-/', '', $schoolId);

        return $base . $suffix . '@dcomc.edu.ph';
    }
}
