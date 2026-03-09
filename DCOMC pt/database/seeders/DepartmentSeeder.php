<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Program;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $education = Department::firstOrCreate(
            ['name' => config('departments.education', 'Education')],
            ['name' => 'Education']
        );
        $entrepreneurship = Department::firstOrCreate(
            ['name' => config('departments.entrepreneurship', 'Entrepreneurship')],
            ['name' => 'Entrepreneurship']
        );

        $entrepProgramName = config('departments.entrepreneurship_program_name', 'Bachelor of Science in Entrepreneurship');

        // Assign programs: Entrepreneurship → Entrepreneurship dept, rest → Education
        Program::query()->chunkById(100, function ($programs) use ($education, $entrepreneurship, $entrepProgramName) {
            foreach ($programs as $program) {
                $name = trim($program->program_name ?? '');
                $deptId = (strcasecmp($name, $entrepProgramName) === 0)
                    ? $entrepreneurship->id
                    : $education->id;
                if ((int) ($program->department_id ?? 0) !== (int) $deptId) {
                    $program->update(['department_id' => $deptId]);
                }
            }
        });

        // Rooms: default to Education (admin can assign Entrepreneurship rooms later)
        Room::whereNull('department_id')->update(['department_id' => $education->id]);

        // Faculty (users with faculty_type): default to Education
        User::whereNotNull('faculty_type')
            ->whereNull('department_id')
            ->update(['department_id' => $education->id]);

        // Deans: ensure they have a department (admin must set: one dean Education, one dean Entrepreneurship)
        // We do not auto-assign dean department here; leave null so admin sets it.
    }
}
