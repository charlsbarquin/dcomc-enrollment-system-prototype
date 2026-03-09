<?php

namespace Database\Seeders;

use App\Models\FeeCategory;
use App\Models\Fee;
use Illuminate\Database\Seeder;

class FeeCategorySeeder extends Seeder
{
    /** Assessed fees from DCOMC (e.g. 1st year baseline). Amounts in pesos. */
    private const CATEGORIES_WITH_AMOUNTS = [
        ['name' => 'Tuition', 'amount' => 2760.00],
        ['name' => 'Entrance', 'amount' => 30.00],
        ['name' => 'Registration', 'amount' => 100.00],
        ['name' => 'Library Fee', 'amount' => 150.00],
        ['name' => 'Athletic Fee', 'amount' => 130.00],
        ['name' => 'Computer Fee', 'amount' => 200.00],
        ['name' => 'Cultural Fee', 'amount' => 100.00],
        ['name' => 'NSTP FEE', 'amount' => 180.00],
        ['name' => 'Medical/Dental Fee', 'amount' => 50.00],
        ['name' => 'Guidance', 'amount' => 50.00],
        ['name' => 'Developmental Fee', 'amount' => 340.00],
    ];

    public function run(): void
    {
        $yearLevel = '1st Year';

        foreach (self::CATEGORIES_WITH_AMOUNTS as $index => $item) {
            $cat = FeeCategory::firstOrCreate(
                ['name' => $item['name']],
                ['sort_order' => $index + 1]
            );

            Fee::firstOrCreate(
                [
                    'fee_category_id' => $cat->id,
                    'year_level' => $yearLevel,
                ],
                [
                    'name' => $cat->name,
                    'category' => $cat->name,
                    'amount' => $item['amount'],
                    'is_active' => true,
                ]
            );
        }
    }
}
