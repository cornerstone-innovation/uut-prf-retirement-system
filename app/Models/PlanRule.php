<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlanRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'plan_id',
        'minimum_initial_investment',
        'minimum_additional_investment',
        'minimum_redemption_amount',
        'minimum_balance_after_redemption',
        'lock_in_period_years',
        'switching_allowed',
        'sip_allowed',
        'minimum_sip_amount',
        'option_growth',
        'option_dividend',
        'option_dividend_reinvestment',
        'is_active',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'minimum_initial_investment' => 'decimal:2',
            'minimum_additional_investment' => 'decimal:2',
            'minimum_redemption_amount' => 'decimal:2',
            'minimum_balance_after_redemption' => 'decimal:2',
            'minimum_sip_amount' => 'decimal:2',
            'lock_in_period_years' => 'integer',
            'switching_allowed' => 'boolean',
            'sip_allowed' => 'boolean',
            'option_growth' => 'boolean',
            'option_dividend' => 'boolean',
            'option_dividend_reinvestment' => 'boolean',
            'is_active' => 'boolean',
            'metadata' => 'array',
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

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}