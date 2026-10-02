<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Visibility;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class DocumentPolicy
{
    use HandlesAuthorization;

    public function __construct(private Visibility $visibility) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('documents.view');
    }

    public function view(User $user, Document $document): Response|bool
    {
        $visible = $this->visibility->documents($user)->whereKey($document->id)->exists();

        return $visible ? true : $this->denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('documents.manage')
            || $user->hasPermission('documents.view') && $user->teacher;
    }

    public function archive(User $user, Document $document): bool
    {
        return $user->hasPermission('documents.manage');
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->hasPermission('documents.manage') || $document->uploaded_by === $user->id;
    }

    public function uploadForStudent(User $user, Student $student): bool
    {
        if ($user->hasPermission('documents.manage')) {
            return true;
        }

        return $user->teacher && $this->visibility->seesStudent($user, $student);
    }

    public function uploadForTeacher(User $user, Teacher $teacher): bool
    {
        if ($user->hasPermission('documents.manage')) {
            return true;
        }

        return $user->teacher && $user->teacher->is($teacher);
    }
}
