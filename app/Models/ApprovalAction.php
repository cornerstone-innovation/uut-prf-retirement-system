<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApprovalAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_request_id',
        'action',
        'acted_by',
        'comments',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function approvalRequest()
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'acted_by');
    }
}