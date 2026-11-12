<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RatingSchemeSeeder::class);
        $this->call(GoldChecklistSeeder::class);
        $this->call(SilverBronzeChecklistSeeder::class);

        if (app()->environment('local')) {
            $this->call(LocalDemoCatalogueSeeder::class);
            $this->call(LocalDemoAdminSeeder::class);
            $this->call(LocalDemoOperationsSeeder::class);
        }
    }
}
