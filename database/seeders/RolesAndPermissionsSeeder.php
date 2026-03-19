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
            // Investor management
            'create investors',
            'view investors',
            'update investors',
            'approve investors',

            // KYC
            'approve kyc',
            'upload investor documents',
            'view investor documents',
            'verify investor documents',

            // System
            'manage users',
            'manage roles',

            // NAV Engine
            'view nav records',
            'create nav records',
            'approve nav records',
            'publish nav records',
            'manage cutoff rules',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Roles
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $fundAdmin = Role::firstOrCreate(['name' => 'fund-admin']);
        $operations = Role::firstOrCreate(['name' => 'operations']);
        $compliance = Role::firstOrCreate(['name' => 'compliance']);
        $finance = Role::firstOrCreate(['name' => 'finance']);
        $trustee = Role::firstOrCreate(['name' => 'trustee']);
        $customerService = Role::firstOrCreate(['name' => 'customer-service']);
        $distributor = Role::firstOrCreate(['name' => 'distributor']);
        $investor = Role::firstOrCreate(['name' => 'investor']);

        /*
        |--------------------------------------------------------------------------
        | Super Admin → everything
        |--------------------------------------------------------------------------
        */
        $superAdmin->syncPermissions(Permission::all());

        /*
        |--------------------------------------------------------------------------
        | Fund Admin (Full operational control incl NAV)
        |--------------------------------------------------------------------------
        */
        $fundAdmin->syncPermissions([
            'create investors',
            'view investors',
            'update investors',
            'approve investors',

            'upload investor documents',
            'view investor documents',
            'verify investor documents',

            'manage users',

            // NAV FULL CONTROL
            'view nav records',
            'create nav records',
            'approve nav records',
            'publish nav records',
            'manage cutoff rules',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Operations (NAV maker role)
        |--------------------------------------------------------------------------
        */
        $operations->syncPermissions([
            'create investors',
            'view investors',
            'update investors',

            // NAV → CREATE ONLY (maker)
            'view nav records',
            'create nav records',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Compliance (NAV approver role)
        |--------------------------------------------------------------------------
        */
        $compliance->syncPermissions([
            'view investors',
            'approve investors',
            'approve kyc',

            // NAV → APPROVER
            'view nav records',
            'approve nav records',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Finance (viewer)
        |--------------------------------------------------------------------------
        */
        $finance->syncPermissions([
            'view investors',
            'view nav records',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Trustee (viewer + oversight)
        |--------------------------------------------------------------------------
        */
        $trustee->syncPermissions([
            'view investors',
            'view nav records',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Customer Service
        |--------------------------------------------------------------------------
        */
        $customerService->syncPermissions([
            'create investors',
            'view investors',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Distributor
        |--------------------------------------------------------------------------
        */
        $distributor->syncPermissions([
            'create investors',
            'view investors',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Investor (no admin permissions)
        |--------------------------------------------------------------------------
        */
        $investor->syncPermissions([]);
    }
}