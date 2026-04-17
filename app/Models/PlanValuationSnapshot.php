<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlanValuationSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'plan_id',
        'valuation_date',
        'plan_family',
        'equity_market_value',
        'bond_market_value',
        'bond_accrued_interest',
        'cash_value',
        'total_gross_asset_value',
        'total_liabilities',
        'net_asset_value',
        'outstanding_units',
        'nav_per_unit',
        'price_source',
        'calculation_status',
        'notes',
        'breakdown',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'valuation_date' => 'date',
            'equity_market_value' => 'decimal:2',
            'bond_market_value' => 'decimal:2',
            'bond_accrued_interest' => 'decimal:2',
            'cash_value' => 'decimal:2',
            'total_gross_asset_value' => 'decimal:2',
            'total_liabilities' => 'decimal:2',
            'net_asset_value' => 'decimal:2',
            'outstanding_units' => 'decimal:6',
            'nav_per_unit' => 'decimal:6',
            'breakdown' => 'array',
        ];
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}