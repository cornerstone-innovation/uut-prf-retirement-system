<?php

namespace App\Http\Controllers\Api\Public;

use App\Models\InvestorOnboardingSession;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\StartInvestorOnboardingRequest;
use App\Http\Requests\Onboarding\CompleteInvestorRegistrationRequest;
use App\Application\Services\Onboarding\InvestorOnboardingService;

class InvestorOnboardingController extends Controller
{
    public function start(
        StartInvestorOnboardingRequest $request,
        InvestorOnboardingService $onboardingService
    ): JsonResponse {
        $session = $onboardingService->start(
            investorType: $request->input('investor_type'),
            phoneNumber: $request->input('phone_number'),
            nidaNumber: $request->input('nida_number')
        );

        return response()->json([
            'message' => 'Investor onboarding started successfully.',
            'data' => [
                'session_uuid' => $session->uuid,
                'investor_type' => $session->investor_type,
                'phone_number' => $session->phone_number,
                'nida_number' => $session->nida_number,
                'current_step' => $session->current_step,
                'status' => $session->status,
                'expires_at' => optional($session->expires_at)?->toDateTimeString(),
            ],
        ], 201);
    }

    public function complete(
        CompleteInvestorRegistrationRequest $request,
        InvestorOnboardingService $onboardingService
    ): JsonResponse {
        $session = InvestorOnboardingSession::query()
            ->where('uuid', $request->input('session_uuid'))
            ->firstOrFail();

        $result = $onboardingService->complete($session, $request->validated());

        return response()->json([
            'message' => 'Investor registration completed successfully.',
            'data' => [
                'token' => $result['token'],
                'user' => [
                    'id' => $result['user']->id,
                    'name' => $result['user']->name,
                    'email' => $result['user']->email,
                    'phone' => $result['user']->phone,
                    'roles' => $result['user']->getRoleNames()->values(),
                    'permissions' => $result['user']->getAllPermissions()->pluck('name')->values(),
                ],
                'investor' => [
                    'id' => $result['investor']->id,
                    'uuid' => $result['investor']->uuid,
                    'investor_number' => $result['investor']->investor_number,
                    'full_name' => $result['investor']->full_name,
                    'investor_type' => $result['investor']->investor_type,
                    'onboarding_status' => $result['investor']->onboarding_status,
                    'kyc_status' => $result['investor']->kyc_status,
                    'investor_status' => $result['investor']->investor_status,
                ],
            ],
        ], 201);
    }
}