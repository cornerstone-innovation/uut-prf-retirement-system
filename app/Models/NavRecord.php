<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NavRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'plan_id',
        'valuation_date',
        'nav_per_unit',
        'status',
        'source',
        'notes',
        'metadata',
        'created_by',
        'approved_by_1',
        'approved_at_1',
        'approved_by_2',
        'approved_at_2',
        'published_by',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'valuation_date' => 'date',
            'nav_per_unit' => 'decimal:6',
            'metadata' => 'array',
            'approved_at_1' => 'datetime',
            'approved_at_2' => 'datetime',
            'published_at' => 'datetime',
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

    public function approverOne()
    {
        return $this->belongsTo(User::class, 'approved_by_1');
    }

    public function approverTwo()
    {
        return $this->belongsTo(User::class, 'approved_by_2');
    }

    public function publisher()
    {
        return $this->belongsTo(User::class, 'published_by');
    }

}