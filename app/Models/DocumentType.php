<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DocumentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'code',
        'description',
        'allowed_extensions',
        'max_file_size_kb',
        'requires_expiry_date',
        'requires_issue_date',
        'requires_document_number',
        'requires_manual_verification',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'requires_expiry_date' => 'boolean',
            'requires_issue_date' => 'boolean',
            'requires_document_number' => 'boolean',
            'requires_manual_verification' => 'boolean',
            'is_active' => 'boolean',
            'max_file_size_kb' => 'integer',
        ];
    }

    public function categoryRequirements()
    {
        return $this->hasMany(CategoryDocumentRequirement::class);
    }

    public function investorDocuments()
    {
        return $this->hasMany(InvestorDocument::class);
    }
}