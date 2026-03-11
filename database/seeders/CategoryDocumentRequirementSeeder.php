<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoryDocumentRequirementSeeder extends Seeder
{
    public function run(): void
    {
        $categories = DB::table('investor_categories')->pluck('id', 'code');
        $documents = DB::table('document_types')->pluck('id', 'code');

        $requirements = [
            // Individual
            ['category' => 'individual', 'document' => 'national_id', 'required' => true, 'order' => 1],
            ['category' => 'individual', 'document' => 'proof_of_address', 'required' => true, 'order' => 2],
            ['category' => 'individual', 'document' => 'bank_proof', 'required' => true, 'order' => 3],
            ['category' => 'individual', 'document' => 'source_of_funds_declaration', 'required' => true, 'order' => 4],
            ['category' => 'individual', 'document' => 'tin_certificate', 'required' => false, 'order' => 5],

            // Joint
            ['category' => 'joint', 'document' => 'national_id', 'required' => true, 'multiple' => true, 'min_count' => 2, 'order' => 1],
            ['category' => 'joint', 'document' => 'proof_of_address', 'required' => true, 'order' => 2],
            ['category' => 'joint', 'document' => 'bank_proof', 'required' => true, 'order' => 3],
            ['category' => 'joint', 'document' => 'source_of_funds_declaration', 'required' => true, 'order' => 4],

            // Minor
            ['category' => 'minor', 'document' => 'birth_certificate', 'required' => true, 'order' => 1],
            ['category' => 'minor', 'document' => 'guardian_id', 'required' => true, 'order' => 2],
            ['category' => 'minor', 'document' => 'proof_of_address', 'required' => true, 'order' => 3],
            ['category' => 'minor', 'document' => 'bank_proof', 'required' => true, 'order' => 4],

            // Corporate
            ['category' => 'corporate', 'document' => 'certificate_of_incorporation', 'required' => true, 'order' => 1],
            ['category' => 'corporate', 'document' => 'memorandum_and_articles', 'required' => true, 'order' => 2],
            ['category' => 'corporate', 'document' => 'board_resolution', 'required' => true, 'order' => 3],
            ['category' => 'corporate', 'document' => 'tin_certificate', 'required' => true, 'order' => 4],
            ['category' => 'corporate', 'document' => 'bank_proof', 'required' => true, 'order' => 5],
            ['category' => 'corporate', 'document' => 'authorized_signatory_id', 'required' => true, 'multiple' => true, 'min_count' => 1, 'order' => 6],
            ['category' => 'corporate', 'document' => 'source_of_funds_declaration', 'required' => true, 'order' => 7],

            // Non-resident individual
            ['category' => 'non_resident_individual', 'document' => 'passport', 'required' => true, 'order' => 1],
            ['category' => 'non_resident_individual', 'document' => 'proof_of_address', 'required' => true, 'order' => 2],
            ['category' => 'non_resident_individual', 'document' => 'bank_proof', 'required' => true, 'order' => 3],
            ['category' => 'non_resident_individual', 'document' => 'custodian_letter', 'required' => true, 'order' => 4],
            ['category' => 'non_resident_individual', 'document' => 'source_of_funds_declaration', 'required' => true, 'order' => 5],

            // Non-resident entity
            ['category' => 'non_resident_entity', 'document' => 'certificate_of_incorporation', 'required' => true, 'order' => 1],
            ['category' => 'non_resident_entity', 'document' => 'board_resolution', 'required' => true, 'order' => 2],
            ['category' => 'non_resident_entity', 'document' => 'authorized_signatory_id', 'required' => true, 'multiple' => true, 'min_count' => 1, 'order' => 3],
            ['category' => 'non_resident_entity', 'document' => 'bank_proof', 'required' => true, 'order' => 4],
            ['category' => 'non_resident_entity', 'document' => 'custodian_letter', 'required' => true, 'order' => 5],
            ['category' => 'non_resident_entity', 'document' => 'source_of_funds_declaration', 'required' => true, 'order' => 6],
        ];

        foreach ($requirements as $requirement) {
            DB::table('category_document_requirements')->updateOrInsert(
                [
                    'investor_category_id' => $categories[$requirement['category']],
                    'document_type_id' => $documents[$requirement['document']],
                ],
                [
                    'is_required' => $requirement['required'] ?? true,
                    'is_multiple_allowed' => $requirement['multiple'] ?? false,
                    'minimum_required_count' => $requirement['min_count'] ?? null,
                    'requires_verification' => true,
                    'is_visible_on_onboarding' => true,
                    'applies_to_resident' => null,
                    'applies_to_non_resident' => null,
                    'applies_to_minor' => null,
                    'applies_to_joint' => null,
                    'applies_to_corporate' => null,
                    'notes' => null,
                    'sort_order' => $requirement['order'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}