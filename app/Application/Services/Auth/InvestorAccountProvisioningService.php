<?php

namespace App\Application\Services\Auth;

use App\Models\User;
use App\Models\Investor;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class InvestorAccountProvisioningService
{
    public function createUserForInvestor(
        Investor $investor,
        string $name,
        string $email,
        string $phone,
        string $password
    ): User {
        return DB::transaction(function () use ($investor, $name, $email, $phone, $password) {
            $existing = User::query()->where('email', $email)->first();

            if ($existing) {
                throw ValidationException::withMessages([
                    'email' => ['A user with this email already exists.'],
                ]);
            }

            $role = Role::query()
                ->where('name', 'investor')
                ->where('guard_name', 'web')
                ->first();

            if (! $role) {
                throw ValidationException::withMessages([
                    'role' => ['Investor role does not exist.'],
                ]);
            }

            $user = User::create([
                'uuid' => (string) Str::uuid(),
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password' => $password,
                'is_active' => true,
                'investor_id' => $investor->id,
            ]);

            $user->assignRole($role);

            return $user->fresh();
        });
    }
}