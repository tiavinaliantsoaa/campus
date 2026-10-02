<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;
use App\Services\Visibility;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class StudentPolicy
{
    use HandlesAuthorization;

    public function __construct(private Visibility $visibility) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('students.view');
    }

    public function view(User $user, Student $student): Response|bool
    {
        if ($this->visibility->seesStudent($user, $student)) {
            return true;
        }

        return $this->denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('students.manage');
    }

    public function update(User $user, Student $student): bool
    {
        return $user->hasPermission('students.manage');
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->hasPermission('students.manage');
    }
}
