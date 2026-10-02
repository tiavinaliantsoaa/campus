<?php

use App\Enums\AcademicYearStatus;
use App\Enums\CourseStatus;
use App\Enums\TeacherStatus;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Level;
use App\Models\Room;
use App\Models\StudentGroup;
use App\Models\Subject;
use App\Models\Teacher;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('a course is rejected when the teacher is already occupied', function () {
    $admin = userWithRole('administration');
    $year = AcademicYear::query()->create([
        'name' => '2026-2027',
        'starts_on' => '2026-09-01',
        'ends_on' => '2027-07-31',
        'status' => AcademicYearStatus::Active,
    ]);
    $level = Level::query()->create(['name' => 'Bachelor 3', 'code' => 'B3', 'sort_order' => 1]);
    $firstGroup = StudentGroup::query()->create([
        'academic_year_id' => $year->id,
        'level_id' => $level->id,
        'name' => 'B3',
        'code' => 'B3',
    ]);
    $secondGroup = StudentGroup::query()->create([
        'academic_year_id' => $year->id,
        'level_id' => $level->id,
        'name' => 'MBA',
        'code' => 'MBA',
    ]);
    $subject = Subject::query()->create(['name' => 'Finance', 'code' => 'FIN']);
    $teacher = Teacher::query()->create([
        'employee_number' => 'ENS-0001',
        'first_name' => 'Thomas',
        'last_name' => 'Bernard',
        'status' => TeacherStatus::Active,
    ]);
    $room = Room::query()->create(['name' => 'A101', 'code' => 'A101']);
    $otherRoom = Room::query()->create(['name' => 'A102', 'code' => 'A102']);

    Course::query()->create([
        'academic_year_id' => $year->id,
        'subject_id' => $subject->id,
        'teacher_id' => $teacher->id,
        'student_group_id' => $firstGroup->id,
        'room_id' => $room->id,
        'starts_at' => '2026-09-29 08:00:00',
        'ends_at' => '2026-09-29 10:00:00',
        'status' => CourseStatus::Scheduled,
    ]);

    $this->actingAs($admin)->post(route('courses.store'), [
        'subject_id' => $subject->id,
        'teacher_id' => $teacher->id,
        'student_group_id' => $secondGroup->id,
        'room_id' => $otherRoom->id,
        'starts_at' => '2026-09-29 09:00:00',
        'ends_at' => '2026-09-29 11:00:00',
    ])->assertSessionHasErrors('teacher_id');

    expect(Course::query()->count())->toBe(1);
});
