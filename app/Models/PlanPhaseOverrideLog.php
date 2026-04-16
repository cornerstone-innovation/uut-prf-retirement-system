<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlanPhaseOverrideLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'plan_id',
        'previous_phase',
        'new_phase',
        'previous_offer_end_date',
        'new_offer_end_date',
        'effective_at',
        'override_reason',
        'override_by',
    ];

    protected function casts(): array
    {
        return [
            'previous_offer_end_date' => 'date',
            'new_offer_end_date' => 'date',
            'effective_at' => 'datetime',
        ];
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function overrideUser()
    {
        return $this->belongsTo(User::class, 'override_by');
    }
}