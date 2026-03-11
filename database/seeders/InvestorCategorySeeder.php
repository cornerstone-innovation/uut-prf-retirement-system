<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class InvestorCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Individual',
                'code' => 'individual',
                'description' => 'Single adult individual investor',
                'requires_guardian' => false,
                'requires_custodian' => false,
                'requires_ubo' => false,
                'requires_authorized_signatories' => false,
                'allows_joint_holding' => false,
                'is_minor_category' => false,
                'sort_order' => 1,
            ],
            [
                'name' => 'Joint Individual',
                'code' => 'joint',
                'description' => 'Joint individual investor account',
                'requires_guardian' => false,
                'requires_custodian' => false,
                'requires_ubo' => false,
                'requires_authorized_signatories' => false,
                'allows_joint_holding' => true,
                'is_minor_category' => false,
                'sort_order' => 2,
            ],
            [
                'name' => 'Minor',
                'code' => 'minor',
                'description' => 'Minor investor represented by guardian',
                'requires_guardian' => true,
                'requires_custodian' => false,
                'requires_ubo' => false,
                'requires_authorized_signatories' => false,
                'allows_joint_holding' => false,
                'is_minor_category' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Corporate',
                'code' => 'corporate',
                'description' => 'Corporate or company investor',
                'requires_guardian' => false,
                'requires_custodian' => false,
                'requires_ubo' => true,
                'requires_authorized_signatories' => true,
                'allows_joint_holding' => false,
                'is_minor_category' => false,
                'sort_order' => 4,
            ],
            [
                'name' => 'Non-Resident Individual',
                'code' => 'non_resident_individual',
                'description' => 'Foreign or diaspora individual investor',
                'requires_guardian' => false,
                'requires_custodian' => true,
                'requires_ubo' => false,
                'requires_authorized_signatories' => false,
                'allows_joint_holding' => false,
                'is_minor_category' => false,
                'sort_order' => 5,
            ],
            [
                'name' => 'Non-Resident Entity',
                'code' => 'non_resident_entity',
                'description' => 'Foreign corporate or institutional investor',
                'requires_guardian' => false,
                'requires_custodian' => true,
                'requires_ubo' => true,
                'requires_authorized_signatories' => true,
                'allows_joint_holding' => false,
                'is_minor_category' => false,
                'sort_order' => 6,
            ],
        ];

        foreach ($categories as $category) {
            DB::table('investor_categories')->updateOrInsert(
                ['code' => $category['code']],
                array_merge($category, [
                    'uuid' => (string) Str::uuid(),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}