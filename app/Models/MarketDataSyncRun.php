<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MarketDataSyncRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'sync_type',
        'run_date',
        'timezone',
        'executed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'run_date' => 'date',
            'executed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}