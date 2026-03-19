<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Investor extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'investor_number',
        'investor_type',
        'investor_category_id',
        'first_name',
        'middle_name',
        'last_name',
        'full_name',
        'company_name',
        'date_of_birth',
        'gender',
        'nationality',
        'national_id_number',
        'tax_identification_number',
        'onboarding_status',
        'kyc_status',
        'investor_status',
        'risk_profile',
        'occupation',
        'employer_name',
        'source_of_funds',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function contact()
    {
        return $this->hasOne(InvestorContact::class);
    }

    public function addresses()
    {
        return $this->hasMany(InvestorAddress::class);
    }

    public function nominees()
    {
        return $this->hasMany(InvestorNominee::class);
    }

    public function kycProfile()
    {
        return $this->hasOne(InvestorKycProfile::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function documents()
    {
        return $this->hasMany(InvestorDocument::class);
    }

    public function approvalRequests()
    {
        return $this->hasMany(ApprovalRequest::class, 'entity_id')
            ->where('entity_type', 'investor');
    }

    public function directors()
    {
        return $this->hasMany(CompanyDirector::class);
    }

    public function identityVerifications()
    {
        return $this->hasMany(IdentityVerification::class, 'entity_id')
            ->where('entity_type', 'investor');
    }
    public function investorCategory()
    {
        return $this->belongsTo(InvestorCategory::class);
    }
    public function kycReviews()
    {
        return $this->hasMany(KycReview::class);
    }

        public function kycCaseAssignments()
    {
        return $this->hasMany(KycCaseAssignment::class);
    }

    public function activeKycCaseAssignment()
    {
        return $this->hasOne(KycCaseAssignment::class)->where('status', 'active')->latestOfMany();
    }

    public function kycCaseNotes()
    {
        return $this->hasMany(KycCaseNote::class);
    }
    public function purchaseRequests()
    {
        return $this->hasMany(PurchaseRequest::class);
    }
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
    public function investmentTransactions()
    {
        return $this->hasMany(InvestmentTransaction::class);
    }

    public function unitLots()
    {
        return $this->hasMany(UnitLot::class);
    }
}