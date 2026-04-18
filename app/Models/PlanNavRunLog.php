<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlanNavRunLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'plan_id',
        'valuation_date',
        'executed_at',
        'status',
        'message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'valuation_date' => 'date',
            'executed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}