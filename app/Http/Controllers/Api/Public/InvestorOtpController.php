<?php

namespace App\Http\Controllers\Api\Public;

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
        InvestorOnboardingService $onboardingService,
        PhoneOtpService $phoneOtpService
    ): JsonResponse {
        $session = $onboardingService->findActiveSessionByUuid(
            $request->string('session_id')->toString()
        );

        $otp = $phoneOtpService->send(
            phoneNumber: $request->string('phone_number')->toString(),
            session: $session,
        );

        return response()->json([
            'message' => 'OTP sent successfully.',
            'data' => [
                'session_id' => $session->uuid,
                'phone_number' => $otp->phone_number,
                'provider' => $otp->provider,
                'expires_at' => optional($otp->expires_at)?->toDateTimeString(),
                'otp_code_for_demo' => $otp->code, // remove in production
            ],
        ]);
    }

    public function verify(
        VerifyPhoneOtpRequest $request,
        InvestorOnboardingService $onboardingService,
        PhoneOtpService $phoneOtpService
    ): JsonResponse {
        $session = $onboardingService->findActiveSessionByUuid(
            $request->string('session_id')->toString()
        );

        $otp = $phoneOtpService->verify(
            phoneNumber: $request->string('phone_number')->toString(),
            code: $request->string('otp_code')->toString(),
            session: $session,
        );

        return response()->json([
            'message' => 'OTP verified successfully.',
            'data' => [
                'session_id' => $session->uuid,
                'phone_number' => $otp->phone_number,
                'verified_at' => optional($otp->verified_at)?->toDateTimeString(),
                'current_step' => $session->fresh()->current_step,
            ],
        ]);
    }
}