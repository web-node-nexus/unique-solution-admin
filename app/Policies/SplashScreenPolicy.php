<?php

namespace App\Policies;

use App\Models\SplashScreen;
use App\Models\User;

class SplashScreenPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('banners.view');
    }

    public function view(User $user, SplashScreen $splashScreen): bool
    {
        return $user->can('banners.view');
    }

    public function create(User $user): bool
    {
        return $user->can('banners.create');
    }

    public function update(User $user, SplashScreen $splashScreen): bool
    {
        return $user->can('banners.update');
    }

    public function delete(User $user, SplashScreen $splashScreen): bool
    {
        return $user->can('banners.delete');
    }
}
