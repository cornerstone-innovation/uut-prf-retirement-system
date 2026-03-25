<?php

namespace App\Application\Services\Auth;

use App\Models\User;
use App\Models\Investor;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvestorAccountProvisioningService
{
    public function createInvestorUser(
        Investor $investor,
        string $email,
        string $phone,
        string $password
    ): User {
        return DB::transaction(function () use ($investor, $email, $phone, $password) {
            $existingUser = User::query()->where('email', $email)->first();

            if ($existingUser) {
                throw ValidationException::withMessages([
                    'email' => ['An account with this email already exists.'],
                ]);
            }

            $user = User::create([
                'uuid' => (string) Str::uuid(),
                'name' => $investor->full_name ?: $investor->company_name ?: 'Investor',
                'email' => $email,
                'phone' => $phone,
                'password' => $password,
                'is_active' => true,
                'investor_id' => $investor->id,
            ]);

            $user->assignRole('investor');

            return $user->fresh();
        });
    }
}