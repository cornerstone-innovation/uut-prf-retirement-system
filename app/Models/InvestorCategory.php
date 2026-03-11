<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvestorCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'code',
        'description',
        'is_active',
        'requires_guardian',
        'requires_custodian',
        'requires_ubo',
        'requires_authorized_signatories',
        'allows_joint_holding',
        'is_minor_category',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'requires_guardian' => 'boolean',
            'requires_custodian' => 'boolean',
            'requires_ubo' => 'boolean',
            'requires_authorized_signatories' => 'boolean',
            'allows_joint_holding' => 'boolean',
            'is_minor_category' => 'boolean',
        ];
    }

    public function documentRequirements()
    {
        return $this->hasMany(CategoryDocumentRequirement::class);
    }

    public function investorDocuments()
    {
        return $this->hasMany(InvestorDocument::class);
    }
}