<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MarketPriceSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'market_security_id',
        'valuation_date',
        'captured_at',
        'market_price',
        'opening_price',
        'high',
        'low',
        'change',
        'percentage_change',
        'volume',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'valuation_date' => 'date',
            'captured_at' => 'datetime',
            'market_price' => 'decimal:6',
            'opening_price' => 'decimal:6',
            'high' => 'decimal:6',
            'low' => 'decimal:6',
            'change' => 'decimal:6',
            'percentage_change' => 'decimal:6',
            'raw_payload' => 'array',
        ];
    }

   
    public function marketSecurity()
    {
        return $this->belongsTo(\App\Models\MarketSecurity::class, 'market_security_id', 'id');
    }
}