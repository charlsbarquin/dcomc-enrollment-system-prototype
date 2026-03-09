<?php

namespace Database\Seeders;

use App\Models\AcademicSemester;
use App\Models\AcademicYearLevel;
use App\Models\Program;
use App\Models\Subject;
use Illuminate\Database\Seeder;

/**
 * Seeds subjects for BSEd Major in Social Studies from the 4-year curriculum.
 * Program → Year → First/Second Semester structure.
 */
class BsedSocialStudiesSubjectsSeeder extends Seeder
{
    protected string $programName = 'Bachelor of Secondary Education Major in Social Studies';

    public function run(): void
    {
        $program = Program::firstOrCreate(
            ['program_name' => $this->programName],
            []
        );
        $yearLevels = AcademicYearLevel::whereIn('name', AcademicYearLevel::CANONICAL)->pluck('id', 'name');
        $firstSem = AcademicSemester::where('name', 'First Semester')->value('id');
        $secondSem = AcademicSemester::where('name', 'Second Semester')->value('id');
        if (! $program || ! $firstSem || ! $secondSem || $yearLevels->count() < 4) {
            $this->command->warn('Ensure AcademicReferenceSeeder and programs migration have run.');
            return;
        }

        $subjects = $this->getCurriculum();
        foreach ($subjects as $row) {
            $yearId = $yearLevels->get($row['year']);
            if (! $yearId) {
                continue;
            }
            Subject::updateOrCreate(
                [
                    'program_id' => $program->id,
                    'academic_year_level_id' => $yearId,
                    'semester' => $row['semester'],
                    'code' => $row['code'],
                ],
                [
                    'title' => $row['title'],
                    'units' => $row['units'],
                    'prerequisites' => $row['prerequisites'] ?? null,
                    'major' => null,
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('BSEd Social Studies subjects seeded by year and semester.');
    }

    /** Curriculum data: code, title, units, year (1st Year..4th Year), semester (First/Second Semester). */
    private function getCurriculum(): array
    {
        return [
            // FIRST YEAR - First Semester
            ['code' => 'Prof Educ 1', 'title' => 'The Child and Adolescent Learners and Learning Principles', 'units' => 3, 'year' => '1st Year', 'semester' => 'First Semester'],
            ['code' => 'MC SocStud 1', 'title' => 'Geography 1 (Human Geography)', 'units' => 3, 'year' => '1st Year', 'semester' => 'First Semester'],
            ['code' => 'GE 1', 'title' => 'Understanding the Self', 'units' => 3, 'year' => '1st Year', 'semester' => 'First Semester'],
            ['code' => 'GE 2', 'title' => 'Readings in the Philippine History', 'units' => 3, 'year' => '1st Year', 'semester' => 'First Semester'],
            ['code' => 'GE 3', 'title' => 'The Contemporary World', 'units' => 3, 'year' => '1st Year', 'semester' => 'First Semester'],
            ['code' => 'GE 4', 'title' => 'Mathematics in the Modern World', 'units' => 3, 'year' => '1st Year', 'semester' => 'First Semester'],
            ['code' => 'GE 5', 'title' => 'Purposive Communication', 'units' => 3, 'year' => '1st Year', 'semester' => 'First Semester'],
            ['code' => 'NSTP 1', 'title' => 'ROTC/LTS/CWTS', 'units' => 3, 'year' => '1st Year', 'semester' => 'First Semester'],
            ['code' => 'PATHFIT 1', 'title' => 'Movement Competency Training', 'units' => 2, 'year' => '1st Year', 'semester' => 'First Semester'],

            // FIRST YEAR - Second Semester
            ['code' => 'Prof Educ 2', 'title' => 'Foundation of Special and Inclusive Education', 'units' => 3, 'year' => '1st Year', 'semester' => 'Second Semester'],
            ['code' => 'MC SocStud 2', 'title' => 'Geography 2 (Physical Geography)', 'units' => 3, 'year' => '1st Year', 'semester' => 'Second Semester', 'prerequisites' => 'MC SocStud 1'],
            ['code' => 'GE 6', 'title' => 'Art Appreciation', 'units' => 3, 'year' => '1st Year', 'semester' => 'Second Semester'],
            ['code' => 'GE 7', 'title' => 'Science, Technology and Society', 'units' => 3, 'year' => '1st Year', 'semester' => 'Second Semester'],
            ['code' => 'GE 8', 'title' => 'Ethics', 'units' => 3, 'year' => '1st Year', 'semester' => 'Second Semester'],
            ['code' => 'GE 9', 'title' => 'The Life and Works of Rizal', 'units' => 3, 'year' => '1st Year', 'semester' => 'Second Semester'],
            ['code' => 'GE ELEC 1', 'title' => 'The Entrepreneurial Mind', 'units' => 3, 'year' => '1st Year', 'semester' => 'Second Semester'],
            ['code' => 'NSTP 2', 'title' => 'ROTC/LTS/CWTS', 'units' => 3, 'year' => '1st Year', 'semester' => 'Second Semester', 'prerequisites' => 'NSTP 1'],
            ['code' => 'PATHFIT 2', 'title' => 'Exercise-Based Fitness Activities', 'units' => 2, 'year' => '1st Year', 'semester' => 'Second Semester', 'prerequisites' => 'PATHFIT 1'],

            // SECOND YEAR - First Semester
            ['code' => 'Prof Educ 3', 'title' => 'The Teaching Profession', 'units' => 3, 'year' => '2nd Year', 'semester' => 'First Semester'],
            ['code' => 'GE ELEC 2', 'title' => 'Philippine Popular Culture', 'units' => 3, 'year' => '2nd Year', 'semester' => 'First Semester'],
            ['code' => 'GE ELEC 3', 'title' => 'Living in the IT Era', 'units' => 3, 'year' => '2nd Year', 'semester' => 'First Semester'],
            ['code' => 'MC SocStud 3', 'title' => 'Geography 3 (Urban Geography)', 'units' => 3, 'year' => '2nd Year', 'semester' => 'First Semester'],
            ['code' => 'MC SocStud 4', 'title' => 'Foundation of Social Studies', 'units' => 3, 'year' => '2nd Year', 'semester' => 'First Semester'],
            ['code' => 'MC SocStud 5', 'title' => 'Asian Studies', 'units' => 3, 'year' => '2nd Year', 'semester' => 'First Semester'],
            ['code' => 'MC SocStud 6', 'title' => 'Socio-cultural Anthropology', 'units' => 3, 'year' => '2nd Year', 'semester' => 'First Semester'],
            ['code' => 'MC SocStud 7', 'title' => 'Microeconomics', 'units' => 3, 'year' => '2nd Year', 'semester' => 'First Semester'],
            ['code' => 'PATHFIT 3', 'title' => 'Sports', 'units' => 2, 'year' => '2nd Year', 'semester' => 'First Semester', 'prerequisites' => 'PATHFIT 2'],

            // SECOND YEAR - Second Semester
            ['code' => 'Prof Educ 4', 'title' => 'The Teacher and the School Curriculum', 'units' => 3, 'year' => '2nd Year', 'semester' => 'Second Semester'],
            ['code' => 'Prof Educ 5', 'title' => 'The Teacher and the Community, School Culture, and Organizational Leadership', 'units' => 3, 'year' => '2nd Year', 'semester' => 'Second Semester'],
            ['code' => 'MC SocStud 8', 'title' => 'Macroeconomics', 'units' => 3, 'year' => '2nd Year', 'semester' => 'Second Semester', 'prerequisites' => 'MC SocStud 7'],
            ['code' => 'MC SocStud 9', 'title' => 'Law-related Studies', 'units' => 3, 'year' => '2nd Year', 'semester' => 'Second Semester'],
            ['code' => 'MC SocStud 10', 'title' => 'Comparative Government and Politics', 'units' => 3, 'year' => '2nd Year', 'semester' => 'Second Semester'],
            ['code' => 'MC SocStud 11', 'title' => 'Comparative Economic Planning', 'units' => 3, 'year' => '2nd Year', 'semester' => 'Second Semester'],
            ['code' => 'MC SocStud 12', 'title' => 'Trends and Issues in Social Studies', 'units' => 3, 'year' => '2nd Year', 'semester' => 'Second Semester'],
            ['code' => 'MC SocStud 13', 'title' => 'World History 1', 'units' => 3, 'year' => '2nd Year', 'semester' => 'Second Semester'],
            ['code' => 'PATHFIT 4', 'title' => 'Dance', 'units' => 2, 'year' => '2nd Year', 'semester' => 'Second Semester', 'prerequisites' => 'PATHFIT 3'],

            // THIRD YEAR - First Semester
            ['code' => 'Prof Educ 6', 'title' => 'Facilitating Learner-Centered Teaching', 'units' => 3, 'year' => '3rd Year', 'semester' => 'First Semester'],
            ['code' => 'Prof Educ 7', 'title' => 'Technology for Teaching and Learning 1', 'units' => 3, 'year' => '3rd Year', 'semester' => 'First Semester'],
            ['code' => 'Prof Educ 8', 'title' => 'Assessment in Learning 1', 'units' => 3, 'year' => '3rd Year', 'semester' => 'First Semester'],
            ['code' => 'MC SocStud 14', 'title' => 'World History 2', 'units' => 3, 'year' => '3rd Year', 'semester' => 'First Semester'],
            ['code' => 'MC SocStud 15', 'title' => 'Places and Landscapes in a Changing World', 'units' => 3, 'year' => '3rd Year', 'semester' => 'First Semester'],
            ['code' => 'MC SocStud 16', 'title' => 'Assessment and Evaluation in Social Sciences', 'units' => 3, 'year' => '3rd Year', 'semester' => 'First Semester'],
            ['code' => 'MC SocStud 17', 'title' => 'Research in Social Studies', 'units' => 3, 'year' => '3rd Year', 'semester' => 'First Semester'],
            ['code' => 'MC SocStud 18', 'title' => 'Human Resource Management', 'units' => 3, 'year' => '3rd Year', 'semester' => 'First Semester'],

            // THIRD YEAR - Second Semester
            ['code' => 'Prof Educ 9', 'title' => 'Assessment in Learning 2', 'units' => 3, 'year' => '3rd Year', 'semester' => 'Second Semester'],
            ['code' => 'Prof Educ 10', 'title' => 'Building and Enhancing New Literacies Across the Curriculum', 'units' => 3, 'year' => '3rd Year', 'semester' => 'Second Semester'],
            ['code' => 'MC SocStud 24', 'title' => 'Research in Social Studies 2', 'units' => 3, 'year' => '3rd Year', 'semester' => 'Second Semester', 'prerequisites' => 'MC SocStud 17'],
            ['code' => 'MC SocStud 19', 'title' => 'Integrative Methods in Teaching Social Science Discipline in Basic Education', 'units' => 3, 'year' => '3rd Year', 'semester' => 'Second Semester'],
            ['code' => 'MC SocStud 20', 'title' => 'Production of Social Studies Instructional Materials', 'units' => 3, 'year' => '3rd Year', 'semester' => 'Second Semester'],
            ['code' => 'MC SocStud 21', 'title' => 'Technology for Teaching and Learning 2', 'units' => 3, 'year' => '3rd Year', 'semester' => 'Second Semester', 'prerequisites' => 'Prof Educ 7'],
            ['code' => 'MC SocStud 22', 'title' => 'Teaching Approaches in Secondary Social Studies', 'units' => 3, 'year' => '3rd Year', 'semester' => 'Second Semester'],
            ['code' => 'MC SocStud 23', 'title' => 'Property and Management for Educators', 'units' => 3, 'year' => '3rd Year', 'semester' => 'Second Semester'],

            // FOURTH YEAR - First Semester
            ['code' => 'Prof Educ 11', 'title' => 'Field Study 1: Observations of Teaching - Learning in Actual School Environment', 'units' => 3, 'year' => '4th Year', 'semester' => 'First Semester', 'prerequisites' => 'All GE, All Prof & Educ, & All Major Subjects'],
            ['code' => 'Prof Educ 12', 'title' => 'Field Study 2: Participation and Teaching Assistantship', 'units' => 3, 'year' => '4th Year', 'semester' => 'First Semester', 'prerequisites' => 'All GE, All Prof & Educ, & All Major Subjects'],
            ['code' => 'Review 1', 'title' => 'Enrichment Class (Major Subjects and General Subjects)', 'units' => 6, 'year' => '4th Year', 'semester' => 'First Semester', 'prerequisites' => 'All GE, All Prof & Educ, & All Major Subjects'],

            // FOURTH YEAR - Second Semester
            ['code' => 'Prof Educ 13', 'title' => 'Field Study 3: The Teaching Internship', 'units' => 6, 'year' => '4th Year', 'semester' => 'Second Semester', 'prerequisites' => 'All GE, All Prof & Educ, & All Major Subjects'],
            ['code' => 'Review 2', 'title' => 'Enrichment Class (Professional Education Subjects)', 'units' => 6, 'year' => '4th Year', 'semester' => 'Second Semester', 'prerequisites' => 'All GE, All Prof & Educ, & All Major Subjects'],
        ];
    }
}
