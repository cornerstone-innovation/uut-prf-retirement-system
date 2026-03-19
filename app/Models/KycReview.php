<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KycReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'investor_id',
        'review_status',
        'decision',
        'review_notes',
        'escalation_reason',
        'override_reason',
        'reviewed_by',
        'reviewed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}