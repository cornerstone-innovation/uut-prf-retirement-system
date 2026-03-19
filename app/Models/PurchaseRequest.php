<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'investor_id',
        'plan_id',
        'amount',
        'currency',
        'request_type',
        'status',
        'kyc_tier_at_request',
        'is_sip',
        'notes',
        'metadata',
        'created_by',
        'updated_by',
        'submitted_at',
        'cancelled_at',
        'option',
        'pricing_date',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_sip' => 'boolean',
            'metadata' => 'array',
            'submitted_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'pricing_date' => 'date',
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
        public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment()
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }
    public function investmentTransaction()
    {
        return $this->hasOne(InvestmentTransaction::class);
    }
}