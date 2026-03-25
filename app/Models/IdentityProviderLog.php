<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IdentityProviderLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'provider',
        'request_type',
        'reference',
        'investor_id',
        'onboarding_session_id',
        'request_payload',
        'response_payload',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_payload' => 'array',
        ];
    }
}