<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvestorKycProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'kyc_tier',
        'identity_verified_at',
        'profile_completed_at',
        'documents_completed_at',
        'investor_id',
        'kyc_reference',
        'document_status',
        'identity_verification_status',
        'address_verification_status',
        'tax_verification_status',
        'pep_check_status',
        'sanctions_check_status',
        'aml_risk_level',
        'review_notes',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'approved_at',
        'rejected_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
                'submitted_at' => 'datetime',
                'reviewed_at' => 'datetime',
                'approved_at' => 'datetime',
                'rejected_at' => 'datetime',
                'expires_at' => 'datetime',
                'identity_verified_at' => 'datetime',
                'profile_completed_at' => 'datetime',
                'documents_completed_at' => 'datetime',
        ];
    }

    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function documents()
    {
        return $this->hasMany(InvestorDocument::class, 'investor_kyc_profile_id');
    }
}