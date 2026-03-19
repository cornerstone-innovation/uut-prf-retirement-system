<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvestmentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'investor_id',
        'plan_id',
        'purchase_request_id',
        'transaction_type',
        'status',
        'gross_amount',
        'net_amount',
        'units',
        'nav_per_unit',
        'currency',
        'option',
        'trade_date',
        'pricing_date',
        'processed_at',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'gross_amount' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'units' => 'decimal:6',
            'nav_per_unit' => 'decimal:6',
            'trade_date' => 'date',
            'pricing_date' => 'date',
            'processed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function unitLots()
    {
        return $this->hasMany(UnitLot::class);
    }
}