<?php

use App\Enums\AcademicYearStatus;
use App\Enums\AssessmentStatus;
use App\Enums\AssessmentType;
use App\Enums\StudentStatus;
use App\Enums\TeacherStatus;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Enrollment;
use App\Models\Level;
use App\Models\Student;
use App\Models\StudentGroup;
use App\Models\Subject;
use App\Models\Teacher;
use App\Services\Gradebook;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('a validated grade produces the weighted average', function () {
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
    $teacherUser = userWithRole('teacher');
    $teacher = Teacher::query()->create([
        'user_id' => $teacherUser->id,
        'employee_number' => 'ENS-0001',
        'first_name' => 'Sarah',
        'last_name' => 'Raveloson',
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
    ]);

    $this->actingAs($teacherUser)->post(route('assessments.store'), [
        'title' => 'Contrôle',
        'type' => AssessmentType::Quiz->value,
        'subject_id' => $subject->id,
        'teacher_id' => $teacher->id,
        'student_group_id' => $group->id,
        'coefficient' => 2,
        'max_score' => 20,
        'assessed_on' => '2026-09-29',
    ])->assertRedirect();

    $assessment = Assessment::query()->first();

    $this->actingAs($teacherUser)->post(route('assessments.save', $assessment), [
        'scores' => [$student->id => 15],
    ])->assertRedirect();

    expect(app(Gradebook::class)->average($student, $year))->toBeNull();

    $validator = userWithRole('pedagogical');
    $this->actingAs($teacherUser)->post(route('assessments.submit', $assessment))->assertRedirect();
    $this->actingAs($validator)->post(route('assessments.validate', $assessment))->assertRedirect();

    expect($assessment->fresh()->status)->toBe(AssessmentStatus::Validated)
        ->and(app(Gradebook::class)->average($student->fresh(), $year))->toBe('15.00');
});
