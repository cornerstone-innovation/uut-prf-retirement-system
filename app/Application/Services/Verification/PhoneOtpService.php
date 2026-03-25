<?php

namespace App\Application\Services\Verification;

use App\Models\OtpVerification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PhoneOtpService
{
    public function send(string $phoneNumber, string $purpose = 'investor_onboarding'): OtpVerification
    {
        $code = (string) random_int(100000, 999999);

        return OtpVerification::create([
            'uuid' => (string) Str::uuid(),
            'phone_number' => $phoneNumber,
            'purpose' => $purpose,
            'code' => $code,
            'provider' => 'beem',
            'provider_reference' => 'MOCK-' . Str::upper(Str::random(10)),
            'status' => 'sent',
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
            'metadata' => [
                'mock_mode' => true,
                'note' => 'Replace with real Beem Africa API integration.',
            ],
        ]);
    }

    public function verify(string $phoneNumber, string $otpCode, string $purpose = 'investor_onboarding'): OtpVerification
    {
        $otp = OtpVerification::query()
            ->where('phone_number', $phoneNumber)
            ->where('purpose', $purpose)
            ->whereIn('status', ['sent', 'pending'])
            ->latest('id')
            ->first();

        if (! $otp) {
            throw ValidationException::withMessages([
                'otp' => ['No OTP request found for this phone number.'],
            ]);
        }

        if ($otp->expires_at && $otp->expires_at->isPast()) {
            $otp->update(['status' => 'expired']);

            throw ValidationException::withMessages([
                'otp' => ['OTP has expired.'],
            ]);
        }

        $otp->increment('attempts');

        if ($otp->code !== $otpCode) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid OTP code.'],
            ]);
        }

        $otp->update([
            'status' => 'verified',
            'verified_at' => now(),
        ]);

        return $otp->fresh();
    }
}