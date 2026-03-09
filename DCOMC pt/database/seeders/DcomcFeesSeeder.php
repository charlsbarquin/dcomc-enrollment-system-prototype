<?php

namespace Database\Seeders;

use App\Models\Fee;
use App\Models\FeeCategory;
use Illuminate\Database\Seeder;

/**
 * Seeds fee categories and fee matrix from DCOMC Tuition Fee and Other School Fees
 * (1st semester AY 2025-2026). Fees can differ by program and year level.
 */
class DcomcFeesSeeder extends Seeder
{
    private const YEAR_LEVELS = ['1st Year', '2nd Year', '3rd Year', '4th Year'];

    private const PROGRAMS = [
        'Bachelor of Elementary Education',
        'Bachelor of Secondary Education',
        'Bachelor of Secondary Education Major in Science',
        'Bachelor of Culture and Arts Education',
        'Bachelor of Physical Education',
        'Bachelor of Technical-Vocational Teacher Education',
        'Bachelor of Science in Entrepreneurship',
    ];

    public function run(): void
    {
        $this->ensureCategories();
        $this->seedGlobalFees();
        $this->seedYearLevelFees();
        $this->seedProgramYearFees();
    }

    private function ensureCategories(): void
    {
        $categories = [
            ['Tuition', 1],
            ['Entrance', 2],
            ['Registration', 3],
            ['Library Fee', 4],
            ['Athletic Fee', 5],
            ['Computer Fee', 6],
            ['Cultural Fee', 7],
            ['NSTP FEE', 8],
            ['Laboratory Fee', 9],
            ['Medical/Dental Fee', 10],
            ['Guidance', 11],
            ['School ID', 12],
            ['Handbook', 13],
            ['Developmental Fee', 14],
        ];
        foreach ($categories as $i => [$name, $order]) {
            FeeCategory::firstOrCreate(
                ['name' => $name],
                ['sort_order' => $order]
            );
        }
    }

    /** Fees that are the same for all programs and year levels (document). */
    private function seedGlobalFees(): void
    {
        $global = [
            'Entrance' => 30.00,
            'Registration' => 100.00,
            'Library Fee' => 150.00,
            'Athletic Fee' => 130.00,
            'Computer Fee' => 200.00,
            'Cultural Fee' => 50.00,
            'Medical/Dental Fee' => 50.00,
            'School ID' => 50.00,
            'Handbook' => 50.00,
            'Developmental Fee' => 340.00,
        ];
        foreach ($global as $name => $amount) {
            $cat = FeeCategory::where('name', $name)->first();
            if (! $cat) {
                continue;
            }
            Fee::updateOrCreate(
                [
                    'fee_category_id' => $cat->id,
                    'program' => null,
                    'year_level' => null,
                ],
                [
                    'name' => $cat->name,
                    'category' => $cat->name,
                    'amount' => $amount,
                    'is_active' => true,
                ]
            );
        }
    }

    /** Fees that vary by year level only (NSTP 1st year only, Guidance 150/50, Laboratory 100 or 1000). */
    private function seedYearLevelFees(): void
    {
        $catNstp = FeeCategory::where('name', 'NSTP FEE')->first();
        if ($catNstp) {
            Fee::updateOrCreate(
                [
                    'fee_category_id' => $catNstp->id,
                    'program' => null,
                    'year_level' => '1st Year',
                ],
                ['name' => $catNstp->name, 'category' => $catNstp->name, 'amount' => 180.00, 'is_active' => true]
            );
        }

        $catGuidance = FeeCategory::where('name', 'Guidance')->first();
        if ($catGuidance) {
            Fee::updateOrCreate(
                [
                    'fee_category_id' => $catGuidance->id,
                    'program' => null,
                    'year_level' => '1st Year',
                ],
                ['name' => $catGuidance->name, 'category' => $catGuidance->name, 'amount' => 150.00, 'is_active' => true]
            );
            foreach (['2nd Year', '3rd Year', '4th Year'] as $y) {
                Fee::updateOrCreate(
                    [
                        'fee_category_id' => $catGuidance->id,
                        'program' => null,
                        'year_level' => $y,
                    ],
                    ['name' => $catGuidance->name, 'category' => $catGuidance->name, 'amount' => 50.00, 'is_active' => true]
                );
            }
        }

        $catLab = FeeCategory::where('name', 'Laboratory Fee')->first();
        if ($catLab) {
            foreach (['1st Year', '2nd Year', '3rd Year'] as $y) {
                Fee::updateOrCreate(
                    [
                        'fee_category_id' => $catLab->id,
                        'program' => null,
                        'year_level' => $y,
                    ],
                    ['name' => $catLab->name, 'category' => $catLab->name, 'amount' => 100.00, 'is_active' => true]
                );
            }
            Fee::updateOrCreate(
                [
                    'fee_category_id' => $catLab->id,
                    'program' => null,
                    'year_level' => '4th Year',
                ],
                ['name' => $catLab->name, 'category' => $catLab->name, 'amount' => 1000.00, 'is_active' => true]
            );
            // Science major: 225 for 1st–3rd year
            foreach (['1st Year', '2nd Year', '3rd Year'] as $y) {
                Fee::updateOrCreate(
                    [
                        'fee_category_id' => $catLab->id,
                        'program' => 'Bachelor of Secondary Education Major in Science',
                        'year_level' => $y,
                    ],
                    ['name' => $catLab->name, 'category' => $catLab->name, 'amount' => 225.00, 'is_active' => true]
                );
            }
        }
    }

    /** Tuition (and any program+year specific) – representative amounts per program per year. */
    private function seedProgramYearFees(): void
    {
        $catTuition = FeeCategory::where('name', 'Tuition')->first();
        if (! $catTuition) {
            return;
        }
        $defaultTuition = [
            '1st Year' => 2760.00,
            '2nd Year' => 2760.00,
            '3rd Year' => 2760.00,
            '4th Year' => 720.00,
        ];
        foreach (self::PROGRAMS as $program) {
            foreach (self::YEAR_LEVELS as $year) {
                $amount = $defaultTuition[$year] ?? 2760.00;
                Fee::updateOrCreate(
                    [
                        'fee_category_id' => $catTuition->id,
                        'program' => $program,
                        'year_level' => $year,
                    ],
                    [
                        'name' => $catTuition->name,
                        'category' => $catTuition->name,
                        'amount' => $amount,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
