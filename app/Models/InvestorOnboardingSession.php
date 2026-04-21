<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvestorOnboardingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'investor_type',
        'phone_number',
        'nida_number',
        'phone_verified_at',
        'nida_verified_at',
        'current_step',
        'status',
        'prefill_data',
        'payload_snapshot',
        'metadata',
        'expires_at',
        'document_type',
        'document_number',
        'document_original_name',
        'document_mime_type',
        'document_storage_disk',
        'document_storage_path',
    ];

    protected function casts(): array
    {
        return [
            'phone_verified_at' => 'datetime',
            'nida_verified_at' => 'datetime',
            'prefill_data' => 'array',
            'payload_snapshot' => 'array',
            'metadata' => 'array',
            'expires_at' => 'datetime',
        ];
    }
}