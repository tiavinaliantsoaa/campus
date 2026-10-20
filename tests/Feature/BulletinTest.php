<?php

use App\Enums\AcademicYearStatus;
use App\Models\AcademicYear;
use App\Models\Semester;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('administration can create a semester without duplicating its name or period', function () {
    $year = AcademicYear::query()->create([
        'name' => '2026-2027',
        'starts_on' => '2026-09-01',
        'ends_on' => '2027-07-31',
        'status' => AcademicYearStatus::Active,
    ]);
    $admin = userWithRole('administration');
    $payload = ['name' => 'Semestre 1', 'starts_on' => '2026-09-01', 'ends_on' => '2026-12-31'];

    $this->actingAs($admin)->withSession(['academic_year_id' => $year->id])
        ->post(route('bulletins.semesters.store'), $payload)->assertRedirect();

    expect(Semester::query()->where('academic_year_id', $year->id)->count())->toBe(1);

    $this->actingAs($admin)->withSession(['academic_year_id' => $year->id])
        ->post(route('bulletins.semesters.store'), $payload)->assertSessionHasErrors('name');

    $this->actingAs($admin)->withSession(['academic_year_id' => $year->id])
        ->post(route('bulletins.semesters.store'), [
            'name' => 'Autre semestre', 'starts_on' => '2026-12-01', 'ends_on' => '2027-03-01',
        ])->assertSessionHasErrors('starts_on');

    expect(Semester::query()->where('academic_year_id', $year->id)->count())->toBe(1);
});

test('a teacher cannot create a semester', function () {
    $this->actingAs(userWithRole('teacher'))
        ->post(route('bulletins.semesters.store'), [
            'name' => 'Semestre 1', 'starts_on' => '2026-09-01', 'ends_on' => '2026-12-31',
        ])->assertForbidden();
});

test('the bulletin catalog keeps the modules and credits from the maquette', function () {
    $modules = collect(config('bulletins.modules'));

    expect($modules->where('level', 'B1')->where('term', 1)->count())->toBe(16)
        ->and($modules->where('level', 'B1')->where('term', 2)->count())->toBe(14)
        ->and($modules->where('level', 'B2')->where('term', 1)->count())->toBe(14)
        ->and($modules->where('level', 'B2')->where('term', 2)->count())->toBe(14)
        ->and($modules->where('level', 'B3')->count())->toBe(34)
        ->and($modules->where('level', 'MBA')->count())->toBe(36)
        ->and($modules->firstWhere('title', 'Elaborer un diagnostic marketing')['credits'])->toBe(2.0)
        ->and($modules->firstWhere('title', 'Business Game')['credits'])->toBeNull();
});
