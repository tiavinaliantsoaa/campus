<?php

use App\Enums\AcademicYearStatus;
use App\Enums\StudentStatus;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\Student;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->year = AcademicYear::query()->create([
        'name' => '2026-2027',
        'starts_on' => '2026-09-01',
        'ends_on' => '2027-07-31',
        'status' => AcademicYearStatus::Active,
    ]);
});

test('a student cannot open another student profile', function () {
    $owner = userWithRole('student');
    $otherUser = userWithRole('student');
    $mine = Student::query()->create([
        'user_id' => $owner->id,
        'matricule' => 'ESCM-2026-0001',
        'first_name' => 'Aina',
        'last_name' => 'Rabe',
        'status' => StudentStatus::Active,
    ]);
    $other = Student::query()->create([
        'user_id' => $otherUser->id,
        'matricule' => 'ESCM-2026-0002',
        'first_name' => 'Hery',
        'last_name' => 'Andria',
        'status' => StudentStatus::Active,
    ]);

    $this->actingAs($owner)->get(route('students.show', $other))->assertNotFound();
    $this->actingAs($owner)->get(route('students.show', $mine))->assertOk()->assertSee('Aina Rabe');
});

test('a parent only sees the linked student', function () {
    $parent = userWithRole('parent');
    $guardian = Guardian::query()->create([
        'user_id' => $parent->id,
        'first_name' => 'Rakoto',
        'last_name' => 'Parent',
        'email' => 'parent@escm.mg',
    ]);
    $child = Student::query()->create([
        'matricule' => 'ESCM-2026-0003',
        'first_name' => 'Mialy',
        'last_name' => 'Rakoto',
        'status' => StudentStatus::Active,
    ]);
    $stranger = Student::query()->create([
        'matricule' => 'ESCM-2026-0004',
        'first_name' => 'Soa',
        'last_name' => 'Rabe',
        'status' => StudentStatus::Active,
    ]);
    $guardian->students()->attach($child->id, ['relationship' => 'Parent', 'is_primary' => true]);
    Enrollment::query()->create([
        'student_id' => $child->id,
        'academic_year_id' => $this->year->id,
        'status' => 'active',
        'enrolled_on' => '2026-09-01',
    ]);

    $this->actingAs($parent)->get(route('students.show', $stranger))->assertNotFound();
    $this->actingAs($parent)->get(route('students.show', $child))->assertOk()->assertSee('Mialy Rakoto');
});
