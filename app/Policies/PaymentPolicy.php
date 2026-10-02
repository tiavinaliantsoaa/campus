<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use App\Services\Visibility;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class PaymentPolicy
{
    use HandlesAuthorization;

    public function __construct(private Visibility $visibility) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('finance.view');
    }

    public function view(User $user, Payment $payment): Response|bool
    {
        if ($user->hasPermission('finance.manage')) {
            return true;
        }

        return $this->visibility->seesStudent($user, $payment->student)
            ? true
            : $this->denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('finance.manage');
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->hasPermission('finance.manage');
    }
}
