<?php

namespace App\Application\Services\Payment;

class ClickPesaChecksumService
{
    public function generate(string $secretKey, array $payload): string
    {
        $payloadForChecksum = $this->stripChecksumFields($payload);
        $canonicalPayload = $this->canonicalize($payloadForChecksum);
        $json = json_encode($canonicalPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return hash_hmac('sha256', $json, $secretKey);
    }

    public function validate(string $secretKey, array $payload, ?string $receivedChecksum): bool
    {
        if (! $receivedChecksum) {
            return false;
        }

        $computed = $this->generate($secretKey, $payload);

        return hash_equals($computed, $receivedChecksum);
    }

    protected function stripChecksumFields(array $payload): array
    {
        unset($payload['checksum'], $payload['checksumMethod']);

        return $payload;
    }

    protected function canonicalize(mixed $value): mixed
    {
        if (is_array($value)) {
            if ($this->isAssoc($value)) {
                ksort($value);
            }

            foreach ($value as $key => $item) {
                $value[$key] = $this->canonicalize($item);
            }
        }

        return $value;
    }

    protected function isAssoc(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }
}