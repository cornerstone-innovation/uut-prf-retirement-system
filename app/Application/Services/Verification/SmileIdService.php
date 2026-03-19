<?php

namespace App\Application\Services\Verification;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class SmileIdService
{
    public function verifyDirectorNationalId(array $payload): array
    {
        $mode = config('services.smile_id.mode', 'mock');
        $enabled = (bool) config('services.smile_id.enabled', false);

        if (! $enabled || $mode === 'mock') {
            return $this->mockNationalIdVerification($payload);
        }

        return $this->liveNationalIdVerification($payload);
    }

    public function verifyInvestorNationalId(array $payload): array
    {
        $mode = config('services.smile_id.mode', 'mock');
        $enabled = (bool) config('services.smile_id.enabled', false);

        if (! $enabled || $mode === 'mock') {
            return $this->mockNationalIdVerification($payload);
        }

        return $this->liveNationalIdVerification($payload);
    }

    protected function mockNationalIdVerification(array $payload): array
    {
        $nationalId = $payload['national_id_number'] ?? null;

        $isPass = filled($nationalId) && str_contains($nationalId, '-');

        return [
            'success' => $isPass,
            'provider' => 'smile_id',
            'provider_reference' => 'mock-smile-' . Str::uuid(),
            'status' => $isPass ? 'verified' : 'failed',
            'score' => $isPass ? 95.50 : 22.00,
            'failure_reason' => $isPass ? null : 'Mock verification failed: invalid or missing national ID format.',
            'request_payload' => $payload,
            'response_payload' => [
                'mode' => 'mock',
                'result_text' => $isPass ? 'National ID verified successfully.' : 'National ID verification failed.',
                'match' => $isPass,
            ],
        ];
    }

    protected function liveNationalIdVerification(array $payload): array
    {
        $baseUrl = config('services.smile_id.base_url');
        $apiKey = config('services.smile_id.api_key');
        $partnerId = config('services.smile_id.partner_id');
        $callbackUrl = config('services.smile_id.callback_url');

        if (! $baseUrl || ! $apiKey || ! $partnerId) {
            return [
                'success' => false,
                'provider' => 'smile_id',
                'provider_reference' => null,
                'status' => 'failed',
                'score' => null,
                'failure_reason' => 'Smile ID live configuration is incomplete.',
                'request_payload' => $payload,
                'response_payload' => null,
            ];
        }

        $requestBody = [
            'partner_id' => $partnerId,
            'callback_url' => $callbackUrl,
            'id_number' => $payload['national_id_number'] ?? null,
            'full_name' => $payload['full_name'] ?? null,
            'country' => $payload['country'] ?? 'TZ',
            'id_type' => 'NATIONAL_ID',
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Accept' => 'application/json',
        ])->post(rtrim($baseUrl, '/') . '/verify/national-id', $requestBody);

        $json = $response->json();

        $passed = $response->successful() && data_get($json, 'status') === 'verified';

        return [
            'success' => $passed,
            'provider' => 'smile_id',
            'provider_reference' => data_get($json, 'reference') ?? data_get($json, 'job_id'),
            'status' => $passed ? 'verified' : 'failed',
            'score' => data_get($json, 'score'),
            'failure_reason' => $passed ? null : (data_get($json, 'message') ?? 'Smile ID verification failed.'),
            'request_payload' => $requestBody,
            'response_payload' => $json,
        ];
    }
}