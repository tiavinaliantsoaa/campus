<?php

use App\Enums\AcademicYearStatus;
use App\Enums\AttendanceStatus;
use App\Enums\CourseStatus;
use App\Enums\StudentStatus;
use App\Enums\TeacherStatus;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Level;
use App\Models\Student;
use App\Models\StudentGroup;
use App\Models\Subject;
use App\Models\Teacher;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('a teacher records attendance and another teacher is refused', function () {
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
        'name' => 'B1 G1',
        'code' => 'B1-G1',
    ]);
    $subject = Subject::query()->create(['name' => 'Communication', 'code' => 'COM']);
    $owner = userWithRole('teacher');
    $intruder = userWithRole('teacher');
    $teacher = Teacher::query()->create([
        'user_id' => $owner->id,
        'employee_number' => 'ENS-0001',
        'first_name' => 'Nadia',
        'last_name' => 'Rakoto',
        'status' => TeacherStatus::Active,
    ]);
    Teacher::query()->create([
        'user_id' => $intruder->id,
        'employee_number' => 'ENS-0002',
        'first_name' => 'Thomas',
        'last_name' => 'Bernard',
        'status' => TeacherStatus::Active,
    ]);
    $student = Student::query()->create([
        'matricule' => 'ESCM-2026-0001',
        'first_name' => 'Aina',
        'last_name' => 'Rabe',
        'status' => StudentStatus::Active,
    ]);
    Enrollment::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $year->id,
        'student_group_id' => $group->id,
        'level_id' => $level->id,
        'status' => 'active',
        'enrolled_on' => '2026-09-01',
    ]);
    $course = Course::query()->create([
        'academic_year_id' => $year->id,
        'subject_id' => $subject->id,
        'teacher_id' => $teacher->id,
        'student_group_id' => $group->id,
        'starts_at' => '2026-09-29 08:00:00',
        'ends_at' => '2026-09-29 10:00:00',
        'status' => CourseStatus::Scheduled,
    ]);

    $this->actingAs($intruder)
        ->post(route('attendance.store', $course), [
            'statuses' => [$student->id => AttendanceStatus::Present->value],
        ])
        ->assertNotFound();

    $this->actingAs($owner)
        ->post(route('attendance.store', $course), [
            'statuses' => [$student->id => AttendanceStatus::Late->value],
            'comments' => [$student->id => 'Arrivé à 8h15'],
        ])
        ->assertRedirect();

    $record = AttendanceRecord::query()->where('course_id', $course->id)->where('student_id', $student->id)->first();

    expect($record)->not->toBeNull()
        ->and($record->status)->toBe(AttendanceStatus::Late)
        ->and($record->comment)->toBe('Arrivé à 8h15');
});
