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
        'maximum_initial_investment',
        'minimum_additional_investment',
        'maximum_additional_investment',
        'minimum_redemption_amount',
        'minimum_balance_after_redemption',
        'lock_in_period_years',

        'switching_allowed',
        'sip_allowed',
        'minimum_sip_amount',
        'sip_frequency',

        'option_growth',
        'option_dividend',
        'option_dividend_reinvestment',

        'exit_fee_percentage',
        'exit_fee_period_days',

        'currency',
        'status',

        'is_active',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'minimum_initial_investment' => 'decimal:2',
            'maximum_initial_investment' => 'decimal:2',
            'minimum_additional_investment' => 'decimal:2',
            'maximum_additional_investment' => 'decimal:2',
            'minimum_redemption_amount' => 'decimal:2',
            'minimum_balance_after_redemption' => 'decimal:2',
            'minimum_sip_amount' => 'decimal:2',

            'lock_in_period_years' => 'integer',
            'exit_fee_period_days' => 'integer',

            'switching_allowed' => 'boolean',
            'sip_allowed' => 'boolean',
            'option_growth' => 'boolean',
            'option_dividend' => 'boolean',
            'option_dividend_reinvestment' => 'boolean',
            'is_active' => 'boolean',

            'metadata' => 'array',

            'exit_fee_percentage' => 'decimal:2',
            'sip_frequency' => 'string',
            'currency' => 'string',
            'status' => 'string',
        ];
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = (string) \Str::uuid();
            }
        });
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'active');
    }

    public function allowsSip(): bool
    {
        return $this->sip_allowed;
    }

    public function allowsGrowth(): bool
    {
        return $this->option_growth;
    }

    public function hasLockIn(): bool
    {
        return $this->lock_in_period_years > 0;
    }
}