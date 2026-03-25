<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KycTierRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'kyc_tier_id',
        'requires_phone_verified',
        'requires_nida_verified',
        'requires_identity_verified',
        'requires_profile_completed',
        'requires_documents_completed',
        'requires_admin_approval',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'requires_phone_verified' => 'boolean',
            'requires_nida_verified' => 'boolean',
            'requires_identity_verified' => 'boolean',
            'requires_profile_completed' => 'boolean',
            'requires_documents_completed' => 'boolean',
            'requires_admin_approval' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function tier()
    {
        return $this->belongsTo(KycTier::class, 'kyc_tier_id');
    }
}