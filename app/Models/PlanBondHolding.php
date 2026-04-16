<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlanBondHolding extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'plan_id',
        'bond_name',
        'bond_code',
        'principal_amount',
        'coupon_rate_percent',
        'issue_date',
        'investment_date',
        'maturity_date',
        'coupon_frequency',
        'last_coupon_date',
        'next_coupon_date',
        'accrued_interest_amount',
        'face_value',
        'holding_status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'principal_amount' => 'decimal:2',
            'coupon_rate_percent' => 'decimal:6',
            'issue_date' => 'date',
            'investment_date' => 'date',
            'maturity_date' => 'date',
            'last_coupon_date' => 'date',
            'next_coupon_date' => 'date',
            'accrued_interest_amount' => 'decimal:6',
            'face_value' => 'decimal:2',
        ];
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}