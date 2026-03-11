<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IdentityVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'entity_type',
        'entity_id',
        'provider',
        'verification_type',
        'status',
        'provider_reference',
        'score',
        'failure_reason',
        'request_payload',
        'response_payload',
        'metadata',
        'reviewed_by',
        'reviewed_at',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'request_payload' => 'array',
            'response_payload' => 'array',
            'metadata' => 'array',
            'reviewed_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}