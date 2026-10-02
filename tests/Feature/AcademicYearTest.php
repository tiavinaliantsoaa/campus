<?php

use App\Enums\AcademicYearStatus;
use App\Enums\StudentStatus;
use App\Models\AcademicYear;
use App\Models\Student;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('only one academic year can be active', function () {
    $admin = userWithRole('administration');
    $first = AcademicYear::query()->create([
        'name' => '2026-2027',
        'starts_on' => '2026-09-01',
        'ends_on' => '2027-07-31',
        'status' => AcademicYearStatus::Draft,
    ]);
    $second = AcademicYear::query()->create([
        'name' => '2027-2028',
        'starts_on' => '2027-09-01',
        'ends_on' => '2028-07-31',
        'status' => AcademicYearStatus::Draft,
    ]);

    $this->actingAs($admin)->post(route('academic-years.activate', $first))->assertRedirect();
    $this->actingAs($admin)->post(route('academic-years.activate', $second))->assertSessionHasErrors('status');

    expect($first->fresh()->status)->toBe(AcademicYearStatus::Active)
        ->and($second->fresh()->status)->toBe(AcademicYearStatus::Draft);
});

test('a closed academic year rejects student changes', function () {
    $admin = userWithRole('administration');
    $year = AcademicYear::query()->create([
        'name' => '2025-2026',
        'starts_on' => '2025-09-01',
        'ends_on' => '2026-07-31',
        'status' => AcademicYearStatus::Closed,
    ]);
    $student = Student::query()->create([
        'matricule' => 'ESCM-2025-0001',
        'first_name' => 'Aina',
        'last_name' => 'Rabe',
        'status' => StudentStatus::Active,
    ]);

    $this->actingAs($admin)
        ->withSession(['academic_year_id' => $year->id])
        ->from(route('students.edit', $student))
        ->put(route('students.update', $student), [
            'first_name' => 'Aina',
            'last_name' => 'Modifié',
            'status' => StudentStatus::Active->value,
        ])
        ->assertSessionHasErrors('academic_year');

    expect($student->fresh()->last_name)->toBe('Rabe');
});
