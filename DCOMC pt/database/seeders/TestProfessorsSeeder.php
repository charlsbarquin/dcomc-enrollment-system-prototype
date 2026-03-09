<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Test professors for Education and Entrepreneurship.
 * Education: 2 permanent, 2 COS, 2 part-time.
 * Entrepreneurship: 2 permanent, 2 COS, 2 part-time.
 */
class TestProfessorsSeeder extends Seeder
{
    public function run(): void
    {
        $entries = [
            // Education – 2 permanent, 2 COS, 2 part-time
            ['name' => 'Educ Permanent One', 'email' => 'educ.permanent1@test.dcomc.edu.ph', 'department_scope' => 'education', 'faculty_type' => 'permanent', 'gender' => 'Male'],
            ['name' => 'Educ Permanent Two', 'email' => 'educ.permanent2@test.dcomc.edu.ph', 'department_scope' => 'education', 'faculty_type' => 'permanent', 'gender' => 'Female'],
            ['name' => 'Educ COS One', 'email' => 'educ.cos1@test.dcomc.edu.ph', 'department_scope' => 'education', 'faculty_type' => 'cos', 'gender' => 'Male'],
            ['name' => 'Educ COS Two', 'email' => 'educ.cos2@test.dcomc.edu.ph', 'department_scope' => 'education', 'faculty_type' => 'cos', 'gender' => 'Female'],
            ['name' => 'Educ Part-time One', 'email' => 'educ.pt1@test.dcomc.edu.ph', 'department_scope' => 'education', 'faculty_type' => 'part-time', 'gender' => 'Male'],
            ['name' => 'Educ Part-time Two', 'email' => 'educ.pt2@test.dcomc.edu.ph', 'department_scope' => 'education', 'faculty_type' => 'part-time', 'gender' => 'Female'],
            // Entrepreneurship – 2 permanent, 2 COS, 2 part-time
            ['name' => 'Entrep Permanent One', 'email' => 'entrep.permanent1@test.dcomc.edu.ph', 'department_scope' => 'entrepreneurship', 'faculty_type' => 'permanent', 'gender' => 'Male'],
            ['name' => 'Entrep Permanent Two', 'email' => 'entrep.permanent2@test.dcomc.edu.ph', 'department_scope' => 'entrepreneurship', 'faculty_type' => 'permanent', 'gender' => 'Female'],
            ['name' => 'Entrep COS One', 'email' => 'entrep.cos1@test.dcomc.edu.ph', 'department_scope' => 'entrepreneurship', 'faculty_type' => 'cos', 'gender' => 'Male'],
            ['name' => 'Entrep COS Two', 'email' => 'entrep.cos2@test.dcomc.edu.ph', 'department_scope' => 'entrepreneurship', 'faculty_type' => 'cos', 'gender' => 'Female'],
            ['name' => 'Entrep Part-time One', 'email' => 'entrep.pt1@test.dcomc.edu.ph', 'department_scope' => 'entrepreneurship', 'faculty_type' => 'part-time', 'gender' => 'Male'],
            ['name' => 'Entrep Part-time Two', 'email' => 'entrep.pt2@test.dcomc.edu.ph', 'department_scope' => 'entrepreneurship', 'faculty_type' => 'part-time', 'gender' => 'Female'],
        ];

        foreach ($entries as $e) {
            User::firstOrCreate(
                ['email' => $e['email']],
                [
                    'name' => $e['name'],
                    'password' => Hash::make(Str::random(32)),
                    'role' => 'staff',
                    'gender' => $e['gender'],
                    'faculty_type' => $e['faculty_type'],
                    'department_scope' => $e['department_scope'],
                    'max_units' => match ($e['faculty_type']) { 'permanent' => 24, 'cos' => 18, default => 12 },
                ]
            );
        }

        $this->command->info('Test professors created: 6 Education (2 permanent, 2 COS, 2 part-time), 6 Entrepreneurship (2 permanent, 2 COS, 2 part-time).');
    }
}
