<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvestorNominee extends Model
{
    use HasFactory;

    protected $fillable = [
        'investor_id',
        'full_name',
        'relationship',
        'date_of_birth',
        'phone',
        'email',
        'national_id_number',
        'allocation_percentage',
        'is_minor',
        'guardian_name',
        'guardian_phone',
        'address',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'allocation_percentage' => 'decimal:2',
            'is_minor' => 'boolean',
        ];
    }

    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }
}