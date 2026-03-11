<?php

namespace App\Http\Controllers\Api;

use App\Models\Investor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\InvestorResource;
use App\Application\Services\Audit\AuditLogger;
use App\Application\Actions\Investor\CreateInvestorAction;
use App\Application\DTOs\Investor\CreateInvestorData;
use App\Application\DTOs\Investor\InvestorAddressData;
use App\Application\DTOs\Investor\InvestorNomineeData;
use App\Application\Services\Investor\InvestorOnboardingValidator;
use App\Http\Requests\Investor\StoreInvestorRequest;
use Illuminate\Support\Facades\DB;
use App\Models\ApprovalRequest;
use App\Http\Requests\Approval\ApproveInvestorRequest;
use App\Http\Requests\Approval\RejectInvestorRequest;
use App\Application\Services\Approval\ApprovalWorkflowService;

class InvestorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Investor::class);

        $query = Investor::query()
            ->with(['contact', 'addresses', 'nominees', 'kycProfile']);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('investor_number', 'ilike', "%{$search}%")
                    ->orWhere('full_name', 'ilike', "%{$search}%")
                    ->orWhere('company_name', 'ilike', "%{$search}%")
                    ->orWhereHas('contact', function ($contactQuery) use ($search) {
                        $contactQuery->where('email', 'ilike', "%{$search}%")
                            ->orWhere('phone_primary', 'ilike', "%{$search}%");
                    });
            });
        }

        if ($request->filled('investor_type')) {
            $query->where('investor_type', $request->string('investor_type')->toString());
        }

        if ($request->filled('onboarding_status')) {
            $query->where('onboarding_status', $request->string('onboarding_status')->toString());
        }

        if ($request->filled('kyc_status')) {
            $query->where('kyc_status', $request->string('kyc_status')->toString());
        }

        if ($request->filled('investor_status')) {
            $query->where('investor_status', $request->string('investor_status')->toString());
        }

        $perPage = (int) $request->integer('per_page', 15);
        $perPage = min(max($perPage, 1), 100);

        $investors = $query
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Investors retrieved successfully.',
            'data' => InvestorResource::collection($investors->items()),
            'meta' => [
                'current_page' => $investors->currentPage(),
                'last_page' => $investors->lastPage(),
                'per_page' => $investors->perPage(),
                'total' => $investors->total(),
            ],
        ]);
    }

    public function store(
        StoreInvestorRequest $request,
        InvestorOnboardingValidator $validator,
        CreateInvestorAction $action,
        AuditLogger $auditLogger
    ): JsonResponse {
        $this->authorize('create', Investor::class);

        $validated = $request->validated();

        $validator->validate($validated);

        $investor = $action->execute(
            new CreateInvestorData(
                investorType: $validated['investor_type'],
                fullName: $validated['full_name'],
                firstName: $validated['first_name'] ?? null,
                middleName: $validated['middle_name'] ?? null,
                lastName: $validated['last_name'] ?? null,
                companyName: $validated['company_name'] ?? null,
                dateOfBirth: $validated['date_of_birth'] ?? null,
                gender: $validated['gender'] ?? null,
                nationality: $validated['nationality'] ?? null,
                nationalIdNumber: $validated['national_id_number'] ?? null,
                taxIdentificationNumber: $validated['tax_identification_number'] ?? null,
                riskProfile: $validated['risk_profile'] ?? null,
                occupation: $validated['occupation'] ?? null,
                employerName: $validated['employer_name'] ?? null,
                sourceOfFunds: $validated['source_of_funds'] ?? null,
                notes: $validated['notes'] ?? null,
                email: $validated['email'] ?? null,
                phonePrimary: $validated['phone_primary'] ?? null,
                phoneSecondary: $validated['phone_secondary'] ?? null,
                alternateContactName: $validated['alternate_contact_name'] ?? null,
                alternateContactPhone: $validated['alternate_contact_phone'] ?? null,
                preferredContactMethod: $validated['preferred_contact_method'] ?? null,
                addresses: array_map(
                    fn (array $address) => new InvestorAddressData(
                        addressType: $address['address_type'],
                        country: $address['country'],
                        region: $address['region'] ?? null,
                        city: $address['city'] ?? null,
                        district: $address['district'] ?? null,
                        ward: $address['ward'] ?? null,
                        street: $address['street'] ?? null,
                        postalAddress: $address['postal_address'] ?? null,
                        postalCode: $address['postal_code'] ?? null,
                        isPrimary: (bool) $address['is_primary'],
                    ),
                    $validated['addresses']
                ),
                nominees: array_map(
                    fn (array $nominee) => new InvestorNomineeData(
                        fullName: $nominee['full_name'],
                        relationship: $nominee['relationship'],
                        dateOfBirth: $nominee['date_of_birth'] ?? null,
                        phone: $nominee['phone'] ?? null,
                        email: $nominee['email'] ?? null,
                        nationalIdNumber: $nominee['national_id_number'] ?? null,
                        allocationPercentage: (float) $nominee['allocation_percentage'],
                        isMinor: (bool) $nominee['is_minor'],
                        guardianName: $nominee['guardian_name'] ?? null,
                        guardianPhone: $nominee['guardian_phone'] ?? null,
                        address: $nominee['address'] ?? null,
                    ),
                    $validated['nominees'] ?? []
                ),
                createdBy: $request->user()?->id,
                updatedBy: $request->user()?->id,
            )
        );

        $auditLogger->log(
            userId: $request->user()?->id,
            action: 'investor.created',
            entityType: 'investor',
            entityId: $investor->id,
            entityReference: $investor->investor_number,
            metadata: [
                'investor_type' => $investor->investor_type,
                'full_name' => $investor->full_name,
                'onboarding_status' => $investor->onboarding_status,
                'kyc_status' => $investor->kyc_status,
            ],
            request: $request
        );

        return response()->json([
            'message' => 'Investor created successfully.',
            'data' => new InvestorResource($investor),
        ], 201);
    }

    public function show(Request $request, Investor $investor): JsonResponse
    {
        $this->authorize('view', $investor);

        $investor->load(['contact', 'addresses', 'nominees', 'kycProfile']);

        return response()->json([
            'message' => 'Investor retrieved successfully.',
            'data' => new InvestorResource($investor),
        ]);
    }

    public function approve(
    ApproveInvestorRequest $request,
    Investor $investor,
    ApprovalWorkflowService $approvalWorkflowService,
    AuditLogger $auditLogger
): JsonResponse {
    $this->authorize('approve', $investor);

    $approvalRequest = ApprovalRequest::query()
        ->where('approval_type', 'investor_onboarding')
        ->where('entity_type', 'investor')
        ->where('entity_id', $investor->id)
        ->where('status', 'pending')
        ->latest('id')
        ->firstOrFail();

    DB::transaction(function () use (
        $request,
        $investor,
        $approvalRequest,
        $approvalWorkflowService,
        $auditLogger
    ) {
        $approvalWorkflowService->approve(
            approvalRequest: $approvalRequest,
            actedBy: $request->user()->id,
            comments: $request->input('comments'),
            metadata: [
                'investor_number' => $investor->investor_number,
            ]
        );

        $investor->update([
            'onboarding_status' => 'approved',
            'investor_status' => 'active',
            'updated_by' => $request->user()->id,
        ]);

        $auditLogger->log(
            userId: $request->user()->id,
            action: 'investor.approved',
            entityType: 'investor',
            entityId: $investor->id,
            entityReference: $investor->investor_number,
            metadata: [
                'comments' => $request->input('comments'),
                'onboarding_status' => 'approved',
                'investor_status' => 'active',
            ],
            request: $request
        );
    });

    return response()->json([
        'message' => 'Investor approved successfully.',
        'data' => new InvestorResource($investor->fresh()->load(['contact', 'addresses', 'nominees', 'kycProfile'])),
    ]);
}


