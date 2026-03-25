<?php

namespace App\Http\Controllers\Api\Public;

use App\Models\InvestorOnboardingSession;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\SendPhoneOtpRequest;
use App\Http\Requests\Onboarding\VerifyPhoneOtpRequest;
use App\Application\Services\Verification\PhoneOtpService;
use App\Application\Services\Onboarding\InvestorOnboardingService;

class InvestorOtpController extends Controller
{
    public function send(
        SendPhoneOtpRequest $request,
        PhoneOtpService $otpService
    ): JsonResponse {
        $session = InvestorOnboardingSession::query()
            ->where('uuid', $request->input('session_uuid'))
            ->firstOrFail();

        $otp = $otpService->send(
            phoneNumber: $request->input('phone_number'),
            purpose: 'investor_onboarding'
        );

        return response()->json([
            'message' => 'OTP sent successfully.',
            'data' => [
                'session_uuid' => $session->uuid,
                'otp_uuid' => $otp->uuid,
                'phone_number' => $otp->phone_number,
                'expires_at' => optional($otp->expires_at)?->toDateTimeString(),
                'mock_code' => $otp->code,
            ],
        ]);
    }

    public function verify(
        VerifyPhoneOtpRequest $request,
        PhoneOtpService $otpService,
        InvestorOnboardingService $onboardingService
    ): JsonResponse {
        $session = InvestorOnboardingSession::query()
            ->where('uuid', $request->input('session_uuid'))
            ->firstOrFail();

        $otp = $otpService->verify(
            phoneNumber: $request->input('phone_number'),
            otpCode: $request->input('otp_code'),
            purpose: 'investor_onboarding'
        );

        $session = $onboardingService->markPhoneVerified($session);

        return response()->json([
            'message' => 'OTP verified successfully.',
            'data' => [
                'session_uuid' => $session->uuid,
                'otp_uuid' => $otp->uuid,
                'phone_verified_at' => optional($session->phone_verified_at)?->toDateTimeString(),
                'current_step' => $session->current_step,
            ],
        ]);
    }
}