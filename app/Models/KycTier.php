<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KycTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'code',
        'name',
        'description',
        'rank',
        'can_view_products',
        'can_purchase',
        'can_redeem',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rank' => 'integer',
            'can_view_products' => 'boolean',
            'can_purchase' => 'boolean',
            'can_redeem' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function rules()
    {
        return $this->hasMany(KycTierRule::class);
    }
}