public function reject(
    RejectInvestorRequest $request,
    Investor $investor,
    ApprovalWorkflowService $approvalWorkflowService,
    AuditLogger $auditLogger
): JsonResponse {
    $this->authorize('approve', $investor);

    $approvalRequest = ApprovalRequest::query()
        ->where('approval_type', 'investor_onboarding')
        ->where('entity_type', 'investor')
        ->where('entity_id', $investor->id)
        ->where('status', 'pending')
        ->latest('id')
        ->firstOrFail();

    DB::transaction(function () use (
        $request,
        $investor,
        $approvalRequest,
        $approvalWorkflowService,
        $auditLogger
    ) {
        $approvalWorkflowService->reject(
            approvalRequest: $approvalRequest,
            actedBy: $request->user()->id,
            comments: $request->input('comments'),
            metadata: [
                'investor_number' => $investor->investor_number,
            ]
        );

        $investor->update([
            'onboarding_status' => 'rejected',
            'investor_status' => 'inactive',
            'updated_by' => $request->user()->id,
        ]);

        $auditLogger->log(
            userId: $request->user()->id,
            action: 'investor.rejected',
            entityType: 'investor',
            entityId: $investor->id,
            entityReference: $investor->investor_number,
            metadata: [
                'comments' => $request->input('comments'),
                'onboarding_status' => 'rejected',
                'investor_status' => 'inactive',
            ],
            request: $request
        );
    });

    return response()->json([
        'message' => 'Investor rejected successfully.',
        'data' => new InvestorResource($investor->fresh()->load(['contact', 'addresses', 'nominees', 'kycProfile'])),
    ]);
}
}