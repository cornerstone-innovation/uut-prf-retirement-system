<?php

namespace App\Http\Controllers\Api\Public;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\InvestorOnboardingSession;
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

        $phoneNumber = $request->input('phone_number');

        $otp = $otpService->send(
            phoneNumber: $phoneNumber,
            purpose: 'investor_onboarding'
        );

        $session->update([
            'phone_number' => $otp->phone_number,
            'current_step' => 'otp_sent',
        ]);

        $payload = [
            'session_uuid' => $session->uuid,
            'otp_uuid' => $otp->uuid,
            'phone_number' => $otp->phone_number,
            'provider' => $otp->provider,
            'provider_reference' => $otp->provider_reference,
            'external_pin_id' => $otp->external_pin_id,
            'status' => $otp->status,
            'attempts' => $otp->attempts,
            'expires_at' => optional($otp->expires_at)?->toDateTimeString(),
            'current_step' => $session->current_step,
        ];

        if (config('otp.driver', 'mock') !== 'beem') {
            $payload['mock_code'] = $otp->code;
        }

        return response()->json([
            'message' => 'OTP sent successfully.',
            'data' => $payload,
        ], 201);
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
                'phone_number' => $otp->phone_number,
                'provider' => $otp->provider,
                'provider_reference' => $otp->provider_reference,
                'external_pin_id' => $otp->external_pin_id,
                'otp_status' => $otp->status,
                'otp_attempts' => $otp->attempts,
                'verified_at' => optional($otp->verified_at)?->toDateTimeString(),
                'phone_verified_at' => optional($session->phone_verified_at)?->toDateTimeString(),
                'current_step' => $session->current_step,
            ],
        ]);
    }
}