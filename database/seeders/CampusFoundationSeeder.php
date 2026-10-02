<?php

namespace Database\Seeders;

use App\Enums\AcademicYearStatus;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CampusFoundationSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            ['name' => 'Bachelor 1', 'code' => 'B1', 'sort_order' => 1],
            ['name' => 'Bachelor 2', 'code' => 'B2', 'sort_order' => 2],
            ['name' => 'Bachelor 3', 'code' => 'B3', 'sort_order' => 3],
            ['name' => 'MBA', 'code' => 'MBA', 'sort_order' => 4],
            ['name' => 'Master 1', 'code' => 'M1', 'sort_order' => 5],
        ];

        foreach ($levels as $level) {
            Level::query()->updateOrCreate(['code' => $level['code']], $level);
        }

        $year = AcademicYear::query()->firstOrCreate(
            ['name' => '2026-2027'],
            [
                'starts_on' => '2026-09-01',
                'ends_on' => '2027-07-31',
                'status' => AcademicYearStatus::Active,
                'ranking_enabled' => true,
            ],
        );

        Semester::query()->updateOrCreate(
            ['academic_year_id' => $year->id, 'name' => 'Semestre 1'],
            ['starts_on' => '2026-09-01', 'ends_on' => '2027-01-31', 'sort_order' => 1],
        );
        Semester::query()->updateOrCreate(
            ['academic_year_id' => $year->id, 'name' => 'Semestre 2'],
            ['starts_on' => '2027-02-01', 'ends_on' => '2027-07-31', 'sort_order' => 2],
        );

        $email = config('campus.admin_email');
        $configuredPassword = config('campus.admin_password');
        $generatedPassword = null;

        if (is_string($configuredPassword) && $configuredPassword !== '') {
            $plainPassword = $configuredPassword;
        } elseif (app()->environment('local')) {
            $plainPassword = 'password';
        } else {
            $plainPassword = Str::password(16);
            $generatedPassword = $plainPassword;
        }

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => config('campus.admin_name'),
                'password' => $plainPassword,
            ],
        );

        $role = Role::query()->where('slug', 'administration')->first();

        if ($role) {
            $user->roles()->syncWithoutDetaching([$role->id]);
        }

        if ($generatedPassword !== null && $user->wasRecentlyCreated) {
            $this->command?->warn('Compte direction créé pour '.$email.'. Mot de passe affiché une seule fois : '.$generatedPassword);
        }
    }
}
