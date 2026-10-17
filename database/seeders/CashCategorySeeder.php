<?php

namespace Database\Seeders;

use App\Models\CashCategory;
use Illuminate\Database\Seeder;

class CashCategorySeeder extends Seeder
{
    public function run(): void
    {
        $out = ['Listrik & Air', 'Gaji', 'Sewa', 'Transport', 'Perlengkapan Toko', 'Lain-lain'];
        $in = ['Modal', 'Pendapatan Lain'];

        foreach ($out as $name) {
            CashCategory::query()->firstOrCreate(['name' => $name], ['type' => 'out', 'is_active' => true]);
        }

        foreach ($in as $name) {
            CashCategory::query()->firstOrCreate(['name' => $name], ['type' => 'in', 'is_active' => true]);
        }
    }
}
