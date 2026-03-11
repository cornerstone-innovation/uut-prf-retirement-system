<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $documentTypes = [
            [
                'name' => 'National ID',
                'code' => 'national_id',
                'description' => 'Government-issued national ID',
                'allowed_extensions' => 'pdf,jpg,jpeg,png',
                'max_file_size_kb' => 5120,
                'requires_expiry_date' => false,
                'requires_issue_date' => false,
                'requires_document_number' => true,
                'requires_manual_verification' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Passport',
                'code' => 'passport',
                'description' => 'Valid passport document',
                'allowed_extensions' => 'pdf,jpg,jpeg,png',
                'max_file_size_kb' => 5120,
                'requires_expiry_date' => true,
                'requires_issue_date' => false,
                'requires_document_number' => true,
                'requires_manual_verification' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Proof of Address',
                'code' => 'proof_of_address',
                'description' => 'Utility bill, tenancy, or address confirmation',
                'allowed_extensions' => 'pdf,jpg,jpeg,png',
                'max_file_size_kb' => 5120,
                'requires_expiry_date' => false,
                'requires_issue_date' => true,
                'requires_document_number' => false,
                'requires_manual_verification' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'TIN Certificate',
                'code' => 'tin_certificate',
                'description' => 'Tax identification certificate',
                'allowed_extensions' => 'pdf,jpg,jpeg,png',
                'max_file_size_kb' => 5120,
                'requires_expiry_date' => false,
                'requires_issue_date' => false,
                'requires_document_number' => true,
                'requires_manual_verification' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Bank Account Proof',
                'code' => 'bank_proof',
                'description' => 'Bank confirmation letter, statement, or account proof',
                'allowed_extensions' => 'pdf,jpg,jpeg,png',
                'max_file_size_kb' => 5120,
                'requires_expiry_date' => false,
                'requires_issue_date' => true,
                'requires_document_number' => false,
                'requires_manual_verification' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'Birth Certificate',
                'code' => 'birth_certificate',
                'description' => 'Birth certificate for minor investor',
                'allowed_extensions' => 'pdf,jpg,jpeg,png',
                'max_file_size_kb' => 5120,
                'requires_expiry_date' => false,
                'requires_issue_date' => false,
                'requires_document_number' => true,
                'requires_manual_verification' => true,
                'sort_order' => 6,
            ],
            [
                'name' => 'Guardian ID',
                'code' => 'guardian_id',
                'description' => 'Guardian identification document',
                'allowed_extensions' => 'pdf,jpg,jpeg,png',
                'max_file_size_kb' => 5120,
                'requires_expiry_date' => false,
                'requires_issue_date' => false,
                'requires_document_number' => true,
                'requires_manual_verification' => true,
                'sort_order' => 7,
            ],
            [
                'name' => 'Certificate of Incorporation',
                'code' => 'certificate_of_incorporation',
                'description' => 'Company incorporation certificate',
                'allowed_extensions' => 'pdf,jpg,jpeg,png',
                'max_file_size_kb' => 5120,
                'requires_expiry_date' => false,
                'requires_issue_date' => false,
                'requires_document_number' => true,
                'requires_manual_verification' => true,
                'sort_order' => 8,
            ],
            [
                'name' => 'Board Resolution',
                'code' => 'board_resolution',
                'description' => 'Board or authorized investment resolution',
                'allowed_extensions' => 'pdf,jpg,jpeg,png',
                'max_file_size_kb' => 5120,
                'requires_expiry_date' => false,
                'requires_issue_date' => true,
                'requires_document_number' => false,
                'requires_manual_verification' => true,
                'sort_order' => 9,
            ],
            [
                'name' => 'Memorandum and Articles',
                'code' => 'memorandum_and_articles',
                'description' => 'Company constitutional documents',
                'allowed_extensions' => 'pdf,jpg,jpeg,png',
                'max_file_size_kb' => 10240,
                'requires_expiry_date' => false,
                'requires_issue_date' => false,
                'requires_document_number' => false,
                'requires_manual_verification' => true,
                'sort_order' => 10,
            ],
            [
                'name' => 'Authorized Signatory ID',
                'code' => 'authorized_signatory_id',
                'description' => 'Identification for authorized signatory',
                'allowed_extensions' => 'pdf,jpg,jpeg,png',
                'max_file_size_kb' => 5120,
                'requires_expiry_date' => false,
                'requires_issue_date' => false,
                'requires_document_number' => true,
                'requires_manual_verification' => true,
                'sort_order' => 11,
            ],
            [
                'name' => 'Source of Funds Declaration',
                'code' => 'source_of_funds_declaration',
                'description' => 'Declared source of investment funds',
                'allowed_extensions' => 'pdf,jpg,jpeg,png',
                'max_file_size_kb' => 5120,
                'requires_expiry_date' => false,
                'requires_issue_date' => true,
                'requires_document_number' => false,
                'requires_manual_verification' => true,
                'sort_order' => 12,
            ],
            [
                'name' => 'Custodian Letter',
                'code' => 'custodian_letter',
                'description' => 'Custodian confirmation for non-resident investor',
                'allowed_extensions' => 'pdf,jpg,jpeg,png',
                'max_file_size_kb' => 5120,
                'requires_expiry_date' => false,
                'requires_issue_date' => true,
                'requires_document_number' => false,
                'requires_manual_verification' => true,
                'sort_order' => 13,
            ],
        ];

        foreach ($documentTypes as $documentType) {
            DB::table('document_types')->updateOrInsert(
                ['code' => $documentType['code']],
                array_merge($documentType, [
                    'uuid' => (string) Str::uuid(),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}