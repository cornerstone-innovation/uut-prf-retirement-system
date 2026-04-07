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

        if (empty($config['checkout_link_url'])) {
            throw ValidationException::withMessages([
                'clickpesa' => ['ClickPesa checkout link URL is missing.'],
            ]);
        }

        $token = $this->getAuthorizationToken($config);

        $requestPayload = $this->buildCheckoutPayload($payment, $payload, $config);

        // Checksum is documented for hosted checkout requests.
        // Some accounts may enforce it; if secret is not configured, we skip it for now.
        if (! empty($config['api_secret'])) {
            $requestPayload['checksum'] = $this->checksumService->generate(
                $config['api_secret'],
                $requestPayload
            );
        }

        Log::info('ClickPesa checkout request prepared.', [
            'payment_id' => $payment->id,
            'payment_reference' => $payment->reference,
            'checkout_link_url' => $config['checkout_link_url'],
            'payload' => $requestPayload,
        ]);

        $response = Http::timeout((int) ($config['timeout_seconds'] ?? 30))
            ->acceptJson()
            ->withToken($token)
            ->post($config['checkout_link_url'], $requestPayload);

        Log::info('ClickPesa checkout response received.', [
            'payment_id' => $payment->id,
            'status' => $response->status(),
            'body' => $response->json() ?: $response->body(),
        ]);

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
            'provider_reference' => data_get($data, 'paymentReference')
                ?? data_get($data, 'data.paymentReference')
                ?? data_get($data, 'orderReference')
                ?? data_get($data, 'data.orderReference')
                ?? $payment->reference,
            'status' => 'pending',
            'checkout_url' => data_get($data, 'checkoutLink')
                ?? data_get($data, 'data.checkoutLink')
                ?? data_get($data, 'checkoutUrl')
                ?? data_get($data, 'data.checkoutUrl'),
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

        if (empty($config['client_id']) || empty($config['api_key'])) {
            throw ValidationException::withMessages([
                'clickpesa' => ['ClickPesa client ID or API key is missing.'],
            ]);
        }

        Log::info('ClickPesa auth request prepared.', [
            'auth_url' => $config['auth_url'],
            'client_id_present' => ! empty($config['client_id']),
            'api_key_present' => ! empty($config['api_key']),
        ]);

        $response = Http::timeout((int) ($config['timeout_seconds'] ?? 30))
            ->acceptJson()
            ->withHeaders([
                'client-id' => $config['client_id'],
                'api-key' => $config['api_key'],
            ])
            ->post($config['auth_url']);

        Log::info('ClickPesa auth response received.', [
            'status' => $response->status(),
            'body' => $response->json() ?: $response->body(),
        ]);

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

        $token = data_get($data, 'token')
            ?? data_get($data, 'data.token')
            ?? data_get($data, 'access_token')
            ?? data_get($data, 'data.access_token');

        if (! $token) {
            throw ValidationException::withMessages([
                'clickpesa' => ['ClickPesa authorization token was not returned.'],
            ]);
        }

        return $token;
    }

    protected function buildCheckoutPayload(Payment $payment, array $payload, array $config): array
    {
        $payment->loadMissing(['purchaseRequest', 'investor']);

        $purchaseRequest = $payment->purchaseRequest;
        $investor = $payment->investor;

        return [
            'totalPrice' => (string) $payment->amount,
            'orderReference' => $payment->reference,
            'orderCurrency' => $payment->currency ?: ($config['currency'] ?? 'TZS'),
            'customerName' => $investor?->full_name ?? 'Investor',
            'customerEmail' => $investor?->email
                ?? data_get($investor, 'user.email')
                ?? null,
            'customerPhone' => $investor?->phone_primary
                ?? $investor?->phone
                ?? null,
            'description' => "Investment payment for purchase request {$purchaseRequest->uuid}",
        ];
    }
}