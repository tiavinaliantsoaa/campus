<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;
use App\Services\Visibility;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class CoursePolicy
{
    use HandlesAuthorization;

    public function __construct(private Visibility $visibility) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('timetable.view');
    }

    public function view(User $user, Course $course): Response|bool
    {
        if ($user->hasAnyRole(['administration', 'pedagogical'])) {
            return true;
        }

        $visible = $this->visibility->courses($user, $course->academicYear)->whereKey($course->id)->exists();

        return $visible ? true : $this->denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('timetable.manage');
    }

    public function update(User $user, Course $course): bool
    {
        return $user->hasPermission('timetable.manage');
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->hasPermission('timetable.manage');
    }

    public function recordAttendance(User $user, Course $course): Response|bool
    {
        if (! $user->hasPermission('attendance.record')) {
            return $this->denyAsNotFound();
        }

        if ($user->hasAnyRole(['administration', 'pedagogical'])) {
            return true;
        }

        if ($user->teacher && $user->teacher->id === $course->teacher_id) {
            return true;
        }

        return $this->denyAsNotFound();
    }
}
