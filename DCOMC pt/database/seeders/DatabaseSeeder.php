<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@dcomc.edu.ph'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]
        );

        User::firstOrCreate(
            ['email' => 'registrar@dcomc.edu.ph'],
            [
                'name' => 'Registrar Account',
                'password' => Hash::make('registrar123'),
                'role' => 'registrar',
            ]
        );

        User::firstOrCreate(
            ['email' => 'Student1@dcomc.edu.ph'],
            [
                'name' => 'Juan Dela Cruz',
                'password' => Hash::make('student123'),
                'role' => 'student',
                'profile_completed' => true,
                'school_id' => 'DCOMC-2025-0001',
                'first_name' => 'Juan',
                'middle_name' => 'Santos',
                'last_name' => 'Dela Cruz',
                'gender' => 'Male',
                'date_of_birth' => '2005-05-15',
                'place_of_birth' => 'Daraga, Albay',
                'civil_status' => 'Single',
                'citizenship' => 'Filipino',
                'phone' => '09123456789',
                'college' => 'College of Education',
                'course' => 'BEED',
                'year_level' => '1st Year',
                'semester' => 'Second Semester',
                'units_enrolled' => 0,
                'student_status' => 'Regular',
                'father_name' => 'Pedro Dela Cruz',
                'father_occupation' => 'Farmer',
                'mother_name' => 'Maria Santos',
                'mother_occupation' => 'Teacher',
                'monthly_income' => '10,000-20,000',
                'num_family_members' => 5,
                'dswd_household_no' => 'DSWD-2025-001',
                'num_siblings' => 3,
                'purok_zone' => 'Purok 1',
                'house_number' => '123',
                'street' => 'Main Street',
                'barangay' => 'Anislag',
                'municipality' => 'Daraga',
                'province' => 'Albay',
                'zip_code' => '4501',
            ]
        );

        $this->call(AcademicReferenceSeeder::class);
        $this->call(ProgramsSeeder::class);
        $this->call(DepartmentSeeder::class);
        $this->call(DeanAccountsSeeder::class);
        $this->call(UserRoleSeeder::class);
        $this->call(FeeCategorySeeder::class);
        $this->call(DcomcFeesSeeder::class);
        $this->call(EnrollmentFormTemplateSeeder::class);
    }
}