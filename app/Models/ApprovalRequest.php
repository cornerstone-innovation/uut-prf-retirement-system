<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApprovalRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'approval_type',
        'entity_type',
        'entity_id',
        'entity_reference',
        'status',
        'submitted_by',
        'current_approver_id',
        'submitted_at',
        'decided_at',
        'decision_reason',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function actions()
    {
        return $this->hasMany(ApprovalAction::class);
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function currentApprover()
    {
        return $this->belongsTo(User::class, 'current_approver_id');
    }
    public function investor()
    {
        return $this->belongsTo(Investor::class, 'entity_id')
            ->where('entity_type', 'investor');
    }
}