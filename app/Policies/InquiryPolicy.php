<?php

namespace App\Policies;

use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class InquiryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('communication.manage')
            || $user->hasAnyRole(['parent', 'student', 'teacher']);
    }

    public function view(User $user, Inquiry $inquiry): Response|bool
    {
        if ($user->hasPermission('communication.manage') || $inquiry->author_id === $user->id) {
            return true;
        }

        return $this->denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['parent', 'student', 'teacher']) || $user->hasPermission('communication.manage');
    }

    public function reply(User $user, Inquiry $inquiry): bool
    {
        return $user->hasPermission('communication.manage') || $inquiry->author_id === $user->id;
    }
}
