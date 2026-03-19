<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvestorDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'investor_id',
        'investor_kyc_profile_id',
        'investor_category_id',
        'parent_document_id',
        'version_number',
        'is_current_version',
        'document_type_id',
        'original_filename',
        'stored_filename',
        'storage_disk',
        'storage_path',
        'mime_type',
        'file_extension',
        'file_size_bytes',
        'document_number',
        'issue_date',
        'expiry_date',
        'verification_status',
        'verification_notes',
        'verified_by',
        'verified_at',
        'replaced_at',
        'uploaded_by',
        'uploaded_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'verified_at' => 'datetime',
            'replaced_at' => 'datetime',
            'uploaded_at' => 'datetime',
            'metadata' => 'array',
            'file_size_bytes' => 'integer',
            'is_current_version' => 'boolean',
            'version_number' => 'integer',
        ];
    }

    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }

    public function kycProfile()
    {
        return $this->belongsTo(InvestorKycProfile::class, 'investor_kyc_profile_id');
    }

    public function investorCategory()
    {
        return $this->belongsTo(InvestorCategory::class);
    }

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function parentDocument()
    {
        return $this->belongsTo(self::class, 'parent_document_id');
    }

    public function childVersions()
    {
        return $this->hasMany(self::class, 'parent_document_id');
    }
}