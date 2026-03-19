<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class OperationalRolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Investor management
            'view investors',
            'create investors',
            'update investors',
            'assist investor onboarding',
            'view investor profiles',

            // Investor self-service
            'view own profile',
            'upload own documents',
            'view own documents',
            'trigger own identity verification',
            'view own kyc status',

            // Documents
            'upload investor documents',
            'view investor documents',
            'verify investor documents',
            'reject investor documents',
            'reupload investor documents',

            // Identity / KYC
            'trigger investor identity verification',
            'trigger director identity verification',
            'view identity verifications',
            'review kyc',
            'approve kyc',
            'reject kyc',
            'escalate kyc',
            'override kyc',

            // Corporate onboarding
            'create company directors',
            'update company directors',
            'verify company directors',

            // Config / administration
            'manage investor categories',
            'manage document types',
            'manage document requirements',
            'manage roles',
            'manage permissions',
            'assign roles',

            // Audit / oversight
            'view audit logs',
            'view approvals',
            'approve workflow actions',
            'view compliance reports',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $roles = [
            'super_admin' => $permissions,

            'operations_officer' => [
                'view investors',
                'create investors',
                'update investors',
                'assist investor onboarding',
                'view investor profiles',
                'upload investor documents',
                'view investor documents',
                'create company directors',
                'update company directors',
                'view identity verifications',
            ],

            'kyc_officer' => [
                'view investors',
                'view investor profiles',
                'view investor documents',
                'verify investor documents',
                'reject investor documents',
                'view identity verifications',
                'review kyc',
                'escalate kyc',
            ],

            'compliance_officer' => [
                'view investors',
                'view investor profiles',
                'view investor documents',
                'verify investor documents',
                'reject investor documents',
                'view identity verifications',
                'review kyc',
                'approve kyc',
                'reject kyc',
                'escalate kyc',
                'view approvals',
                'view compliance reports',
            ],

            'fund_manager' => [
                'view investors',
                'view investor profiles',
                'view investor documents',
                'view identity verifications',
                'approve workflow actions',
                'approve kyc',
                'override kyc',
                'view approvals',
                'view compliance reports',
            ],

            'auditor' => [
                'view investors',
                'view investor profiles',
                'view investor documents',
                'view identity verifications',
                'view audit logs',
                'view approvals',
                'view compliance reports',
            ],

            'investor' => [
                'view own profile',
                'upload own documents',
                'view own documents',
                'trigger own identity verification',
                'view own kyc status',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($rolePermissions);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}