<?php

namespace App\Application\Services\Payment;

use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\PurchaseRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Application\Services\Purchase\PurchaseAllocationService;

class PaymentService
{
    public function __construct(
        private readonly ClickPesaUssdPushService $ussdPushService,
        private readonly PurchaseAllocationService $purchaseAllocationService
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

        $purchaseRequest->loadMissing(['investor']);

        $normalizedPhoneNumber = $this->normalizePhoneNumber(
            $phoneNumber
                ?: data_get($purchaseRequest, 'investor.phone_primary')
                ?: data_get($purchaseRequest, 'investor.phone')
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
            $reference = $this->generateUniquePaymentReference();

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

            $attemptNumber = ((int) $payment->attempts()->max('attempt_number')) + 1;
            if ($attemptNumber <= 0) {
                $attemptNumber = 1;
            }

            $attempt = PaymentAttempt::create([
                'uuid' => (string) Str::uuid(),
                'payment_id' => $payment->id,
                'attempt_number' => $attemptNumber,
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

            try {
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
            } catch (ValidationException $e) {
                $payment->update([
                    'status' => 'failed',
                    'updated_by' => $createdBy,
                ]);

                $attempt->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'response_payload' => [
                        'errors' => $e->errors(),
                    ],
                ]);

                throw $e;
            } catch (\Throwable $e) {
                $payment->update([
                    'status' => 'failed',
                    'updated_by' => $createdBy,
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'exception_message' => $e->getMessage(),
                    ]),
                ]);

                $attempt->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'response_payload' => [
                        'exception_message' => $e->getMessage(),
                    ],
                ]);

                throw $e;
            }

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

    public function syncPaymentStatus(Payment $payment, ?int $processedBy = null): array
    {
        $payment->loadMissing(['purchaseRequest', 'attempts', 'purchaseRequest.investmentTransaction']);

        $statusResponses = $this->ussdPushService->queryPaymentStatus($payment->reference);
        $statusRow = collect($statusResponses)->first();

        if (! $statusRow) {
            return [
                'payment' => $payment->fresh(['purchaseRequest', 'attempts']),
                'auto_allocated' => false,
                'allocation' => null,
            ];
        }

        $providerStatus = data_get($statusRow, 'status');
        $mappedStatus = $this->mapProviderStatusToLocalStatus($providerStatus);

        $allocationResult = null;
        $autoAllocated = false;

        if ($mappedStatus === 'paid') {
            $payment = $this->markPaid($payment, [
                'status_query' => $statusRow,
            ]);

            $purchaseRequest = $payment->purchaseRequest?->fresh([
                'investmentTransaction',
                'latestPayment',
                'plan',
            ]);

            if (
                $purchaseRequest &&
                $purchaseRequest->status === 'payment_received' &&
                ! $purchaseRequest->investmentTransaction
            ) {
                $allocationResult = $this->purchaseAllocationService->allocate(
                    purchaseRequest: $purchaseRequest,
                    processedBy: $processedBy
                );

                $autoAllocated = true;
            }

            return [
                'payment' => $payment->fresh(['purchaseRequest', 'attempts']),
                'auto_allocated' => $autoAllocated,
                'allocation' => $allocationResult,
            ];
        }

        if ($mappedStatus === 'failed') {
            $payment = $this->markFailed($payment, [
                'status_query' => $statusRow,
            ]);

            return [
                'payment' => $payment->fresh(['purchaseRequest', 'attempts']),
                'auto_allocated' => false,
                'allocation' => null,
            ];
        }

        $payment->update([
            'status' => $mappedStatus,
            'metadata' => array_merge($payment->metadata ?? [], [
                'last_status_query' => $statusRow,
            ]),
        ]);

        if (in_array(strtoupper((string) $providerStatus), ['SUCCESS', 'SETTLED'], true)) {
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            $purchaseRequest = $payment->purchaseRequest;

            if (
                $purchaseRequest &&
                $purchaseRequest->status !== 'allocated' &&
                ! $purchaseRequest->investmentTransaction
            ) {
                $allocationResult = $this->purchaseAllocationService->allocate(
                    purchaseRequest: $purchaseRequest->fresh(['investmentTransaction', 'latestPayment', 'plan']),
                    processedBy: $processedBy
                );
                $autoAllocated = true;
            }
        }

        return [
            'payment' => $payment->fresh(['purchaseRequest', 'attempts']),
            'auto_allocated' => $autoAllocated,
            'allocation' => $allocationResult,
        ];
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

    protected function generateUniquePaymentReference(): string
    {
        do {
            $reference = 'PAY' . now()->format('YmdHis') . strtoupper(Str::random(6));
        } while (Payment::query()->where('reference', $reference)->exists());

        return $reference;
    }
}