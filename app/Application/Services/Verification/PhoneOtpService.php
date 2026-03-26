<?php

namespace App\Application\Services\Verification;

use App\Models\OtpVerification;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class PhoneOtpService
{
    public function send(string $phoneNumber, string $purpose = 'investor_onboarding'): OtpVerification
    {
        $driver = config('otp.driver', 'mock');

        if ($driver === 'beem') {
            return $this->sendViaBeem($phoneNumber, $purpose);
        }

        return $this->sendMock($phoneNumber, $purpose);
    }

    public function verify(string $phoneNumber, string $otpCode, string $purpose = 'investor_onboarding'): OtpVerification
    {
        $driver = config('otp.driver', 'mock');

        if ($driver === 'beem') {
            return $this->verifyViaBeem($phoneNumber, $otpCode, $purpose);
        }

        return $this->verifyMock($phoneNumber, $otpCode, $purpose);
    }

    protected function sendMock(string $phoneNumber, string $purpose): OtpVerification
    {
        $code = (string) random_int(100000, 999999);

        return OtpVerification::create([
            'uuid' => (string) Str::uuid(),
            'phone_number' => $phoneNumber,
            'purpose' => $purpose,
            'code' => $code,
            'provider' => 'mock',
            'provider_reference' => 'MOCK-' . Str::upper(Str::random(10)),
            'external_pin_id' => null,
            'status' => 'sent',
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
            'verified_externally' => false,
            'metadata' => [
                'mock_mode' => true,
            ],
        ]);
    }

    protected function verifyMock(string $phoneNumber, string $otpCode, string $purpose): OtpVerification
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
            'verified_externally' => false,
        ]);

        return $otp->fresh();
    }

 protected function sendViaBeem(string $phoneNumber, string $purpose): OtpVerification
    {
        $baseUrl = rtrim(config('otp.beem.base_url'), '/');
        $apiKey = config('otp.beem.api_key');
        $secretKey = config('otp.beem.secret_key');
        $appId = config('otp.beem.app_id');

        if (! $apiKey || ! $secretKey || ! $appId) {
            throw ValidationException::withMessages([
                'otp' => ['Beem OTP credentials are not configured correctly. Check API key, secret, and app id.'],
            ]);
        }

        $response = Http::withBasicAuth($apiKey, $secretKey)
            ->acceptJson()
            ->post($baseUrl . '/request', [
                'appId' => (string) $appId,
                'msisdn' => $phoneNumber,
            ]);

        $json = $response->json();

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'otp' => [
                    'Beem HTTP error: ' . $response->status() . ' | Response: ' . json_encode($json),
                ],
            ]);
        }

        $pinId = data_get($json, 'data.pinId');
        $messageCode = (int) data_get($json, 'data.message.code', 0);
        $messageText = (string) data_get($json, 'data.message.message', 'Unknown response');

        if (! $pinId || $messageCode !== 100) {
            throw ValidationException::withMessages([
                'otp' => [
                    "Beem OTP request failed. Code: {$messageCode}. Message: {$messageText}. Full response: " . json_encode($json),
                ],
            ]);
        }

        return OtpVerification::create([
            'uuid' => (string) Str::uuid(),
            'phone_number' => $phoneNumber,
            'purpose' => $purpose,
            'code' => null,
            'provider' => 'beem',
            'provider_reference' => $pinId,
            'external_pin_id' => $pinId,
            'status' => 'sent',
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
            'verified_externally' => false,
            'metadata' => [
                'beem_response' => $json,
            ],
        ]);
    }

    protected function verifyViaBeem(string $phoneNumber, string $otpCode, string $purpose): OtpVerification
    {
        $otp = OtpVerification::query()
            ->where('phone_number', $phoneNumber)
            ->where('purpose', $purpose)
            ->where('provider', 'beem')
            ->whereIn('status', ['sent', 'pending'])
            ->latest('id')
            ->first();

        if (! $otp) {
            throw ValidationException::withMessages([
                'otp' => ['No OTP request found for this phone number.'],
            ]);
        }

        if (! $otp->external_pin_id) {
            throw ValidationException::withMessages([
                'otp' => ['Stored Beem pinId is missing.'],
            ]);
        }

        $otp->increment('attempts');

        $baseUrl = rtrim(config('otp.beem.base_url'), '/');
        $apiKey = config('otp.beem.api_key');
        $secretKey = config('otp.beem.secret_key');

        $response = Http::withBasicAuth($apiKey, $secretKey)
            ->acceptJson()
            ->post($baseUrl . '/verify', [
                'pinId' => $otp->external_pin_id,
                'pin' => $otpCode,
            ]);

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'otp' => ['Failed to verify OTP via Beem.'],
            ]);
        }

        $json = $response->json();
        $messageCode = (int) data_get($json, 'data.message.code', 0);
        $messageText = (string) data_get($json, 'data.message.message', 'Unknown response');

        if ($messageCode !== 117) {
            throw ValidationException::withMessages([
                'otp' => ["OTP verification failed: {$messageText}"],
            ]);
        }

        $otp->update([
            'status' => 'verified',
            'verified_at' => now(),
            'verified_externally' => true,
            'metadata' => array_merge($otp->metadata ?? [], [
                'beem_verify_response' => $json,
            ]),
        ]);

        return $otp->fresh();
    }
}