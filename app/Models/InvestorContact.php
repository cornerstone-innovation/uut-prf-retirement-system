<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvestorContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'investor_id',
        'email',
        'phone_primary',
        'phone_secondary',
        'alternate_contact_name',
        'alternate_contact_phone',
        'preferred_contact_method',
    ];

    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }
}