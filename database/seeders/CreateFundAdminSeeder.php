<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class CreateFundAdminSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::where('name', 'fund-admin')->first();

        $user = User::updateOrCreate(
            ['email' => 'fundadmin@uutprf.com'],
            [
                'uuid' => Str::uuid()->toString(),
                'name' => 'Fund Admin',
                'phone' => '255700000001',
                'password' => 'Password123!',
                'is_active' => true,
                'investor_id' => null,
            ]
        );

        if ($role) {
            $user->syncRoles([$role->name]);
        }
    }
}