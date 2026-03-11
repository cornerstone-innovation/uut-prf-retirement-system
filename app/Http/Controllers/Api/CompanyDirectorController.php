<?php

namespace App\Http\Controllers\Api;

use App\Models\Investor;
use App\Models\CompanyDirector;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\CompanyDirectorResource;
use App\Http\Requests\Director\StoreCompanyDirectorRequest;
use App\Application\Services\Audit\AuditLogger;

class CompanyDirectorController extends Controller
{
    public function index(Investor $investor): JsonResponse
    {
        $directors = $investor->directors()->latest('id')->get();

        return response()->json([
            'message' => 'Company directors retrieved successfully.',
            'data' => CompanyDirectorResource::collection($directors),
        ]);
    }

    public function store(
        StoreCompanyDirectorRequest $request,
        Investor $investor,
        AuditLogger $auditLogger
    ): JsonResponse {
        if ($investor->investor_type !== 'corporate') {
            return response()->json([
                'message' => 'Directors can only be added to corporate investors.',
            ], 422);
        }

        $director = $investor->directors()->create($request->validated());

        $auditLogger->log(
            userId: $request->user()?->id,
            action: 'company_director.created',
            entityType: 'company_director',
            entityId: $director->id,
            entityReference: $director->full_name,
            metadata: [
                'investor_id' => $investor->id,
                'investor_number' => $investor->investor_number,
                'has_signing_authority' => $director->has_signing_authority,
                'role' => $director->role,
            ],
            request: $request
        );

        return response()->json([
            'message' => 'Company director added successfully.',
            'data' => new CompanyDirectorResource($director),
        ], 201);
    }
}