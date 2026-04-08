<?php

namespace App\Application\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ClickPesaTokenService
{
    public function generateToken(): string
    {
        $config = config('services.clickpesa');

        if (! ($config['enabled'] ?? false)) {
            throw ValidationException::withMessages([
                'clickpesa' => ['ClickPesa integration is not enabled.'],
            ]);
        }

        if (($config['mode'] ?? 'mock') === 'mock') {
            return 'Bearer MOCK-CLICKPESA-TOKEN';
        }

        $authUrl = $config['auth_url'] ?? null;
        $clientId = $config['client_id'] ?? null;
        $apiKey = $config['api_key'] ?? null;
        $timeout = (int) ($config['timeout_seconds'] ?? 30);

        if (! $authUrl) {
            throw ValidationException::withMessages([
                'clickpesa' => ['ClickPesa auth URL is missing.'],
            ]);
        }

        if (! $clientId) {
            throw ValidationException::withMessages([
                'clickpesa' => ['ClickPesa client ID is missing.'],
            ]);
        }

        if (! $apiKey) {
            throw ValidationException::withMessages([
                'clickpesa' => ['ClickPesa API key is missing.'],
            ]);
        }

        Log::info('ClickPesa token request prepared.', [
            'auth_url' => $authUrl,
            'client_id_present' => true,
            'api_key_present' => true,
            'mode' => $config['mode'] ?? null,
        ]);

        $response = Http::timeout($timeout)
            ->acceptJson()
            ->withHeaders([
                'client-id' => $clientId,
                'api-key' => $apiKey,
            ])
            ->post($authUrl);

        $responseBody = $response->json() ?: $response->body();

        Log::info('ClickPesa token response received.', [
            'status' => $response->status(),
            'body' => $responseBody,
        ]);

        if (! $response->successful()) {
            Log::error('ClickPesa token generation failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw ValidationException::withMessages([
                'clickpesa' => ['Failed to generate ClickPesa authorization token.'],
            ]);
        }

        $token = data_get($responseBody, 'token')
            ?? data_get($responseBody, 'data.token')
            ?? data_get($responseBody, 'access_token')
            ?? data_get($responseBody, 'data.access_token');

        if (! $token || ! is_string($token)) {
            throw ValidationException::withMessages([
                'clickpesa' => ['ClickPesa authorization token was not returned.'],
            ]);
        }

        return trim($token);
    }
}