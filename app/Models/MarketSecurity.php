<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MarketSecurity extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'source',
        'source_security_reference',
        'symbol',
        'security_id',
        'company_name',
        'security_type',
        'market_segment',
        'is_active',
        'last_synced_at',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_synced_at' => 'datetime',
            'raw_payload' => 'array',
        ];
    }

    public function priceSnapshots()
    {
        return $this->hasMany(MarketPriceSnapshot::class);
    }

    public function equityHoldings()
    {
        return $this->hasMany(PlanEquityHolding::class);
    }
}