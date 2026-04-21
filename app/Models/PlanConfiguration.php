<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlanConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'plan_id',
        'plan_family',
        'valuation_method',
        'holding_scope',
        'initial_offer_start_date',
        'initial_offer_end_date',
        'initial_offer_duration_days',
        'initial_offer_price_per_unit',
        'phase_status',
        'live_phase_started_at',
        'market_close_time',
        'market_close_timezone',
        'auto_calculate_nav',
        'allow_nav_override',
        'allow_phase_override',
        'is_phase_overridden',
        'phase_override_reason',
        'phase_override_by',
        'phase_override_at',
        'total_units_on_offer',
        'initial_offer_price',
        'allow_post_offer_sales',
        'unit_sale_cap_type',

    ];

    protected function casts(): array
    {
        return [
            'initial_offer_start_date' => 'date',
            'initial_offer_end_date' => 'date',
            'initial_offer_price_per_unit' => 'decimal:6',
            'live_phase_started_at' => 'datetime',
            'auto_calculate_nav' => 'boolean',
            'allow_nav_override' => 'boolean',
            'allow_phase_override' => 'boolean',
            'is_phase_overridden' => 'boolean',
            'phase_override_at' => 'datetime',
            'total_units_on_offer' => 'decimal:6',
            'initial_offer_price' => 'decimal:6',
            'initial_offer_start_date' => 'date',
            'initial_offer_end_date' => 'date',
            'allow_post_offer_sales' => 'boolean',
        ];
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function phaseOverrideUser()
    {
        return $this->belongsTo(User::class, 'phase_override_by');
    }
}