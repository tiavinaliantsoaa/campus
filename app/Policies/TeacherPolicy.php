<?php

namespace App\Policies;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class TeacherPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('teachers.view');
    }

    public function view(User $user, Teacher $teacher): Response|bool
    {
        if ($user->hasPermission('teachers.view')) {
            return true;
        }

        if ($user->teacher && $user->teacher->is($teacher)) {
            return true;
        }

        return $this->denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('teachers.manage');
    }

    public function update(User $user, Teacher $teacher): bool
    {
        return $user->hasPermission('teachers.manage');
    }

    public function delete(User $user, Teacher $teacher): bool
    {
        return $user->hasPermission('teachers.manage');
    }
}
