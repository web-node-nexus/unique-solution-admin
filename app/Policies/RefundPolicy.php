<?php

namespace App\Policies;

use App\Models\Refund;
use App\Models\User;

class RefundPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('refunds.view');
    }

    public function view(User $user, Refund $refund): bool
    {
        return $user->can('refunds.view');
    }

    public function create(User $user): bool
    {
        return $user->can('refunds.create');
    }

    public function update(User $user, Refund $refund): bool
    {
        return $user->can('refunds.update');
    }

    public function delete(User $user, Refund $refund): bool
    {
        return $user->can('refunds.delete');
    }

    public function approve(User $user, Refund $refund): bool
    {
        return $user->can('refunds.approve');
    }
}
