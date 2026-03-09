<?php

namespace Database\Seeders;

use App\Models\AcademicSemester;
use App\Models\AcademicYearLevel;
use Illuminate\Database\Seeder;

class AcademicReferenceSeeder extends Seeder
{
    public function run(): void
    {
        foreach (AcademicYearLevel::CANONICAL as $name) {
            AcademicYearLevel::firstOrCreate(
                ['name' => $name],
                ['is_active' => true]
            );
        }

        foreach (AcademicSemester::CANONICAL as $name) {
            AcademicSemester::firstOrCreate(
                ['name' => $name],
                ['is_active' => true]
            );
        }
    }
}
