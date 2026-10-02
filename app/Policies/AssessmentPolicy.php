<?php

namespace App\Policies;

use App\Models\Assessment;
use App\Models\User;
use App\Services\Visibility;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class AssessmentPolicy
{
    use HandlesAuthorization;

    public function __construct(private Visibility $visibility) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('grades.view');
    }

    public function view(User $user, Assessment $assessment): Response|bool
    {
        $visible = $this->visibility->assessments($user, $assessment->academicYear)->whereKey($assessment->id)->exists();

        return $visible ? true : $this->denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('grades.enter');
    }

    public function enter(User $user, Assessment $assessment): Response|bool
    {
        if (! $user->hasPermission('grades.enter')) {
            return false;
        }

        if ($user->hasAnyRole(['administration', 'pedagogical'])) {
            return true;
        }

        if ($user->teacher && $user->teacher->id === $assessment->teacher_id) {
            return true;
        }

        return $this->denyAsNotFound();
    }

    public function validate(User $user, Assessment $assessment): bool
    {
        return $user->hasPermission('grades.validate');
    }
}
