<?php

namespace App\Application\Services\Verification;

use App\Models\OtpVerification;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PhoneOtpService
{
    public function send(string $phoneNumber, string $purpose = 'investor_onboarding'): OtpVerification
    {
        $driver = config('otp.driver', 'mock');

        return $driver === 'beem'
            ? $this->sendViaBeem($phoneNumber, $purpose)
            : $this->sendMock($phoneNumber, $purpose);
    }

    public function verify(string $phoneNumber, string $otpCode, string $purpose = 'investor_onboarding'): OtpVerification
    {
        $driver = config('otp.driver', 'mock');

        return $driver === 'beem'
            ? $this->verifyViaBeem($phoneNumber, $otpCode, $purpose)
            : $this->verifyMock($phoneNumber, $otpCode, $purpose);
    }

    protected function sendMock(string $phoneNumber, string $purpose): OtpVerification
    {
        $normalizedPhone = $this->normalizePhoneNumber($phoneNumber);
        $code = (string) random_int(100000, 999999);

        return OtpVerification::create([
            'uuid' => (string) Str::uuid(),
            'phone_number' => $normalizedPhone,
            'purpose' => $purpose,
            'code' => $code,
            'provider' => 'mock',
            'provider_reference' => 'MOCK-' . Str::upper(Str::random(10)),
            'external_pin_id' => null,
            'status' => 'sent',
            'attempts' => 0,
            'expires_at' => now()->addMinutes((int) config('otp.expiry_minutes', 10)),
            'verified_externally' => false,
            'metadata' => [
                'mock_mode' => true,
            ],
        ]);
    }

    protected function verifyMock(string $phoneNumber, string $otpCode, string $purpose): OtpVerification
    {
        $normalizedPhone = $this->normalizePhoneNumber($phoneNumber);

        $otp = OtpVerification::query()
            ->where('phone_number', $normalizedPhone)
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

        $maxAttempts = (int) config('otp.max_attempts', 5);

        if ((int) $otp->attempts >= $maxAttempts) {
            $otp->update(['status' => 'failed']);

            throw ValidationException::withMessages([
                'otp' => ['Maximum OTP attempts exceeded.'],
            ]);
        }

        $otp->increment('attempts');

        if ($otp->code !== trim($otpCode)) {
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
    $normalizedPhone = $this->normalizePhoneNumber($phoneNumber);

    $baseUrl = rtrim((string) config('otp.beem.base_url'), '/');
    $apiKey = config('otp.beem.api_key');
    $secretKey = config('otp.beem.secret_key');
    $appId = config('otp.beem.app_id');
    $timeout = (int) config('otp.beem.timeout', 30);

    if (! $apiKey || ! $secretKey || ! $appId) {
        throw ValidationException::withMessages([
            'otp' => ['Beem OTP credentials are not configured correctly.'],
        ]);
    }

    $payload = [
        'appId' => (string) $appId,
        'msisdn' => $normalizedPhone,
    ];

    Log::info('Beem OTP send request prepared.', [
        'base_url' => $baseUrl,
        'app_id' => $appId,
        'phone_number' => $normalizedPhone,
        'purpose' => $purpose,
        'payload' => $payload,
    ]);

    $response = Http::withBasicAuth($apiKey, $secretKey)
        ->acceptJson()
        ->contentType('application/json')
        ->timeout($timeout)
        ->post($baseUrl . '/request', $payload);

    $json = $response->json();

    Log::info('Beem OTP send response received.', [
        'phone_number' => $normalizedPhone,
        'purpose' => $purpose,
        'status_code' => $response->status(),
        'response' => $json ?: $response->body(),
    ]);

    if (! $response->successful()) {
        throw ValidationException::withMessages([
            'otp' => [
                'Beem OTP request failed with HTTP ' . $response->status() . '. Response: ' . json_encode($json ?: $response->body()),
            ],
        ]);
    }

    $messageCode = (int) (
        data_get($json, 'data.message.code')
        ?? data_get($json, 'message.code')
        ?? data_get($json, 'code')
        ?? 0
    );

    $messageText = (string) (
        data_get($json, 'data.message.message')
        ?? data_get($json, 'message.message')
        ?? data_get($json, 'message')
        ?? 'Unknown response'
    );

    $pinId = (string) (
        data_get($json, 'data.pinId')
        ?? data_get($json, 'pinId')
        ?? data_get($json, 'data.pin_id')
        ?? data_get($json, 'pin_id')
        ?? ''
    );

    if ($messageCode !== 100 || empty($pinId)) {
        throw ValidationException::withMessages([
            'otp' => [
                "Beem OTP request failed. Code: {$messageCode}. Message: {$messageText}. Full response: " . json_encode($json),
            ],
        ]);
    }

    return OtpVerification::create([
        'uuid' => (string) Str::uuid(),
        'phone_number' => $normalizedPhone,
        'purpose' => $purpose,
        'code' => null,
        'provider' => 'beem',
        'provider_reference' => $pinId,
        'external_pin_id' => $pinId,
        'status' => 'sent',
        'attempts' => 0,
        'expires_at' => now()->addMinutes((int) config('otp.expiry_minutes', 10)),
        'verified_externally' => false,
        'metadata' => [
            'provider' => 'beem',
            'request_payload' => $payload,
            'send_response' => $json,
            'send_message_code' => $messageCode,
            'send_message_text' => $messageText,
        ],
    ]);
}

    protected function verifyViaBeem(string $phoneNumber, string $otpCode, string $purpose): OtpVerification
    {
        $normalizedPhone = $this->normalizePhoneNumber($phoneNumber);

        $otp = OtpVerification::query()
            ->where('phone_number', $normalizedPhone)
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

        if ($otp->expires_at && $otp->expires_at->isPast()) {
            $otp->update(['status' => 'expired']);

            throw ValidationException::withMessages([
                'otp' => ['OTP has expired.'],
            ]);
        }

        $maxAttempts = (int) config('otp.max_attempts', 5);

        if ((int) $otp->attempts >= $maxAttempts) {
            $otp->update(['status' => 'failed']);

            throw ValidationException::withMessages([
                'otp' => ['Maximum OTP attempts exceeded.'],
            ]);
        }

        if (! $otp->external_pin_id) {
            throw ValidationException::withMessages([
                'otp' => ['Stored Beem pinId is missing.'],
            ]);
        }

        $otp->increment('attempts');

        $baseUrl = rtrim((string) config('otp.beem.base_url'), '/');
        $apiKey = config('otp.beem.api_key');
        $secretKey = config('otp.beem.secret_key');
        $timeout = (int) config('otp.beem.timeout', 30);

        $payload = [
            'pinId' => $otp->external_pin_id,
            'pin' => trim($otpCode),
        ];

        $response = Http::withBasicAuth($apiKey, $secretKey)
            ->acceptJson()
            ->contentType('application/json')
            ->timeout($timeout)
            ->post($baseUrl . '/verify', $payload);

        $json = $response->json();

        Log::info('Beem OTP verify response received.', [
            'phone_number' => $normalizedPhone,
            'purpose' => $purpose,
            'pin_id' => $otp->external_pin_id,
            'status_code' => $response->status(),
            'response' => $json ?: $response->body(),
        ]);

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'otp' => [
                    'Failed to verify OTP via Beem. HTTP ' . $response->status() . '.',
                ],
            ]);
        }

        $messageCode = (int) (
            data_get($json, 'data.message.code')
            ?? data_get($json, 'message.code')
            ?? data_get($json, 'code')
            ?? 0
        );

        $messageText = (string) (
            data_get($json, 'data.message.message')
            ?? data_get($json, 'message.message')
            ?? data_get($json, 'message')
            ?? 'Unknown response'
        );

        if ($messageCode === 117) {
            $otp->update([
                'status' => 'verified',
                'verified_at' => now(),
                'verified_externally' => true,
                'metadata' => array_merge($otp->metadata ?? [], [
                    'verify_payload' => $payload,
                    'verify_response' => $json,
                    'verify_message_code' => $messageCode,
                    'verify_message_text' => $messageText,
                ]),
            ]);

            return $otp->fresh();
        }

        if (in_array($messageCode, [114, 115, 116, 118], true)) {
            $status = match ($messageCode) {
                115 => 'expired',
                116, 118 => 'failed',
                default => 'sent',
            };

            $otp->update([
                'status' => $status,
                'metadata' => array_merge($otp->metadata ?? [], [
                    'verify_payload' => $payload,
                    'verify_response' => $json,
                    'verify_message_code' => $messageCode,
                    'verify_message_text' => $messageText,
                ]),
            ]);

            throw ValidationException::withMessages([
                'otp' => [$this->mapBeemVerificationMessage($messageCode, $messageText)],
            ]);
        }

        throw ValidationException::withMessages([
            'otp' => ["OTP verification failed. Code: {$messageCode}. Message: {$messageText}"],
        ]);
    }

    protected function normalizePhoneNumber(string $phoneNumber): string
    {
        $digits = preg_replace('/\D+/', '', $phoneNumber ?? '');

        if (! $digits) {
            throw ValidationException::withMessages([
                'phone_number' => ['Phone number is required.'],
            ]);
        }

        if (str_starts_with($digits, '0')) {
            $digits = '255' . substr($digits, 1);
        }

        if (! str_starts_with($digits, '255')) {
            throw ValidationException::withMessages([
                'phone_number' => ['Phone number must be a valid Tanzanian number in 255XXXXXXXXX format.'],
            ]);
        }

        if (strlen($digits) !== 12) {
            throw ValidationException::withMessages([
                'phone_number' => ['Phone number must be 12 digits in international format, e.g. 255712345678.'],
            ]);
        }

        return $digits;
    }

    protected function mapBeemVerificationMessage(int $code, string $fallback): string
    {
        return match ($code) {
            114 => 'Incorrect OTP code.',
            115 => 'OTP has expired.',
            116 => 'OTP attempts exceeded.',
            118 => 'OTP has already been used.',
            default => $fallback ?: 'OTP verification failed.',
        };
    }
}