<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

      $permissions = [
        'create investors',
        'view investors',
        'update investors',
        'approve investors',
        'approve kyc',
        'upload investor documents',
        'view investor documents',
        'verify investor documents',
        'manage users',
        'manage roles',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $fundAdmin = Role::firstOrCreate(['name' => 'fund-admin']);
        $operations = Role::firstOrCreate(['name' => 'operations']);
        $compliance = Role::firstOrCreate(['name' => 'compliance']);
        $finance = Role::firstOrCreate(['name' => 'finance']);
        $trustee = Role::firstOrCreate(['name' => 'trustee']);
        $customerService = Role::firstOrCreate(['name' => 'customer-service']);
        $distributor = Role::firstOrCreate(['name' => 'distributor']);
        $investor = Role::firstOrCreate(['name' => 'investor']);

        $superAdmin->givePermissionTo(Permission::all());

        $fundAdmin->givePermissionTo([
            'upload investor documents',
            'view investor documents',
            'verify investor documents',
            'create investors',
            'view investors',
            'update investors',
            'approve investors',
            'manage users',
        ]);

        $operations->givePermissionTo([
            'create investors',
            'view investors',
            'update investors',
        ]);

        $compliance->givePermissionTo([
            'view investors',
            'approve investors',
            'approve kyc',
        ]);

        $finance->givePermissionTo([
            'view investors',
        ]);

        $trustee->givePermissionTo([
            'view investors',
        ]);

        $customerService->givePermissionTo([
            'create investors',
            'view investors',
        ]);

        $distributor->givePermissionTo([
            'create investors',
            'view investors',
        ]);

        $investor->givePermissionTo([]);
    }
}