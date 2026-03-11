<?php

namespace App\Http\Controllers\Api;

use App\Models\Investor;
use App\Models\DocumentType;
use App\Models\InvestorDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Http\Requests\Document\UploadInvestorDocumentRequest;
use App\Application\Services\Document\InvestorDocumentStorageService;
use App\Application\Services\Audit\AuditLogger;

class InvestorDocumentController extends Controller
{
    public function store(
        UploadInvestorDocumentRequest $request,
        Investor $investor,
        InvestorDocumentStorageService $storageService,
        AuditLogger $auditLogger
    ): JsonResponse {
        $this->authorize('create', InvestorDocument::class);

        $documentType = DocumentType::findOrFail($request->integer('document_type_id'));

        $stored = $storageService->storeEncrypted(
            file: $request->file('file'),
            investorId: $investor->id,
            documentCode: $documentType->code,
            disk: 's3'
        );

        $document = InvestorDocument::create([
            'uuid' => (string) Str::uuid(),
            'investor_id' => $investor->id,
            'investor_kyc_profile_id' => $investor->kycProfile?->id,
            'investor_category_id' => null,
            'document_type_id' => $documentType->id,
            'original_filename' => $stored['original_filename'],
            'stored_filename' => $stored['stored_filename'],
            'storage_disk' => $stored['storage_disk'],
            'storage_path' => $stored['storage_path'],
            'mime_type' => $stored['mime_type'],
            'file_extension' => $stored['file_extension'],
            'file_size_bytes' => $stored['file_size_bytes'],
            'document_number' => $request->input('document_number'),
            'issue_date' => $request->input('issue_date'),
            'expiry_date' => $request->input('expiry_date'),
            'verification_status' => 'pending',
            'uploaded_by' => $request->user()?->id,
            'uploaded_at' => now(),
            'metadata' => [
                'document_type_code' => $documentType->code,
            ],
        ]);

        $auditLogger->log(
            userId: $request->user()?->id,
            action: 'investor_document.uploaded',
            entityType: 'investor_document',
            entityId: $document->id,
            entityReference: $documentType->code,
            metadata: [
                'investor_id' => $investor->id,
                'investor_number' => $investor->investor_number,
                'document_type' => $documentType->code,
                'original_filename' => $document->original_filename,
            ],
            request: $request
        );

        return response()->json([
            'message' => 'Investor document uploaded successfully.',
            'data' => [
                'id' => $document->id,
                'uuid' => $document->uuid,
                'investor_id' => $document->investor_id,
                'document_type_id' => $document->document_type_id,
                'verification_status' => $document->verification_status,
                'original_filename' => $document->original_filename,
                'uploaded_at' => optional($document->uploaded_at)?->toDateTimeString(),
            ],
        ], 201);
    }
}