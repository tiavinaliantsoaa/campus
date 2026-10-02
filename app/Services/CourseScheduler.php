<?php

namespace App\Services;

use App\Enums\CourseStatus;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\StudentGroup;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CourseScheduler
{
    public function __construct(private AcademicCalendar $calendar) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, AcademicYear $year): Course
    {
        $this->calendar->ensureOpen($year);
        $this->assertGroup($data['student_group_id'], $year);

        $occurrences = $this->occurrences($data);

        return DB::transaction(function () use ($data, $year, $occurrences): Course {
            $key = count($occurrences) > 1 ? (string) Str::uuid() : null;
            $first = null;

            foreach ($occurrences as [$starts, $ends]) {
                $payload = [
                    'teacher_id' => $data['teacher_id'],
                    'room_id' => $data['room_id'] ?? null,
                    'student_group_id' => $data['student_group_id'],
                    'starts_at' => $starts,
                    'ends_at' => $ends,
                ];
                $this->assertNoConflict($payload);

                $course = Course::query()->create([
                    ...$payload,
                    'academic_year_id' => $year->id,
                    'semester_id' => $data['semester_id'] ?? null,
                    'subject_id' => $data['subject_id'],
                    'title' => $data['title'] ?? null,
                    'recurrence_key' => $key,
                    'status' => CourseStatus::Scheduled,
                    'notes' => $data['notes'] ?? null,
                ]);

                $first ??= $course;
            }

            return $first;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Course $course, array $data): Course
    {
        $this->calendar->ensureOpen($course->academicYear);
        $this->assertGroup($data['student_group_id'], $course->academicYear);

        $starts = Carbon::parse($data['starts_at']);
        $ends = Carbon::parse($data['ends_at']);
        $this->assertInterval($starts, $ends);

        $payload = [
            'teacher_id' => $data['teacher_id'],
            'room_id' => $data['room_id'] ?? null,
            'student_group_id' => $data['student_group_id'],
            'starts_at' => $starts,
            'ends_at' => $ends,
        ];

        $this->assertNoConflict($payload, $course);

        $course->update([
            ...$payload,
            'semester_id' => $data['semester_id'] ?? null,
            'subject_id' => $data['subject_id'],
            'title' => $data['title'] ?? null,
            'status' => $data['status'] ?? $course->status,
            'notes' => $data['notes'] ?? null,
        ]);

        return $course->refresh();
    }

    public function delete(Course $course): void
    {
        $this->calendar->ensureOpen($course->academicYear);
        $course->delete();
    }

    /**
     * @param  array{teacher_id: int, room_id: ?int, student_group_id: int, starts_at: Carbon, ends_at: Carbon}  $data
     */
    public function assertNoConflict(array $data, ?Course $ignore = null): void
    {
        $overlap = Course::query()
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
            ->where('status', CourseStatus::Scheduled)
            ->where('starts_at', '<', $data['ends_at'])
            ->where('ends_at', '>', $data['starts_at']);

        $messages = [];

        if ((clone $overlap)->where('teacher_id', $data['teacher_id'])->exists()) {
            $messages['teacher_id'] = 'Cet enseignant a déjà un cours sur ce créneau.';
        }

        if (! empty($data['room_id']) && (clone $overlap)->where('room_id', $data['room_id'])->exists()) {
            $messages['room_id'] = 'Cette salle est déjà occupée sur ce créneau.';
        }

        if ((clone $overlap)->where('student_group_id', $data['student_group_id'])->exists()) {
            $messages['student_group_id'] = 'Ce groupe a déjà un cours sur ce créneau.';
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{0: Carbon, 1: Carbon}>
     */
    private function occurrences(array $data): array
    {
        $starts = Carbon::parse($data['starts_at']);
        $ends = Carbon::parse($data['ends_at']);
        $this->assertInterval($starts, $ends);

        $occurrences = [[$starts->copy(), $ends->copy()]];

        if (empty($data['repeat_until'])) {
            return $occurrences;
        }

        $until = Carbon::parse($data['repeat_until'])->endOfDay();
        $cursorStart = $starts->copy()->addWeek();
        $cursorEnd = $ends->copy()->addWeek();
        $guard = 0;

        while ($cursorStart->lte($until) && $guard < 40) {
            $occurrences[] = [$cursorStart->copy(), $cursorEnd->copy()];
            $cursorStart->addWeek();
            $cursorEnd->addWeek();
            $guard++;
        }

        return $occurrences;
    }

    private function assertInterval(Carbon $starts, Carbon $ends): void
    {
        if ($ends->lessThanOrEqualTo($starts)) {
            throw ValidationException::withMessages([
                'ends_at' => 'L\'heure de fin doit être après l\'heure de début.',
            ]);
        }
    }

    private function assertGroup(mixed $groupId, AcademicYear $year): void
    {
        $belongs = StudentGroup::query()->whereKey($groupId)->where('academic_year_id', $year->id)->exists();

        if (! $belongs) {
            throw ValidationException::withMessages([
                'student_group_id' => 'Ce groupe n\'appartient pas à l\'année académique sélectionnée.',
            ]);
        }
    }
}
