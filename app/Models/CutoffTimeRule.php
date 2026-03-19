<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CutoffTimeRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'plan_id',
        'cutoff_time',
        'timezone',
        'effective_from',
        'effective_to',
        'status',
        'is_active',
        'notes',
        'metadata',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
            'metadata' => 'array',
            'approved_at' => 'datetime',
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

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}