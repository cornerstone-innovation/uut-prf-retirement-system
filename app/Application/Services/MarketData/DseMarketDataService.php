<?php

namespace App\Application\Services\MarketData;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class DseMarketDataService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.dse.base_url', 'https://api.dse.co.tz/api'), '/');
    }

    public function fetchEquities(): array
    {
        $response = Http::timeout(30)
            ->acceptJson()
            ->get($this->baseUrl . '/market-data', [
                'isBond' => 'false',
            ]);

        if (! $response->successful()) {
            Log::error('DSE market data request failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw ValidationException::withMessages([
                'market_data' => ['Failed to fetch equity market data from DSE.'],
            ]);
        }

        $json = $response->json();

        if (! is_array($json)) {
            Log::error('DSE market data response is not an array.', [
                'response' => $json,
            ]);

            return [];
        }

        return collect($json)
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item) {
                return $this->transformMarketDataRow($item);
            })
            ->filter()
            ->values()
            ->all();
    }

    public function searchEquities(?string $query = null): array
    {
        $rows = $this->fetchEquities();

        if (! $query) {
            return $rows;
        }

        $needle = mb_strtolower(trim($query));

        return collect($rows)
            ->filter(function (array $row) use ($needle) {
                return str_contains(mb_strtolower((string) ($row['symbol'] ?? '')), $needle)
                    || str_contains(mb_strtolower((string) ($row['company_name'] ?? '')), $needle)
                    || str_contains(mb_strtolower((string) ($row['security_desc'] ?? '')), $needle)
                    || str_contains(mb_strtolower((string) ($row['security_id'] ?? '')), $needle);
            })
            ->values()
            ->all();
    }

    protected function transformMarketDataRow(array $item): ?array
    {
        $company = Arr::get($item, 'company', []);
        $security = Arr::get($item, 'security', []);

        $symbol = Arr::get($security, 'symbol')
            ?: Arr::get($company, 'symbol')
            ?: Arr::get($company, 'name');

        if (! $symbol) {
            return null;
        }

        return [
            'source' => 'dse',
            'source_security_reference' => Arr::get($item, 'securityReference'),
            'symbol' => $symbol,
            'security_id' => Arr::get($security, 'securityId') ?: Arr::get($company, 'securityId'),
            'company_name' => Arr::get($item, 'companyDescription')
                ?: Arr::get($security, 'securityDesc')
                ?: Arr::get($company, 'name'),
            'security_desc' => Arr::get($security, 'securityDesc')
                ?: Arr::get($item, 'companyDescription'),
            'security_type' => Arr::get($security, 'securityType'),
            'market_segment' => Arr::get($security, 'marketSegmentID'),
            'market_price' => Arr::get($item, 'marketPrice'),
            'opening_price' => Arr::get($item, 'openingPrice'),
            'change' => Arr::get($item, 'change'),
            'percentage_change' => Arr::get($item, 'percentageChange'),
            'high' => Arr::get($item, 'high'),
            'low' => Arr::get($item, 'low'),
            'volume' => Arr::get($item, 'volume'),
            'captured_at' => Arr::get($item, 'time'),
            'raw_payload' => $item,
        ];
    }
}