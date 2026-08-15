<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAnyCustomers(User $user): bool
    {
        return $user->can('customers.view');
    }

    public function viewCustomer(User $user, User $customer): bool
    {
        return $user->can('customers.view');
    }

    public function updateCustomer(User $user, User $customer): bool
    {
        return $user->can('customers.update');
    }

    public function exportCustomers(User $user): bool
    {
        return $user->can('customers.export');
    }

    public function viewAnyStaff(User $user): bool
    {
        return $user->can('staff.view');
    }

    public function viewStaff(User $user, User $staff): bool
    {
        return $user->can('staff.view');
    }

    public function createStaff(User $user): bool
    {
        return $user->can('staff.create');
    }

    public function updateStaff(User $user, User $staff): bool
    {
        return $user->can('staff.update');
    }

    public function deleteStaff(User $user, User $staff): bool
    {
        return $user->can('staff.delete');
    }
}
