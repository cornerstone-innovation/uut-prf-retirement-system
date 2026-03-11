<?php

namespace App\Application\Services\Document;

use App\Models\InvestorCategory;

class DocumentRequirementService
{
    public function getRequirementsForCategory(InvestorCategory $category): InvestorCategory
    {
        return $category->load([
            'documentRequirements' => function ($query) {
                $query->where('is_active', true)
                    ->orderBy('sort_order');
            },
            'documentRequirements.documentType' => function ($query) {
                $query->where('is_active', true)
                    ->orderBy('sort_order');
            },
        ]);
    }
}