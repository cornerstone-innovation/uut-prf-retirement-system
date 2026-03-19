<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Fund extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'code',
        'name',
        'description',
        'fund_type',
        'pricing_method',
        'status',
        'currency',
        'is_open_ended',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_open_ended' => 'boolean',
        ];
    }

    public function plans()
    {
        return $this->hasMany(Plan::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}