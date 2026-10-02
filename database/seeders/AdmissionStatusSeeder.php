<?php

namespace Database\Seeders;

use App\Models\AdmissionStatus;
use Illuminate\Database\Seeder;

class AdmissionStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['slug' => 'submitted', 'name' => 'Déposée', 'sort_order' => 1, 'is_terminal' => false, 'converts_to_student' => false],
            ['slug' => 'review', 'name' => 'En examen', 'sort_order' => 2, 'is_terminal' => false, 'converts_to_student' => false],
            ['slug' => 'admitted', 'name' => 'Admise', 'sort_order' => 3, 'is_terminal' => false, 'converts_to_student' => true],
            ['slug' => 'rejected', 'name' => 'Refusée', 'sort_order' => 4, 'is_terminal' => true, 'converts_to_student' => false],
            ['slug' => 'converted', 'name' => 'Inscrite', 'sort_order' => 5, 'is_terminal' => true, 'converts_to_student' => false],
            ['slug' => 'withdrawn', 'name' => 'Retirée', 'sort_order' => 6, 'is_terminal' => true, 'converts_to_student' => false],
        ];

        foreach ($statuses as $status) {
            AdmissionStatus::query()->updateOrCreate(['slug' => $status['slug']], $status);
        }
    }
}
