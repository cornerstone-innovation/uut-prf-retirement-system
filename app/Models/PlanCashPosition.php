<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlanCashPosition extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'plan_id',
        'position_date',
        'cash_amount',
        'source_type',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'position_date' => 'date',
            'cash_amount' => 'decimal:2',
        ];
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}