<?php

namespace App\Policies;

use App\Models\User;
use App\Models\InvestorDocument;

class InvestorDocumentPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view investor documents');
    }

    public function view(User $user, InvestorDocument $investorDocument): bool
    {
        return $user->can('view investor documents');
    }

    public function create(User $user): bool
    {
        return $user->can('upload investor documents');
    }

    public function verify(User $user, InvestorDocument $investorDocument): bool
    {
        return $user->can('verify investor documents');
    }
}