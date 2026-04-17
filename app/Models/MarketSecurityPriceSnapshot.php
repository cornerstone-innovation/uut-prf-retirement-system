<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MarketSecurityPriceSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'market_security_id',
        'price_date',
        'captured_at',
        'market_price',
        'opening_price',
        'change_amount',
        'percentage_change',
        'high_price',
        'low_price',
        'volume',
        'source',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'price_date' => 'date',
            'captured_at' => 'datetime',
            'market_price' => 'decimal:6',
            'opening_price' => 'decimal:6',
            'change_amount' => 'decimal:6',
            'percentage_change' => 'decimal:6',
            'high_price' => 'decimal:6',
            'low_price' => 'decimal:6',
            'raw_payload' => 'array',
        ];
    }

    public function marketSecurity()
    {
        return $this->belongsTo(MarketSecurity::class);
    }
}