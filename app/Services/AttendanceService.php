<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\CourseStatus;
use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function __construct(private AcademicCalendar $calendar) {}

    /**
     * @param  array<int|string, string>  $statuses
     * @param  array<int|string, ?string>  $comments
     */
    public function record(Course $course, array $statuses, array $comments, User $actor): int
    {
        $this->calendar->ensureOpen($course->academicYear);

        if ($course->status === CourseStatus::Cancelled) {
            throw ValidationException::withMessages([
                'course' => 'L\'appel ne peut pas être fait sur un cours annulé.',
            ]);
        }

        $students = Enrollment::query()
            ->where('academic_year_id', $course->academic_year_id)
            ->where('student_group_id', $course->student_group_id)
            ->where('status', 'active')
            ->pluck('student_id');

        return DB::transaction(function () use ($course, $statuses, $comments, $actor, $students): int {
            $count = 0;

            foreach ($students as $studentId) {
                $status = $statuses[$studentId] ?? $statuses[(string) $studentId] ?? null;

                if (! is_string($status) || AttendanceStatus::tryFrom($status) === null) {
                    throw ValidationException::withMessages([
                        'statuses' => 'Chaque étudiant du groupe doit avoir un statut de présence valide.',
                    ]);
                }

                AttendanceRecord::query()->updateOrCreate(
                    ['course_id' => $course->id, 'student_id' => $studentId],
                    [
                        'status' => $status,
                        'comment' => $comments[$studentId] ?? $comments[(string) $studentId] ?? null,
                        'recorded_by' => $actor->id,
                    ],
                );
                $count++;
            }

            return $count;
        });
    }
}
