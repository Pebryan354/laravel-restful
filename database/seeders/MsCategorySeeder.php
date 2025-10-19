<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MsCategory;

class MsCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MsCategory::create([
            'name' => 'Income',
        ]);

        MsCategory::create([
            'name' => 'Expense',
        ]);
    }
}
