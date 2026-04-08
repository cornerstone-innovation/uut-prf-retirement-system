<?php

namespace App\Application\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ClickPesaUssdPushService
{
    public function __construct(
        private readonly ClickPesaTokenService $tokenService,
        private readonly ClickPesaChecksumService $checksumService
    ) {
    }

    public function preview(array $payload): array
    {
        $config = config('services.clickpesa');

        if (! ($config['enabled'] ?? false)) {
            throw ValidationException::withMessages([
                'clickpesa' => ['ClickPesa integration is not enabled.'],
            ]);
        }

        if (($config['mode'] ?? 'mock') === 'mock') {
            return [
                'activeMethods' => [
                    [
                        'name' => 'M-PESA',
                        'status' => 'AVAILABLE',
                        'fee' => 0,
                        'message' => null,
                    ],
                    [
                        'name' => 'TIGO-PESA',
                        'status' => 'AVAILABLE',
                        'fee' => 0,
                        'message' => null,
                    ],
                ],
                'sender' => [
                    'accountName' => 'Mock Sender',
                    'accountNumber' => $payload['phoneNumber'] ?? null,
                    'accountProvider' => 'M-PESA',
                ],
            ];
        }

        $url = $config['ussd_preview_url'] ?? null;
        if (! $url) {
            throw ValidationException::withMessages([
                'clickpesa' => ['ClickPesa USSD preview URL is missing.'],
            ]);
        }

        $token = $this->tokenService->generateToken();

        $requestPayload = [
            'amount' => (string) ($payload['amount'] ?? ''),
            'currency' => $payload['currency'] ?? ($config['currency'] ?? 'TZS'),
            'orderReference' => $payload['orderReference'] ?? '',
            'phoneNumber' => $payload['phoneNumber'] ?? null,
            'fetchSenderDetails' => (bool) ($payload['fetchSenderDetails'] ?? true),
        ];

        if (! empty($config['api_secret'])) {
            $requestPayload['checksum'] = $this->checksumService->generate(
                $config['api_secret'],
                $requestPayload
            );
        }

        Log::info('ClickPesa USSD preview request prepared.', [
            'url' => $url,
            'payload' => $requestPayload,
        ]);

        $response = Http::timeout((int) ($config['timeout_seconds'] ?? 30))
            ->acceptJson()
            ->withHeaders([
                'Authorization' => $token,
            ])
            ->post($url, $requestPayload);

        $responseBody = $response->json() ?: $response->body();

        Log::info('ClickPesa USSD preview response received.', [
            'status' => $response->status(),
            'body' => $responseBody,
        ]);

        if (! $response->successful()) {
            Log::error('ClickPesa USSD preview failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw ValidationException::withMessages([
                'clickpesa' => [
                    is_array($responseBody)
                        ? (data_get($responseBody, 'message') ?? 'Failed to preview ClickPesa USSD push request.')
                        : 'Failed to preview ClickPesa USSD push request.'
                ],
            ]);
        }

