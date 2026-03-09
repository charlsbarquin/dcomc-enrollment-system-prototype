<?php

namespace Database\Seeders;

use App\Models\AcademicSemester;
use App\Models\AcademicYearLevel;
use App\Models\Program;
use App\Models\Subject;
use Illuminate\Database\Seeder;

/**
 * Seeds subjects for BSEd Major in Filipino from the 4-year curriculum.
 * Program → Year → First/Second Semester structure.
 */
class BsedFilipinoSubjectsSeeder extends Seeder
{
    protected string $programName = 'Bachelor of Secondary Education Major in Filipino';

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

        $this->command->info('BSEd Filipino subjects seeded by year and semester.');
    }

    /** Curriculum data: code, title, units, year (1st Year..4th Year), semester (First/Second Semester). */
    private function getCurriculum(): array
    {
        return [
            // FIRST YEAR - First Semester
            ['code' => 'Prof Educ 1', 'title' => 'The Child and Adolescent Learners and Learning Principles', 'units' => 3, 'year' => '1st Year', 'semester' => 'First Semester'],
            ['code' => 'MC FIL 1', 'title' => 'Introduksyon sa Pag-aaral ng Wika', 'units' => 3, 'year' => '1st Year', 'semester' => 'First Semester'],
            ['code' => 'MC FIL 2', 'title' => 'Panimulang Linggwistika', 'units' => 3, 'year' => '1st Year', 'semester' => 'First Semester'],
            ['code' => 'MC FIL 3', 'title' => 'Panitikan ng Rehiyon', 'units' => 3, 'year' => '1st Year', 'semester' => 'First Semester'],
            ['code' => 'GE 1', 'title' => 'Pag-unawa sa Sarili', 'units' => 3, 'year' => '1st Year', 'semester' => 'First Semester'],
            ['code' => 'GE 2', 'title' => 'Mga Babasahin Hinggil sa Kasaysayan ng Pilipinas', 'units' => 3, 'year' => '1st Year', 'semester' => 'First Semester'],
            ['code' => 'GE 3', 'title' => 'Ang Kasalukuyang Daigdig', 'units' => 3, 'year' => '1st Year', 'semester' => 'First Semester'],
            ['code' => 'NSTP 1', 'title' => 'ROTC/LTS/CWTS', 'units' => 3, 'year' => '1st Year', 'semester' => 'First Semester'],
            ['code' => 'PATHFIT 1', 'title' => 'Movement Competency Training', 'units' => 2, 'year' => '1st Year', 'semester' => 'First Semester'],

            // FIRST YEAR - Second Semester
            ['code' => 'Prof Educ 2', 'title' => 'Foundation of Special and Inclusive Education', 'units' => 3, 'year' => '1st Year', 'semester' => 'Second Semester'],
            ['code' => 'MC FIL 4', 'title' => 'Estruktura ng Wikang Filipino', 'units' => 3, 'year' => '1st Year', 'semester' => 'Second Semester', 'prerequisites' => 'MC FIL 1 & 2'],
            ['code' => 'MC FIL 5', 'title' => 'Barayti at Baryasyon ng Wika', 'units' => 3, 'year' => '1st Year', 'semester' => 'Second Semester', 'prerequisites' => 'MC FIL 1'],
            ['code' => 'MC FIL 6', 'title' => 'Sanaysay at Talumpati', 'units' => 3, 'year' => '1st Year', 'semester' => 'Second Semester', 'prerequisites' => 'MC FIL 4'],
            ['code' => 'GE 4', 'title' => 'Matimatika sa Makabagong Daigdig', 'units' => 3, 'year' => '1st Year', 'semester' => 'Second Semester'],
            ['code' => 'GE 5', 'title' => 'Malayuning Komunikasyon', 'units' => 3, 'year' => '1st Year', 'semester' => 'Second Semester'],
            ['code' => 'GE 6', 'title' => 'Art Appreciation', 'units' => 3, 'year' => '1st Year', 'semester' => 'Second Semester'],
            ['code' => 'NSTP 2', 'title' => 'ROTC/LTS/CWTS', 'units' => 3, 'year' => '1st Year', 'semester' => 'Second Semester', 'prerequisites' => 'NSTP 1'],
            ['code' => 'PATHFIT 2', 'title' => 'Exercise-Based Fitness Activities', 'units' => 2, 'year' => '1st Year', 'semester' => 'Second Semester', 'prerequisites' => 'PATHFIT 1'],

            // SECOND YEAR - First Semester
            ['code' => 'Prof Educ 3', 'title' => 'The Teaching Profession', 'units' => 3, 'year' => '2nd Year', 'semester' => 'First Semester'],
            ['code' => 'GE 7', 'title' => 'Agham, Teknolohiya, at Lipunan', 'units' => 3, 'year' => '2nd Year', 'semester' => 'First Semester'],
            ['code' => 'GE ELEC 3', 'title' => 'Living in the IT Era', 'units' => 3, 'year' => '2nd Year', 'semester' => 'First Semester'],
            ['code' => 'GE 8', 'title' => 'Etika', 'units' => 3, 'year' => '2nd Year', 'semester' => 'First Semester'],
            ['code' => 'GE 9', 'title' => 'Ang Buhay at mga Akda ni Rizal', 'units' => 3, 'year' => '2nd Year', 'semester' => 'First Semester'],
            ['code' => 'MC FIL 7', 'title' => 'Ang Filipino sa Kurikulum ng Batayang Edukasyon', 'units' => 3, 'year' => '2nd Year', 'semester' => 'First Semester', 'prerequisites' => 'MC FIL 5 & 6'],
            ['code' => 'MC FIL 8', 'title' => 'Pagtuturo at Pagtataya ng Makrong Kasanayang Pangwika', 'units' => 3, 'year' => '2nd Year', 'semester' => 'First Semester', 'prerequisites' => 'MC FIL 5'],
            ['code' => 'MC FIL 9', 'title' => 'Paghahanda at Ebalwasyon ng Kagamitang Panturo', 'units' => 3, 'year' => '2nd Year', 'semester' => 'First Semester'],
            ['code' => 'PATHFIT 3', 'title' => 'Sports', 'units' => 2, 'year' => '2nd Year', 'semester' => 'First Semester', 'prerequisites' => 'PATHFIT 2'],

            // SECOND YEAR - Second Semester
            ['code' => 'Prof Educ 4', 'title' => 'The Teacher and the School Curriculum', 'units' => 3, 'year' => '2nd Year', 'semester' => 'Second Semester'],
            ['code' => 'Prof Educ 5', 'title' => 'The Teacher and the Community, School Culture, and Organizational Leadership', 'units' => 3, 'year' => '2nd Year', 'semester' => 'Second Semester'],
            ['code' => 'GE ELEC 1', 'title' => 'The Entrepreneurial Mind', 'units' => 3, 'year' => '2nd Year', 'semester' => 'Second Semester'],
            ['code' => 'GE ELEC 2', 'title' => 'Philippine Popular Culture', 'units' => 3, 'year' => '2nd Year', 'semester' => 'Second Semester'],
            ['code' => 'MC FIL 10', 'title' => 'Maikling Kuwento at Nobelang Filipino', 'units' => 3, 'year' => '2nd Year', 'semester' => 'Second Semester', 'prerequisites' => 'MC FIL 6'],
            ['code' => 'MC FIL 11', 'title' => 'Dulaang Filipino', 'units' => 3, 'year' => '2nd Year', 'semester' => 'Second Semester', 'prerequisites' => 'MC FIL 8'],
            ['code' => 'MC FIL 12', 'title' => 'Panulaang Filipino', 'units' => 3, 'year' => '2nd Year', 'semester' => 'Second Semester', 'prerequisites' => 'MC FIL 8'],
            ['code' => 'MC FIL Elec 1', 'title' => 'Filipino para sa Natatanging Gamit', 'units' => 3, 'year' => '2nd Year', 'semester' => 'Second Semester'],
            ['code' => 'PATHFIT 4', 'title' => 'Dance', 'units' => 2, 'year' => '2nd Year', 'semester' => 'Second Semester', 'prerequisites' => 'PATHFIT 3'],

            // THIRD YEAR - First Semester
            ['code' => 'Prof Educ 6', 'title' => 'Facilitating Learner-Centered Teaching', 'units' => 3, 'year' => '3rd Year', 'semester' => 'First Semester'],
            ['code' => 'Prof Educ 7', 'title' => 'Technology for Teaching and Learning 1', 'units' => 3, 'year' => '3rd Year', 'semester' => 'First Semester'],
            ['code' => 'Prof Educ 8', 'title' => 'Assessment in Learning 1', 'units' => 3, 'year' => '3rd Year', 'semester' => 'First Semester'],
            ['code' => 'MC FIL 13', 'title' => 'Mga Natatanging Diskurso sa Wika at Panitikan', 'units' => 3, 'year' => '3rd Year', 'semester' => 'First Semester', 'prerequisites' => 'MC FIL 10, 11, & 12'],
            ['code' => 'MC FIL 14', 'title' => 'Panunuring Pampanitikan', 'units' => 3, 'year' => '3rd Year', 'semester' => 'First Semester', 'prerequisites' => 'MC FIL 10, 11, & 12'],
            ['code' => 'MC FIL 15', 'title' => 'Introduksyon sa Pagsasalin', 'units' => 3, 'year' => '3rd Year', 'semester' => 'First Semester', 'prerequisites' => 'MC FIL 4 & 5'],
            ['code' => 'MC FIL 16', 'title' => 'Kulturang Popular', 'units' => 3, 'year' => '3rd Year', 'semester' => 'First Semester'],
            ['code' => 'MC FIL 17', 'title' => 'Pananaliksik sa Filipino 1', 'units' => 3, 'year' => '3rd Year', 'semester' => 'First Semester'],

            // THIRD YEAR - Second Semester
            ['code' => 'Prof Educ 9', 'title' => 'Assessment in Learning 2', 'units' => 3, 'year' => '3rd Year', 'semester' => 'Second Semester'],
            ['code' => 'Prof Educ 10', 'title' => 'Building and Enhancing New Literacies Across the Curriculum', 'units' => 3, 'year' => '3rd Year', 'semester' => 'Second Semester'],
            ['code' => 'MC FIL 18', 'title' => 'Pananaliksik sa Filipino 2', 'units' => 3, 'year' => '3rd Year', 'semester' => 'Second Semester', 'prerequisites' => 'MC FIL 17'],
            ['code' => 'MC FIL 19', 'title' => 'Paggamit ng Teknolohiya sa Pagtuturo ng Filipino', 'units' => 3, 'year' => '3rd Year', 'semester' => 'Second Semester', 'prerequisites' => 'Prof Educ 7'],
            ['code' => 'MC FIL 20', 'title' => 'Ugnayan ng Wika, Kultura at Lipunan', 'units' => 3, 'year' => '3rd Year', 'semester' => 'Second Semester', 'prerequisites' => 'MC FIL 13'],
            ['code' => 'MC FIL 21', 'title' => 'Introduksyon sa Pamamahayag', 'units' => 3, 'year' => '3rd Year', 'semester' => 'Second Semester', 'prerequisites' => 'MC FIL 13'],
            ['code' => 'MC FIL Elec 2', 'title' => 'Malikhaling Pagsulat', 'units' => 3, 'year' => '3rd Year', 'semester' => 'Second Semester'],
            ['code' => 'MC FIL 22', 'title' => 'Introduksyon sa Pananaliksik-Wika at Panitikan', 'units' => 3, 'year' => '3rd Year', 'semester' => 'Second Semester'],

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
