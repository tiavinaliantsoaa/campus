<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            AdmissionStatusSeeder::class,
            CampusFoundationSeeder::class,
        ]);

        if (app()->environment('local') || config('campus.seed_demo')) {
            $this->call(DemoSeeder::class);
        }
    }
}
