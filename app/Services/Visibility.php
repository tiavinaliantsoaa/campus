<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\Document;
use App\Models\Enrollment;
use App\Models\FeeInstallment;
use App\Models\GroupAssignment;
use App\Models\Guardian;
use App\Models\Inquiry;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class Visibility
{
    /**
     * @return Builder<Student>
     */
    public function students(User $user): Builder
    {
        $query = Student::query();

        if ($user->hasAnyRole(['administration', 'pedagogical'])) {
            return $query;
        }

        $ids = $this->studentIds($user);

        if ($ids->isEmpty()) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereIn('students.id', $ids);
    }

    public function seesStudent(User $user, Student $student): bool
    {
        return $this->students($user)->whereKey($student->id)->exists();
    }

    /**
     * @return Collection<int, int>
     */
    public function studentIds(User $user): Collection
    {
        $ids = collect();

        if ($user->teacher) {
            $groupIds = GroupAssignment::query()
                ->where('teacher_id', $user->teacher->id)
                ->pluck('student_group_id');

            $ids = $ids->merge(
                Enrollment::query()->whereIn('student_group_id', $groupIds)->pluck('student_id')
            );
        }

        if ($user->student) {
            $ids->push($user->student->id);
        }

        if ($user->guardian) {
            $ids = $ids->merge($user->guardian->students()->pluck('students.id'));
        }

        return $ids->filter()->unique()->values();
    }

    /**
     * @return Collection<int, int>
     */
    public function groupIds(User $user, ?AcademicYear $year = null): Collection
    {
        $ids = collect();

        if ($user->teacher) {
            $assignments = GroupAssignment::query()->where('teacher_id', $user->teacher->id);

            if ($year) {
                $assignments->whereHas('group', fn (Builder $query) => $query->where('academic_year_id', $year->id));
            }

            $ids = $ids->merge($assignments->pluck('student_group_id'));
        }

        $studentIds = collect();

        if ($user->student) {
            $studentIds->push($user->student->id);
        }

        if ($user->guardian) {
            $studentIds = $studentIds->merge($user->guardian->students()->pluck('students.id'));
        }

        if ($studentIds->isNotEmpty()) {
            $enrollments = Enrollment::query()->whereIn('student_id', $studentIds);

            if ($year) {
                $enrollments->where('academic_year_id', $year->id);
            }

            $ids = $ids->merge($enrollments->pluck('student_group_id'));
        }

        return $ids->filter()->unique()->values();
    }

    /**
     * @return Builder<Course>
     */
    public function courses(User $user, AcademicYear $year): Builder
    {
        $query = Course::query()->where('academic_year_id', $year->id);

        if ($user->hasAnyRole(['administration', 'pedagogical'])) {
            return $query;
        }

        $groupIds = $this->groupIds($user, $year);

        return $query->where(function (Builder $query) use ($user, $groupIds): void {
            $restricted = false;

            if ($user->teacher) {
                $query->orWhere('teacher_id', $user->teacher->id);
                $restricted = true;
            }

            if ($groupIds->isNotEmpty()) {
                $query->orWhereIn('student_group_id', $groupIds);
                $restricted = true;
            }

            if (! $restricted) {
                $query->whereRaw('0 = 1');
            }
        });
    }

    /**
     * @return Builder<Assessment>
     */
    public function assessments(User $user, AcademicYear $year): Builder
    {
        $query = Assessment::query()->where('academic_year_id', $year->id);

        if ($user->hasAnyRole(['administration', 'pedagogical'])) {
            return $query;
        }

        if ($user->hasPermission('grades.enter') && $user->teacher && ! $user->student && ! $user->guardian) {
            return $query->where('teacher_id', $user->teacher->id);
        }

        $studentIds = $this->studentIds($user);

        if ($user->teacher) {
            return $query->where(function (Builder $query) use ($user, $studentIds): void {
                $query->where('teacher_id', $user->teacher->id)
                    ->orWhereIn('student_group_id', Enrollment::query()->whereIn('student_id', $studentIds)->select('student_group_id'));
            });
        }

        if ($studentIds->isEmpty()) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where('status', 'validated')
            ->whereIn('student_group_id', Enrollment::query()->whereIn('student_id', $studentIds)->where('academic_year_id', $year->id)->select('student_group_id'));
    }

    /**
     * @return Builder<Document>
     */
    public function documents(User $user): Builder
    {
        $query = Document::query();

        if ($user->hasAnyRole(['administration', 'pedagogical'])) {
            return $query;
        }

        $studentIds = $this->studentIds($user);

        return $query->whereNull('archived_at')->where(function (Builder $query) use ($user, $studentIds): void {
            $query->where('uploaded_by', $user->id);

            if ($user->teacher) {
                $query->orWhere(function (Builder $query) use ($user): void {
                    $query->where('documentable_type', Teacher::class)
                        ->where('documentable_id', $user->teacher->id);
                });
            }

            if ($studentIds->isNotEmpty()) {
                $query->orWhere(function (Builder $query) use ($studentIds): void {
                    $query->where('documentable_type', Student::class)
                        ->whereIn('documentable_id', $studentIds);
                });
            }
        });
    }

    /**
     * @return Builder<FeeInstallment>
     */
    public function installments(User $user, AcademicYear $year): Builder
    {
        $query = FeeInstallment::query()->where('academic_year_id', $year->id);

        if ($user->hasPermission('finance.manage') || $user->hasRole('administration')) {
            return $query;
        }

        $studentIds = $this->studentIds($user);

        if ($studentIds->isEmpty()) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereIn('student_id', $studentIds);
    }

    /**
     * @return Builder<Payment>
     */
    public function payments(User $user, AcademicYear $year): Builder
    {
        $query = Payment::query()->where('academic_year_id', $year->id);

        if ($user->hasPermission('finance.manage') || $user->hasRole('administration')) {
            return $query;
        }

        $studentIds = $this->studentIds($user);

        if ($studentIds->isEmpty()) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereIn('student_id', $studentIds);
    }

    /**
     * @return Builder<Announcement>
     */
    public function announcements(User $user, bool $manage): Builder
    {
        if ($manage) {
            return Announcement::query();
        }

        $roleIds = $user->roles->pluck('id')->all();
        $groupIds = $this->groupIds($user)->all();

        return Announcement::query()->visibleTo($roleIds, $groupIds);
    }

    /**
     * @return Builder<Inquiry>
     */
    public function inquiries(User $user): Builder
    {
        $query = Inquiry::query();

        if ($user->hasPermission('communication.manage')) {
            return $query;
        }

        return $query->where('author_id', $user->id);
    }

    public function selectedChild(User $user): ?Student
    {
        $guardian = $user->guardian;

        if (! $guardian instanceof Guardian) {
            return null;
        }

        $selected = session('guardian_student_id');

        $students = $guardian->students()->orderBy('last_name')->orderBy('first_name')->get();

        if ($selected) {
            $match = $students->firstWhere('id', (int) $selected);

            if ($match) {
                return $match;
            }
        }

        return $students->first();
    }
}
