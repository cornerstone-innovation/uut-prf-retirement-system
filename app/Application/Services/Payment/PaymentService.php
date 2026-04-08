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
        private readonly ClickPesaUssdPushService $ussdPushService
    ) {
    }

    public function initialize(
        PurchaseRequest $purchaseRequest,
        string $paymentMethod,
        ?string $phoneNumber = null,
        ?int $createdBy = null
    ): array {
        if ($purchaseRequest->status !== 'pending_payment') {
            throw ValidationException::withMessages([
                'purchase_request' => ['Only purchase requests that are ready for payment can be initialized for payment.'],
            ]);
        }

        if ($paymentMethod !== 'mobile_money') {
            throw ValidationException::withMessages([
                'payment_method' => ['Only mobile money is supported for the current ClickPesa USSD push integration.'],
            ]);
        }

        $normalizedPhoneNumber = $this->normalizePhoneNumber(
            $phoneNumber
                ?: $purchaseRequest->investor?->phone_primary
                ?: $purchaseRequest->investor?->phone
        );

        if (! $normalizedPhoneNumber) {
            throw ValidationException::withMessages([
                'phone_number' => ['A valid mobile money phone number is required to initiate USSD push.'],
            ]);
        }

        return DB::transaction(function () use (
            $purchaseRequest,
            $paymentMethod,
            $normalizedPhoneNumber,
            $createdBy
        ) {
            $nextPaymentNumber = (Payment::max('id') ?? 0) + 1;
            $reference = 'PAY' . str_pad((string) $nextPaymentNumber, 6, '0', STR_PAD_LEFT);

            $payment = Payment::create([
                'uuid' => (string) Str::uuid(),
                'purchase_request_id' => $purchaseRequest->id,
                'investor_id' => $purchaseRequest->investor_id,
                'provider' => 'clickpesa',
                'reference' => $reference,
                'amount' => $purchaseRequest->amount,
                'currency' => $purchaseRequest->currency,
                'payment_method' => $paymentMethod,
                'status' => 'pending',
                'metadata' => [
                    'phone_number' => $normalizedPhoneNumber,
                    'flow' => 'ussd_push',
                ],
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
                    'phone_number' => $normalizedPhoneNumber,
                    'order_reference' => $payment->reference,
                ],
                'initiated_at' => now(),
            ]);

            $previewResponse = $this->ussdPushService->preview([
                'amount' => (string) $payment->amount,
                'currency' => $payment->currency,
                'orderReference' => $payment->reference,
                'phoneNumber' => $normalizedPhoneNumber,
                'fetchSenderDetails' => true,
            ]);

            $initiateResponse = $this->ussdPushService->initiate([
                'amount' => (string) $payment->amount,
                'currency' => $payment->currency,
                'orderReference' => $payment->reference,
                'phoneNumber' => $normalizedPhoneNumber,
            ]);

            $providerTransactionId = data_get($initiateResponse, 'id');
            $providerStatus = data_get($initiateResponse, 'status', 'PROCESSING');
            $providerChannel = data_get($initiateResponse, 'channel');

            $mappedStatus = $this->mapProviderStatusToLocalStatus($providerStatus);

            $payment->update([
                'provider_reference' => $providerTransactionId ?: $payment->reference,
                'status' => $mappedStatus,
                'metadata' => array_merge($payment->metadata ?? [], [
                    'phone_number' => $normalizedPhoneNumber,
                    'flow' => 'ussd_push',
                    'preview_response' => $previewResponse,
                    'initiate_response' => $initiateResponse,
                    'channel' => $providerChannel,
                ]),
                'updated_by' => $createdBy,
            ]);

            $attempt->update([
                'status' => $mappedStatus === 'failed' ? 'failed' : 'success',
                'response_payload' => [
                    'preview_response' => $previewResponse,
                    'initiate_response' => $initiateResponse,
                ],
                'completed_at' => now(),
            ]);

            if ($mappedStatus === 'processing') {
                $purchaseRequest->update([
                    'status' => 'processing',
                    'updated_by' => $createdBy,
                ]);
            }

            if ($mappedStatus === 'paid') {
                $payment = $this->markPaid($payment, [
                    'preview_response' => $previewResponse,
                    'initiate_response' => $initiateResponse,
                ]);
            }

            if ($mappedStatus === 'failed') {
                $payment = $this->markFailed($payment, [
                    'preview_response' => $previewResponse,
                    'initiate_response' => $initiateResponse,
                ]);
            }

            return [
                'payment' => $payment->fresh(['attempts', 'purchaseRequest']),
                'payment_attempt' => $attempt->fresh(),
                'preview_response' => $previewResponse,
                'initiate_response' => $initiateResponse,
                'provider_status' => $providerStatus,
                'provider_transaction_id' => $providerTransactionId,
            ];
        });
    }

    public function syncPaymentStatus(Payment $payment): Payment
    {
        $payment->loadMissing(['purchaseRequest', 'attempts']);

        $statusResponses = $this->ussdPushService->queryPaymentStatus($payment->reference);

        $statusRow = collect($statusResponses)->first();

        if (! $statusRow) {
            return $payment->fresh(['purchaseRequest', 'attempts']);
        }

        $providerStatus = data_get($statusRow, 'status');
        $mappedStatus = $this->mapProviderStatusToLocalStatus($providerStatus);

        if ($mappedStatus === 'paid') {
            return $this->markPaid($payment, [
                'status_query' => $statusRow,
            ]);
        }

        if ($mappedStatus === 'failed') {
            return $this->markFailed($payment, [
                'status_query' => $statusRow,
            ]);
        }

        $payment->update([
            'status' => $mappedStatus,
            'metadata' => array_merge($payment->metadata ?? [], [
                'last_status_query' => $statusRow,
            ]),
        ]);

        return $payment->fresh(['purchaseRequest', 'attempts']);
    }

    public function markPaid(Payment $payment, array $payload = []): Payment
    {
        return DB::transaction(function () use ($payment, $payload) {
            if ($payment->status === 'paid') {
                return $payment->fresh(['purchaseRequest', 'attempts']);
            }

            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'metadata' => array_merge($payment->metadata ?? [], [
                    'paid_payload' => $payload,
                ]),
            ]);

            $payment->purchaseRequest?->update([
                'status' => 'payment_received',
            ]);

            $latestAttempt = $payment->attempts()->latest('id')->first();
            if ($latestAttempt) {
                $latestAttempt->update([
                    'status' => 'success',
                    'completed_at' => now(),
                    'response_payload' => array_merge($latestAttempt->response_payload ?? [], [
                        'paid_payload' => $payload,
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
                'metadata' => array_merge($payment->metadata ?? [], [
                    'failed_payload' => $payload,
                ]),
            ]);

            $payment->purchaseRequest?->update([
                'status' => 'pending_payment',
            ]);

            $latestAttempt = $payment->attempts()->latest('id')->first();
            if ($latestAttempt) {
                $latestAttempt->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'response_payload' => array_merge($latestAttempt->response_payload ?? [], [
                        'failed_payload' => $payload,
                    ]),
                ]);
            }

            return $payment->fresh(['purchaseRequest', 'attempts']);
        });
    }

    protected function mapProviderStatusToLocalStatus(?string $providerStatus): string
    {
        return match (strtoupper((string) $providerStatus)) {
            'SUCCESS', 'SETTLED' => 'paid',
            'FAILED' => 'failed',
            'PROCESSING' => 'processing',
            'PENDING' => 'pending',
            default => 'pending',
        };
    }

    protected function normalizePhoneNumber(?string $phoneNumber): ?string
    {
        if (! $phoneNumber) {
            return null;
        }

        $normalized = preg_replace('/\D+/', '', $phoneNumber ?? '');

        if (! $normalized) {
            return null;
        }

        if (str_starts_with($normalized, '0')) {
            $normalized = '255' . ltrim($normalized, '0');
        }

        if (! str_starts_with($normalized, '255')) {
            return null;
        }

        if (strlen($normalized) < 12 || strlen($normalized) > 15) {
            return null;
        }

        return $normalized;
    }
}