<?php

use App\Enums\AcademicYearStatus;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\StudentGroup;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('an administrator can recolor a group from the timetable', function () {
    $admin = userWithRole('administration');
    $year = AcademicYear::query()->create([
        'name' => '2026-2027',
        'starts_on' => '2026-09-01',
        'ends_on' => '2027-07-31',
        'status' => AcademicYearStatus::Active,
    ]);
    $level = Level::query()->create(['name' => 'Bachelor 3', 'code' => 'B3', 'sort_order' => 1]);
    $group = StudentGroup::query()->create([
        'academic_year_id' => $year->id,
        'level_id' => $level->id,
        'name' => 'Bachelor 3',
        'code' => 'B3',
        'color' => '#d0123c',
    ]);

    $this->actingAs($admin)
        ->from(route('courses.index'))
        ->patch(route('groups.color', $group), ['color' => '#2563EB'])
        ->assertRedirect(route('courses.index'));

    expect($group->fresh()->calendarColor())->toBe('#2563eb');
});

test('a student cannot change a group color', function () {
    $student = userWithRole('student');
    $year = AcademicYear::query()->create([
        'name' => '2026-2027',
        'starts_on' => '2026-09-01',
        'ends_on' => '2027-07-31',
        'status' => AcademicYearStatus::Active,
    ]);
    $level = Level::query()->create(['name' => 'Bachelor 1', 'code' => 'B1', 'sort_order' => 1]);
    $group = StudentGroup::query()->create([
        'academic_year_id' => $year->id,
        'level_id' => $level->id,
        'name' => 'Bachelor 1',
        'code' => 'B1-G1',
        'color' => '#2563eb',
    ]);

    $this->actingAs($student)
        ->patch(route('groups.color', $group), ['color' => '#000000'])
        ->assertForbidden();

    expect($group->fresh()->color)->toBe('#2563eb');
});
