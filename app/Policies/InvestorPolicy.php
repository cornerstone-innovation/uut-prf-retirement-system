<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Investor;

class InvestorPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view investors');
    }

    public function view(User $user, Investor $investor): bool
    {
        return $user->can('view investors');
    }

    public function create(User $user): bool
    {
        return $user->can('create investors');
    }

    public function update(User $user, Investor $investor): bool
    {
        return $user->can('update investors');
    }
}