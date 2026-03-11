<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CategoryDocumentRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'investor_category_id',
        'document_type_id',
        'is_required',
        'is_multiple_allowed',
        'minimum_required_count',
        'requires_verification',
        'is_visible_on_onboarding',
        'applies_to_resident',
        'applies_to_non_resident',
        'applies_to_minor',
        'applies_to_joint',
        'applies_to_corporate',
        'notes',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_multiple_allowed' => 'boolean',
            'requires_verification' => 'boolean',
            'is_visible_on_onboarding' => 'boolean',
            'applies_to_resident' => 'boolean',
            'applies_to_non_resident' => 'boolean',
            'applies_to_minor' => 'boolean',
            'applies_to_joint' => 'boolean',
            'applies_to_corporate' => 'boolean',
            'is_active' => 'boolean',
            'minimum_required_count' => 'integer',
        ];
    }

    public function investorCategory()
    {
        return $this->belongsTo(InvestorCategory::class);
    }

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }
}