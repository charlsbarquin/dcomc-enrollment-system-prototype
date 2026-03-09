<?php

namespace Database\Seeders;

use App\Models\AcademicSemester;
use App\Models\AcademicYearLevel;
use App\Models\Program;
use App\Models\Subject;
use Illuminate\Database\Seeder;

/**
 * Seeds subjects for Bachelor of Culture and Arts Education (BCAEd) from the 4-year curriculum.
 * Program → Year → First/Second Semester structure.
 */
class BcaedSubjectsSeeder extends Seeder
{
    protected string $programName = 'Bachelor of Culture and Arts Education';

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

        $this->command->info('BCAEd subjects seeded by year and semester.');
    }

    /** Curriculum data: code, title, units, year (1st Year..4th Year), semester (First/Second Semester). */
    private function getCurriculum(): array
    {
        return [
            // FIRST YEAR - First Semester
            ['code' => 'Prof Educ 1', 'title' => 'The Child and Adolescent Learners and Learning Principles', 'units' => 3, 'year' => '1st Year', 'semester' => 'First Semester'],
            ['code' => 'CAEd 1', 'title' => 'Foundation of Culture and Arts Education', 'units' => 3, 'year' => '1st Year', 'semester' => 'First Semester'],
            ['code' => 'GE 1', 'title' => 'Understanding the Self', 'units' => 3, 'year' => '1st Year', 'semester' => 'First Semester'],
            ['code' => 'GE 2', 'title' => 'Readings in the Philippine History', 'units' => 3, 'year' => '1st Year', 'semester' => 'First Semester'],
            ['code' => 'GE 3', 'title' => 'The Contemporary World', 'units' => 3, 'year' => '1st Year', 'semester' => 'First Semester'],
            ['code' => 'GE 4', 'title' => 'Mathematics in the Modern World', 'units' => 3, 'year' => '1st Year', 'semester' => 'First Semester'],
            ['code' => 'GE 5', 'title' => 'Purposive Communication', 'units' => 3, 'year' => '1st Year', 'semester' => 'First Semester'],
            ['code' => 'NSTP 1', 'title' => 'ROTC/LTS/CWTS', 'units' => 3, 'year' => '1st Year', 'semester' => 'First Semester'],
            ['code' => 'PATHFIT 1', 'title' => 'Movement Competency Training', 'units' => 2, 'year' => '1st Year', 'semester' => 'First Semester'],

            // FIRST YEAR - Second Semester
            ['code' => 'Prof Educ 2', 'title' => 'Foundation of Special and Inclusive Education', 'units' => 3, 'year' => '1st Year', 'semester' => 'Second Semester'],
            ['code' => 'CAEd 2', 'title' => 'Perspective in Philippine Cultural Heritage', 'units' => 3, 'year' => '1st Year', 'semester' => 'Second Semester'],
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
            ['code' => 'CAEd 3', 'title' => 'Principles and Practices in Creative Expressions: Music Overview I - The Philosophical and Historical Foundations of Creative Expressions in Sound-I', 'units' => 3, 'year' => '2nd Year', 'semester' => 'First Semester', 'prerequisites' => 'CAEd 1'],
            ['code' => 'CAEd 4', 'title' => 'Principles and Practices in Creative Expressions: Visual Arts Overview I - Visual Arts in Traditional Societies Form, Meaning and Process in the Visual Arts of Traditional Societies', 'units' => 3, 'year' => '2nd Year', 'semester' => 'First Semester', 'prerequisites' => 'CAEd 1'],
            ['code' => 'CAEd 5', 'title' => 'Principles and Practices in Creative Expressions: Dance Overview I - Foundations of Dance', 'units' => 3, 'year' => '2nd Year', 'semester' => 'First Semester', 'prerequisites' => 'CAEd 1'],
            ['code' => 'CAEd 6', 'title' => 'Principles and Practices in Creative Expressions: Drama Overview I: The Philosophical and Educational Foundations of Creative Drama', 'units' => 3, 'year' => '2nd Year', 'semester' => 'First Semester', 'prerequisites' => 'CAEd 1'],
            ['code' => 'CAEd Elec 1', 'title' => 'Elective-Creative Industries as Culture and Arts Practice', 'units' => 3, 'year' => '2nd Year', 'semester' => 'First Semester'],
            ['code' => 'PATHFIT 3', 'title' => 'Sports', 'units' => 2, 'year' => '2nd Year', 'semester' => 'First Semester', 'prerequisites' => 'PATHFIT 2'],

            // SECOND YEAR - Second Semester
            ['code' => 'Prof Educ 4', 'title' => 'The Teacher and the School Curriculum', 'units' => 3, 'year' => '2nd Year', 'semester' => 'Second Semester'],
            ['code' => 'Prof Educ 5', 'title' => 'The Teacher and the Community, School Culture, and Organizational Leadership', 'units' => 3, 'year' => '2nd Year', 'semester' => 'Second Semester'],
            ['code' => 'CAEd 7', 'title' => 'Culture and Arts Education in Plural Societies', 'units' => 3, 'year' => '2nd Year', 'semester' => 'Second Semester'],
            ['code' => 'CAEd 8', 'title' => 'Principles and Practices in Creative Expressions: Music Overview II - The Philosophical and Historical Foundations of Creative Expressions in Sound-II', 'units' => 3, 'year' => '2nd Year', 'semester' => 'Second Semester', 'prerequisites' => 'CAEd 3'],
            ['code' => 'CAEd 9', 'title' => 'Principles and Practices in Creative Expressions: Visual Arts Overview II - Contemporary Art in Various Contexts Design and Method in Contemporary Visual Arts', 'units' => 3, 'year' => '2nd Year', 'semester' => 'Second Semester', 'prerequisites' => 'CAEd 4'],
            ['code' => 'CAEd 10', 'title' => 'Principles and Practices in Creative Expressions: Dance Overview II - Philippine Traditional Dances', 'units' => 3, 'year' => '2nd Year', 'semester' => 'Second Semester', 'prerequisites' => 'CAEd 5'],
            ['code' => 'CAEd 11', 'title' => 'Principles and Practices in Creative Expressions: Drama Overview II - Introduction to the Basic Elements of Drama and Theater Production', 'units' => 3, 'year' => '2nd Year', 'semester' => 'Second Semester', 'prerequisites' => 'CAEd 6'],
            ['code' => 'CAEd 12', 'title' => 'Art Apprenticeship I', 'units' => 3, 'year' => '2nd Year', 'semester' => 'Second Semester'],
            ['code' => 'PATHFIT 4', 'title' => 'Dance', 'units' => 2, 'year' => '2nd Year', 'semester' => 'Second Semester', 'prerequisites' => 'PATHFIT 3'],

            // THIRD YEAR - First Semester
            ['code' => 'Prof Educ 6', 'title' => 'Facilitating Learner-Centered Teaching', 'units' => 3, 'year' => '3rd Year', 'semester' => 'First Semester'],
            ['code' => 'Prof Educ 7', 'title' => 'Technology for Teaching and Learning 1', 'units' => 3, 'year' => '3rd Year', 'semester' => 'First Semester'],
            ['code' => 'Prof Educ 8', 'title' => 'Assessment in Learning 1', 'units' => 3, 'year' => '3rd Year', 'semester' => 'First Semester'],
            ['code' => 'CAEd 14', 'title' => 'Principles and Practices in Creative Expressions: Music Overview III - Music Pedagogy I (Teaching Methods in Music for K-6)', 'units' => 3, 'year' => '3rd Year', 'semester' => 'First Semester', 'prerequisites' => 'CAEd 8'],
            ['code' => 'CAEd 15', 'title' => 'Principles and Practices in Creative Expressions: Visual Arts Overview III - Analysis and Critical Understanding of the Visual Arts', 'units' => 3, 'year' => '3rd Year', 'semester' => 'First Semester', 'prerequisites' => 'CAEd 9'],
            ['code' => 'CAEd 16', 'title' => 'Principles and Practices in Creative Expressions: Dance Overview III - International Dance and other Forms', 'units' => 3, 'year' => '3rd Year', 'semester' => 'First Semester', 'prerequisites' => 'CAEd 10'],
            ['code' => 'CAEd 17', 'title' => 'Principles and Practices in Creative Expressions: Drama Overview III - Dramaturgy and Aesthetics in Philippine and Non-Philippine Theater Classics', 'units' => 3, 'year' => '3rd Year', 'semester' => 'First Semester', 'prerequisites' => 'CAEd 11'],
            ['code' => 'CAEd 13', 'title' => 'Art Apprenticeship II', 'units' => 3, 'year' => '3rd Year', 'semester' => 'First Semester', 'prerequisites' => 'CAEd 12'],
            ['code' => 'CAEd 18', 'title' => 'Research I - Arts and Culture Research', 'units' => 3, 'year' => '3rd Year', 'semester' => 'First Semester'],

            // THIRD YEAR - Second Semester
            ['code' => 'Prof Educ 9', 'title' => 'Assessment in Learning 2', 'units' => 3, 'year' => '3rd Year', 'semester' => 'Second Semester'],
            ['code' => 'Prof Educ 10', 'title' => 'Building and Enhancing New Literacies Across the Curriculum', 'units' => 3, 'year' => '3rd Year', 'semester' => 'Second Semester'],
            ['code' => 'CAEd 20', 'title' => 'Principles and Practices in Creative Expressions: Music Overview IV - Teaching Methods in Music for Junior and Senior High School', 'units' => 3, 'year' => '3rd Year', 'semester' => 'Second Semester', 'prerequisites' => 'CAEd 14'],
            ['code' => 'CAEd 21', 'title' => 'Principles and Practices in Creative Expressions: Visual Arts Overview IV - Teaching the Visual Arts', 'units' => 3, 'year' => '3rd Year', 'semester' => 'Second Semester', 'prerequisites' => 'CAEd 15'],
            ['code' => 'CAEd 22', 'title' => 'Principles and Practices in Creative Expressions: Dance Overview IV - Teaching Dance', 'units' => 3, 'year' => '3rd Year', 'semester' => 'Second Semester', 'prerequisites' => 'CAEd 16'],
            ['code' => 'CAEd 23', 'title' => 'Principles and Practices in Creative Expressions: Drama Overview IV - Principles and Practice of Teaching Drama', 'units' => 3, 'year' => '3rd Year', 'semester' => 'Second Semester', 'prerequisites' => 'CAEd 17'],
            ['code' => 'CAEd 24', 'title' => 'Technology for Teaching and Learning in Culture and Arts Education', 'units' => 3, 'year' => '3rd Year', 'semester' => 'Second Semester'],
            ['code' => 'CAEd 19', 'title' => 'Research II - Culminating Project', 'units' => 3, 'year' => '3rd Year', 'semester' => 'Second Semester', 'prerequisites' => 'CAEd 18'],

            // FOURTH YEAR - First Semester
            ['code' => 'Prof Educ 11', 'title' => 'Field Study 1: Observations of Teaching - Learning in Actual School Environment', 'units' => 3, 'year' => '4th Year', 'semester' => 'First Semester', 'prerequisites' => 'All GE, All Prof Educ, & All Major Subjects'],
            ['code' => 'Prof Educ 12', 'title' => 'Field Study 2: Participation and Teaching Assistantship', 'units' => 3, 'year' => '4th Year', 'semester' => 'First Semester', 'prerequisites' => 'All GE, All Prof Educ, & All Major Subjects'],
            ['code' => 'Review 1', 'title' => 'Enrichment Class (Major Subjects and General Subjects)', 'units' => 6, 'year' => '4th Year', 'semester' => 'First Semester', 'prerequisites' => 'All GE, All Prof Educ, & All Major Subjects'],

            // FOURTH YEAR - Second Semester
            ['code' => 'Prof Educ 13', 'title' => 'Field Study 3: The Teaching Internship', 'units' => 6, 'year' => '4th Year', 'semester' => 'Second Semester', 'prerequisites' => 'All GE, All Prof Educ, & All Major Subjects'],
            ['code' => 'Review 2', 'title' => 'Enrichment Class (Professional Education Subjects)', 'units' => 6, 'year' => '4th Year', 'semester' => 'Second Semester', 'prerequisites' => 'All GE, All Prof Educ, & All Major Subjects'],
        ];
    }
}