        return is_array($responseBody) ? $responseBody : [];
    }

    public function initiate(array $payload): array
    {
        $config = config('services.clickpesa');

        if (! ($config['enabled'] ?? false)) {
            throw ValidationException::withMessages([
                'clickpesa' => ['ClickPesa integration is not enabled.'],
            ]);
        }

        if (($config['mode'] ?? 'mock') === 'mock') {
            return [
                'id' => 'MOCK-USSD-' . ($payload['orderReference'] ?? uniqid()),
                'status' => 'PROCESSING',
                'channel' => 'M-PESA',
                'orderReference' => $payload['orderReference'] ?? null,
                'collectedAmount' => (string) ($payload['amount'] ?? '0'),
                'collectedCurrency' => $payload['currency'] ?? 'TZS',
                'createdAt' => now()->toIso8601String(),
                'clientId' => $config['client_id'] ?? 'MOCK-CLIENT',
            ];
        }

        $url = $config['ussd_initiate_url'] ?? null;
        if (! $url) {
            throw ValidationException::withMessages([
                'clickpesa' => ['ClickPesa USSD initiate URL is missing.'],
            ]);
        }

        $token = $this->tokenService->generateToken();

        $requestPayload = [
            'amount' => (string) ($payload['amount'] ?? ''),
            'currency' => $payload['currency'] ?? ($config['currency'] ?? 'TZS'),
            'orderReference' => $payload['orderReference'] ?? '',
            'phoneNumber' => $payload['phoneNumber'] ?? '',
        ];

        if (! empty($config['api_secret'])) {
            $requestPayload['checksum'] = $this->checksumService->generate(
                $config['api_secret'],
                $requestPayload
            );
        }

        Log::info('ClickPesa USSD initiate request prepared.', [
            'url' => $url,
            'payload' => $requestPayload,
        ]);

        $response = Http::timeout((int) ($config['timeout_seconds'] ?? 30))
            ->acceptJson()
            ->withHeaders([
                'Authorization' => $token,
            ])
            ->post($url, $requestPayload);

        $responseBody = $response->json() ?: $response->body();

        Log::info('ClickPesa USSD initiate response received.', [
            'status' => $response->status(),
            'body' => $responseBody,
        ]);

        if (! $response->successful()) {
            Log::error('ClickPesa USSD initiate failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw ValidationException::withMessages([
                'clickpesa' => [
                    is_array($responseBody)
                        ? (data_get($responseBody, 'message') ?? 'Failed to initiate ClickPesa USSD push request.')
                        : 'Failed to initiate ClickPesa USSD push request.'
                ],
            ]);
        }

        return is_array($responseBody) ? $responseBody : [];
    }

    public function queryPaymentStatus(string $orderReference): array
    {
        $config = config('services.clickpesa');

        if (! ($config['enabled'] ?? false)) {
            throw ValidationException::withMessages([
                'clickpesa' => ['ClickPesa integration is not enabled.'],
            ]);
        }

        if (($config['mode'] ?? 'mock') === 'mock') {
            return [
                [
                    'id' => 'MOCK-USSD-' . $orderReference,
                    'status' => 'SUCCESS',
                    'paymentReference' => 'MOCK-PAYMENT-' . $orderReference,
                    'paymentPhoneNumber' => '255700000000',
                    'orderReference' => $orderReference,
                    'collectedAmount' => 50000,
                    'collectedCurrency' => 'TZS',
                    'message' => 'Mock payment completed successfully.',
                    'updatedAt' => now()->toIso8601String(),
                    'createdAt' => now()->subMinute()->toIso8601String(),
                    'customer' => [
                        'customerName' => 'Mock Sender',
                        'customerPhoneNumber' => '255700000000',
                        'customerEmail' => 'mock@example.com',
                    ],
                    'clientId' => $config['client_id'] ?? 'MOCK-CLIENT',
                ],
            ];
        }

        $baseUrl = $config['payment_status_url'] ?? null;
        if (! $baseUrl) {
            throw ValidationException::withMessages([
                'clickpesa' => ['ClickPesa payment status URL is missing.'],
            ]);
        }

        $token = $this->tokenService->generateToken();
        $url = rtrim($baseUrl, '/') . '/' . urlencode($orderReference);

        Log::info('ClickPesa payment status query prepared.', [
            'url' => $url,
            'order_reference' => $orderReference,
        ]);

        $response = Http::timeout((int) ($config['timeout_seconds'] ?? 30))
            ->acceptJson()
            ->withHeaders([
                'Authorization' => $token,
            ])
            ->get($url);

        $responseBody = $response->json() ?: $response->body();

        Log::info('ClickPesa payment status response received.', [
            'status' => $response->status(),
            'body' => $responseBody,
        ]);

        if (! $response->successful()) {
            Log::error('ClickPesa payment status query failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw ValidationException::withMessages([
                'clickpesa' => [
                    is_array($responseBody)
                        ? (data_get($responseBody, 'message') ?? 'Failed to query ClickPesa payment status.')
                        : 'Failed to query ClickPesa payment status.'
                ],
            ]);
        }

        if (! is_array($responseBody)) {
            return [];
        }

        return $responseBody;
    }
}