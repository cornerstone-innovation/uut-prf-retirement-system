<?php

namespace App\Application\Services\Payment;

use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\PurchaseRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private readonly MockClickPesaPaymentService $provider
    ) {
    }

    public function initialize(
        PurchaseRequest $purchaseRequest,
        string $paymentMethod,
        ?int $createdBy = null
    ): array {
        if ($purchaseRequest->status !== 'pending_payment') {
            throw ValidationException::withMessages([
                'purchase_request' => ['Only purchase requests pending payment can be initialized for payment.'],
            ]);
        }

        return DB::transaction(function () use ($purchaseRequest, $paymentMethod, $createdBy) {
            $payment = Payment::create([
                'uuid' => (string) Str::uuid(),
                'purchase_request_id' => $purchaseRequest->id,
                'investor_id' => $purchaseRequest->investor_id,
                'provider' => 'clickpesa',
                'reference' => 'PAY-' . str_pad((string) (Payment::max('id') + 1), 6, '0', STR_PAD_LEFT),
                'amount' => $purchaseRequest->amount,
                'currency' => $purchaseRequest->currency,
                'payment_method' => $paymentMethod,
                'status' => 'pending',
                'created_by' => $createdBy,
                'updated_by' => $createdBy,
            ]);

            $attempt = PaymentAttempt::create([
                'uuid' => (string) Str::uuid(),
                'payment_id' => $payment->id,
                'attempt_number' => 1,
                'provider' => 'clickpesa',
                'status' => 'initiated',
                'request_payload' => [
                    'payment_method' => $paymentMethod,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                ],
                'initiated_at' => now(),
            ]);

            $providerResponse = $this->provider->initialize($payment, [
                'payment_method' => $paymentMethod,
            ]);

            $payment->update([
                'provider_reference' => $providerResponse['provider_reference'] ?? null,
                'status' => $providerResponse['status'] ?? 'pending',
                'metadata' => [
                    'checkout_url' => $providerResponse['checkout_url'] ?? null,
                    'payment_instructions' => $providerResponse['payment_instructions'] ?? null,
                ],
                'updated_by' => $createdBy,
            ]);

            $attempt->update([
                'response_payload' => $providerResponse['raw_response'] ?? $providerResponse,
            ]);

            return [
                'payment' => $payment->fresh(['attempts']),
                'payment_attempt' => $attempt->fresh(),
                'checkout_url' => $providerResponse['checkout_url'] ?? null,
                'payment_instructions' => $providerResponse['payment_instructions'] ?? null,
            ];
        });
    }

    public function markPaid(Payment $payment, array $payload = []): Payment
    {
        return DB::transaction(function () use ($payment, $payload) {
            if ($payment->status === 'paid') {
                return $payment->fresh();
            }

            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            $payment->purchaseRequest->update([
                'status' => 'payment_received',
            ]);

            $latestAttempt = $payment->attempts()->latest('id')->first();
            if ($latestAttempt) {
                $latestAttempt->update([
                    'status' => 'success',
                    'completed_at' => now(),
                    'response_payload' => array_merge($latestAttempt->response_payload ?? [], [
                        'mock_callback' => $payload,
                    ]),
                ]);
            }

            return $payment->fresh(['purchaseRequest', 'attempts']);
        });
    }

    public function markFailed(Payment $payment, array $payload = []): Payment
    {
        return DB::transaction(function () use ($payment, $payload) {
            $payment->update([
                'status' => 'failed',
            ]);

            $latestAttempt = $payment->attempts()->latest('id')->first();
            if ($latestAttempt) {
                $latestAttempt->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'response_payload' => array_merge($latestAttempt->response_payload ?? [], [
                        'mock_callback' => $payload,
                    ]),
                ]);
            }

            return $payment->fresh(['purchaseRequest', 'attempts']);
        });
    }
}