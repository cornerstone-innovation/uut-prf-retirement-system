<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvestorAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'investor_id',
        'address_type',
        'country',
        'region',
        'city',
        'district',
        'ward',
        'street',
        'postal_address',
        'postal_code',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }
}