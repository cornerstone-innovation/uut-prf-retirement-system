<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UnitLot extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'investor_id',
        'plan_id',
        'investment_transaction_id',
        'original_units',
        'remaining_units',
        'nav_per_unit',
        'gross_amount',
        'acquired_date',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'original_units' => 'decimal:6',
            'remaining_units' => 'decimal:6',
            'nav_per_unit' => 'decimal:6',
            'gross_amount' => 'decimal:2',
            'acquired_date' => 'date',
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

    public function transaction()
    {
        return $this->belongsTo(InvestmentTransaction::class, 'investment_transaction_id');
    }
}