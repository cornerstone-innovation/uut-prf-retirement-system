<?php

namespace App\Application\Services\Payment;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ClickPesaPaymentService implements PaymentProviderInterface
{
    public function __construct(
        private readonly ClickPesaChecksumService $checksumService
    ) {
    }

    public function initialize(Payment $payment, array $payload = []): array
    {
        $config = config('services.clickpesa');

        if (! ($config['enabled'] ?? false)) {
            throw ValidationException::withMessages([
                'clickpesa' => ['ClickPesa integration is not enabled.'],
            ]);
        }

        if (($config['mode'] ?? 'mock') === 'mock') {
            return [
                'provider' => 'clickpesa',
                'provider_reference' => 'MOCK-CP-' . $payment->reference,
                'status' => 'pending',
                'checkout_url' => url("/mock/clickpesa/checkout/{$payment->reference}"),
                'payment_instructions' => [
                    'message' => 'Mock ClickPesa payment initialized successfully.',
                    'channels' => ['mobile_money', 'card'],
                ],
                'raw_response' => [
                    'mock' => true,
                    'reference' => $payment->reference,
                ],
            ];
        }

        $token = $this->getAuthorizationToken($config);

        $requestPayload = $this->buildCheckoutPayload($payment, $payload, $config);

        $secret = $config['api_secret'] ?? null;
        if (! $secret) {
            throw ValidationException::withMessages([
                'clickpesa' => ['ClickPesa API secret is missing.'],
            ]);
        }

        $requestPayload['checksumMethod'] = 'canonical';
        $requestPayload['checksum'] = $this->checksumService->generate($secret, $requestPayload);

        $response = Http::timeout((int) ($config['timeout_seconds'] ?? 30))
            ->acceptJson()
            ->withToken($token)
            ->post($config['checkout_link_url'], $requestPayload);

        if (! $response->successful()) {
            Log::error('ClickPesa checkout initialization failed.', [
                'payment_id' => $payment->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw ValidationException::withMessages([
                'clickpesa' => ['Failed to initialize ClickPesa checkout.'],
            ]);
        }

        $data = $response->json();

        return [
            'provider' => 'clickpesa',
            'provider_reference' => data_get($data, 'data.paymentReference')
                ?? data_get($data, 'paymentReference')
                ?? data_get($data, 'data.reference')
                ?? data_get($data, 'reference')
                ?? $payment->reference,
            'status' => 'pending',
            'checkout_url' => data_get($data, 'data.checkoutUrl')
                ?? data_get($data, 'checkoutUrl')
                ?? data_get($data, 'data.url')
                ?? data_get($data, 'url'),
            'payment_instructions' => [
                'message' => 'ClickPesa checkout initialized successfully.',
                'channels' => [$payload['payment_method'] ?? 'mobile_money'],
            ],
            'raw_response' => $data,
        ];
    }

    protected function getAuthorizationToken(array $config): string
    {
        if (empty($config['auth_url'])) {
            throw ValidationException::withMessages([
                'clickpesa' => ['ClickPesa auth URL is missing.'],
            ]);
        }

        $authPayload = [
            'clientId' => $config['client_id'],
            'apiKey' => $config['api_key'],
        ];

        $secret = $config['api_secret'] ?? null;
        if (! $secret) {
            throw ValidationException::withMessages([
                'clickpesa' => ['ClickPesa API secret is missing.'],
            ]);
        }

        $authPayload['checksumMethod'] = 'canonical';
        $authPayload['checksum'] = $this->checksumService->generate($secret, $authPayload);

        $response = Http::timeout((int) ($config['timeout_seconds'] ?? 30))
            ->acceptJson()
            ->post($config['auth_url'], $authPayload);

        if (! $response->successful()) {
            Log::error('ClickPesa authorization failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw ValidationException::withMessages([
                'clickpesa' => ['Failed to generate ClickPesa authorization token.'],
            ]);
        }

        $data = $response->json();

        $token = data_get($data, 'data.token')
            ?? data_get($data, 'token')
            ?? data_get($data, 'access_token');

        if (! $token) {
            throw ValidationException::withMessages([
                'clickpesa' => ['ClickPesa authorization token was not returned.'],
            ]);
        }

        return $token;
    }

    protected function buildCheckoutPayload(Payment $payment, array $payload, array $config): array
    {
        $purchaseRequest = $payment->purchaseRequest;
        $investor = $payment->investor;

        return [
            'orderReference' => $payment->reference,
            'amount' => (string) $payment->amount,
            'currency' => $payment->currency ?: ($config['currency'] ?? 'TZS'),
            'description' => "Investment payment for purchase request {$purchaseRequest->uuid}",
            'paymentMethod' => $payload['payment_method'] ?? $payment->payment_method,
            'returnUrl' => $config['return_url'],
            'webhookUrl' => $config['webhook_url'],
            'customer' => [
                'customerName' => $investor?->full_name ?? 'Investor',
                'customerEmail' => $investor?->email ?? $investor?->user?->email ?? null,
                'customerPhoneNumber' => $investor?->phone_primary ?? null,
            ],
            'metadata' => [
                'payment_id' => $payment->id,
                'purchase_request_id' => $purchaseRequest?->id,
                'investor_id' => $payment->investor_id,
            ],
        ];
    }
}