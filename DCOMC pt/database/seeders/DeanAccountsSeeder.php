<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DeanAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $education = Department::where('name', config('departments.education', 'Education'))->first();
        $entrepreneurship = Department::where('name', config('departments.entrepreneurship', 'Entrepreneurship'))->first();

        if (!$education || !$entrepreneurship) {
            $this->command->warn('Departments not found. Run DepartmentSeeder first.');
            return;
        }

        User::firstOrCreate(
            ['email' => 'dean1@dcomc.edu.ph'],
            [
                'name' => 'Dean Education',
                'password' => Hash::make('dean123'),
                'role' => 'dean',
                'department_id' => $education->id,
            ]
        );

        User::firstOrCreate(
            ['email' => 'dean2@dcomc.edu.ph'],
            [
                'name' => 'Dean Entrepreneurship',
                'password' => Hash::make('dean123'),
                'role' => 'dean',
                'department_id' => $entrepreneurship->id,
            ]
        );
    }
}
