<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;
use App\Services\Visibility;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class AnnouncementPolicy
{
    use HandlesAuthorization;

    public function __construct(private Visibility $visibility) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('announcements.view');
    }

    public function view(User $user, Announcement $announcement): Response|bool
    {
        if ($user->hasPermission('announcements.manage')) {
            return true;
        }

        $visible = $this->visibility
            ->announcements($user, false)
            ->whereKey($announcement->id)
            ->exists();

        return $visible ? true : $this->denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('announcements.manage');
    }

    public function update(User $user, Announcement $announcement): bool
    {
        return $user->hasPermission('announcements.manage');
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $user->hasPermission('announcements.manage');
    }
}
