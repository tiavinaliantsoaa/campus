<?php

namespace App\Services;

use App\Enums\AssessmentStatus;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Gradebook
{
    public function __construct(private AcademicCalendar $calendar) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, AcademicYear $year, User $actor): Assessment
    {
        $this->calendar->ensureOpen($year);

        return Assessment::query()->create([
            'academic_year_id' => $year->id,
            'semester_id' => $data['semester_id'] ?? null,
            'student_group_id' => $data['student_group_id'],
            'subject_id' => $data['subject_id'],
            'teacher_id' => $data['teacher_id'],
            'title' => $data['title'],
            'type' => $data['type'],
            'coefficient' => $data['coefficient'],
            'max_score' => $data['max_score'],
            'assessed_on' => $data['assessed_on'],
            'status' => AssessmentStatus::Draft,
        ]);
    }

    /**
     * @param  array<int|string, ?string>  $scores
     * @param  array<int|string, ?string>  $comments
     */
    public function saveScores(Assessment $assessment, array $scores, array $comments): void
    {
        $this->calendar->ensureOpen($assessment->academicYear);

        if ($assessment->isLocked()) {
            throw ValidationException::withMessages([
                'scores' => 'Cette évaluation est validée. Les notes sont verrouillées.',
            ]);
        }

        $students = Enrollment::query()
            ->where('academic_year_id', $assessment->academic_year_id)
            ->where('student_group_id', $assessment->student_group_id)
            ->pluck('student_id');

        DB::transaction(function () use ($assessment, $scores, $comments, $students): void {
            foreach ($students as $studentId) {
                $raw = $scores[$studentId] ?? $scores[(string) $studentId] ?? null;
                $score = $raw === null || $raw === '' ? null : (string) $raw;

                if ($score !== null) {
                    if (! is_numeric($score) || bccomp($score, '0', 2) < 0 || bccomp($score, (string) $assessment->max_score, 2) > 0) {
                        throw ValidationException::withMessages([
                            'scores' => 'Chaque note doit être comprise entre 0 et '.$assessment->max_score.'.',
                        ]);
                    }
                }

                Grade::query()->updateOrCreate(
                    ['assessment_id' => $assessment->id, 'student_id' => $studentId],
                    [
                        'score' => $score,
                        'comment' => $comments[$studentId] ?? $comments[(string) $studentId] ?? null,
                    ],
                );
            }
        });
    }

    public function submit(Assessment $assessment): void
    {
        $this->calendar->ensureOpen($assessment->academicYear);

        if ($assessment->status !== AssessmentStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => 'Seule une évaluation en brouillon peut être soumise.',
            ]);
        }

        $assessment->update(['status' => AssessmentStatus::Submitted]);
    }

    public function validateAssessment(Assessment $assessment, User $actor): void
    {
        $this->calendar->ensureOpen($assessment->academicYear);

        if ($assessment->status === AssessmentStatus::Validated) {
            return;
        }

        $assessment->update([
            'status' => AssessmentStatus::Validated,
            'validated_by' => $actor->id,
            'validated_at' => now(),
        ]);
    }

    public function reopen(Assessment $assessment): void
    {
        $this->calendar->ensureOpen($assessment->academicYear);

        $assessment->update([
            'status' => AssessmentStatus::Draft,
            'validated_by' => null,
            'validated_at' => null,
        ]);
    }

    public function average(Student $student, AcademicYear $year, ?int $semesterId = null): ?string
    {
        $grades = Grade::query()
            ->where('student_id', $student->id)
            ->whereNotNull('score')
            ->whereHas('assessment', function ($query) use ($year, $semesterId): void {
                $query->where('academic_year_id', $year->id)
                    ->where('status', AssessmentStatus::Validated);

                if ($semesterId) {
                    $query->where('semester_id', $semesterId);
                }
            })
            ->with('assessment')
            ->get();

        return $this->weighted($grades);
    }

    /**
     * @return Collection<int, array{student: Student, average: ?string, rank: ?int}>
     */
    public function ranking(int $groupId, AcademicYear $year): Collection
    {
        $students = Student::query()
            ->whereHas('enrollments', fn ($query) => $query->where('academic_year_id', $year->id)->where('student_group_id', $groupId))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $rows = $students->map(fn (Student $student): array => [
            'student' => $student,
            'average' => $this->average($student, $year),
            'rank' => null,
        ])->sortByDesc(fn (array $row): float => (float) ($row['average'] ?? -1))->values();

        $rank = 0;
        $previous = null;

        return $rows->map(function (array $row) use (&$rank, &$previous): array {
            if ($row['average'] === null) {
                return $row;
            }

            if ($previous !== $row['average']) {
                $rank++;
                $previous = $row['average'];
            }

            $row['rank'] = $rank;

            return $row;
        });
    }

    /**
     * @param  Collection<int, Grade>  $grades
     */
    private function weighted(Collection $grades): ?string
    {
        if ($grades->isEmpty()) {
            return null;
        }

        $weighted = '0';
        $coefficient = '0';

        foreach ($grades as $grade) {
            $max = (string) $grade->assessment->max_score;

            if (bccomp($max, '0', 2) <= 0) {
                continue;
            }

            $normalized = bcdiv(bcmul((string) $grade->score, '20', 4), $max, 4);
            $weight = (string) $grade->assessment->coefficient;
            $weighted = bcadd($weighted, bcmul($normalized, $weight, 4), 4);
            $coefficient = bcadd($coefficient, $weight, 4);
        }

        if (bccomp($coefficient, '0', 4) === 0) {
            return null;
        }

        return bcdiv($weighted, $coefficient, 2);
    }
}
