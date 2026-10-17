<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            SettingsSeeder::class,
            CashCategorySeeder::class,
        ]);

        if (app()->environment(['local', 'staging', 'testing'])) {
            $this->call(UatSeeder::class);
        }
    }
}
