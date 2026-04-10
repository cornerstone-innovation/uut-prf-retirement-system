<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OtpVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'phone_number',
        'purpose',
        'code',
        'provider',
        'provider_reference',
        'external_pin_id',
        'status',
        'attempts',
        'expires_at',
        'verified_at',
        'verified_externally',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'verified_externally' => 'boolean',
            'metadata' => 'array',
        ];
    }
}