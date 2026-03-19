<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlanStatusHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'plan_id',
        'from_status',
        'to_status',
        'notes',
        'changed_by',
        'changed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function changer()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}