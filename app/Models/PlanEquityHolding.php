<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlanEquityHolding extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'plan_id',
        'market_security_id',
        'quantity',
        'invested_amount',
        'average_cost_per_share',
        'trade_date',
        'holding_status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:6',
            'invested_amount' => 'decimal:2',
            'average_cost_per_share' => 'decimal:6',
            'trade_date' => 'date',
        ];
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function marketSecurity()
    {
        return $this->belongsTo(MarketSecurity::class);
    }
}