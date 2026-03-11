<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyDirector extends Model
{
    protected $fillable = [
        'investor_id',
        'full_name',
        'national_id_number',
        'passport_number',
        'nationality',
        'role',
        'has_signing_authority',
        'ownership_percentage',
        'identity_verification_status',
        'smile_verification_id',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'has_signing_authority' => 'boolean',
            'ownership_percentage' => 'decimal:2',
            'verified_at' => 'datetime',
        ];
    }

    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }

    public function identityVerifications()
    {
        return $this->hasMany(IdentityVerification::class, 'entity_id')
            ->where('entity_type', 'company_director');
    }
}