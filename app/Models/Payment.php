<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'purchase_request_id',
        'investor_id',
        'provider',
        'reference',
        'provider_reference',
        'amount',
        'currency',
        'payment_method',
        'status',
        'paid_at',
        'cancelled_at',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }

    public function attempts()
    {
        return $this->hasMany(PaymentAttempt::class);
    }

    public function callbacks()
    {
        return $this->hasMany(PaymentCallback::class);
    }
}