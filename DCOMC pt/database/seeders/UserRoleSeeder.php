<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds one test account per user role for DCC enrollment system testing.
 * All accounts use the same password for easy team testing.
 */
class UserRoleSeeder extends Seeder
{
    public const TEST_PASSWORD = 'password123';

    /** @var array{email: string, name: string, role: string} */
    protected static array $accounts = [
        [
            'email' => 'test_admin@dcomc.edu.ph',
            'name' => 'Test Administrator',
            'role' => User::ROLE_ADMIN,
        ],
        [
            'email' => 'test_registrar@dcomc.edu.ph',
            'name' => 'Test Registrar',
            'role' => User::ROLE_REGISTRAR,
        ],
        [
            'email' => 'test_student@dcomc.edu.ph',
            'name' => 'Test Student',
            'role' => User::ROLE_STUDENT,
        ],
        [
            'email' => 'test_dean@dcomc.edu.ph',
            'name' => 'Test Dean',
            'role' => User::ROLE_DEAN,
        ],
        [
            'email' => 'test_staff@dcomc.edu.ph',
            'name' => 'Test Staff',
            'role' => User::ROLE_STAFF,
        ],
        [
            'email' => 'test_unifast@dcomc.edu.ph',
            'name' => 'Test UNIFAST',
            'role' => User::ROLE_UNIFAST,
        ],
    ];

    public function run(): void
    {
        $deanDepartmentId = $this->resolveDeanDepartmentId();

        foreach (self::$accounts as $account) {
            $attrs = [
                'name' => $account['name'],
                'password' => Hash::make(self::TEST_PASSWORD),
                'role' => $account['role'],
            ];
            if ($account['role'] === User::ROLE_DEAN && $deanDepartmentId !== null) {
                $attrs['department_id'] = $deanDepartmentId;
            }
            if ($account['role'] === User::ROLE_STUDENT) {
                $attrs['profile_completed'] = true;
                $attrs['school_id'] = 'DCOMC-TEST-001';
                $attrs['first_name'] = 'Test';
                $attrs['last_name'] = 'Student';
                $attrs['gender'] = 'Male';
                $attrs['year_level'] = '1st Year';
                $attrs['semester'] = 'First Semester';
                $attrs['student_status'] = 'Regular';
            }

            User::updateOrCreate(
                ['email' => $account['email']],
                $attrs
            );
        }

        $this->printCredentialsTable();
    }

    private function resolveDeanDepartmentId(): ?int
    {
        $dept = Department::orderBy('id')->first();
        if ($dept === null) {
            $this->command->warn('No department found. Run DepartmentSeeder first so the Test Dean has department access.');
            return null;
        }
        return (int) $dept->id;
    }

    private function printCredentialsTable(): void
    {
        $rows = [];
        foreach (self::$accounts as $a) {
            $rows[] = [$a['role'], $a['email'], self::TEST_PASSWORD];
        }

        $this->command->newLine();
        $this->command->info('=== Test accounts (one per role) – Password for all: ' . self::TEST_PASSWORD . ' ===');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            $rows
        );
        $this->command->info('Log in via the appropriate portal (Admin / DCOMC / Student) then use the URL for that role (e.g. /admin/dashboard, /registrar/dashboard, /student/dashboard).');
        $this->command->newLine();
    }
}
