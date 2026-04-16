<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NavOverrideLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'nav_record_id',
        'plan_id',
        'calculated_nav_per_unit',
        'override_nav_per_unit',
        'override_reason',
        'override_by',
        'override_at',
        'calculation_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'calculated_nav_per_unit' => 'decimal:6',
            'override_nav_per_unit' => 'decimal:6',
            'override_at' => 'datetime',
            'calculation_snapshot' => 'array',
        ];
    }

    public function navRecord()
    {
        return $this->belongsTo(NavRecord::class);
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