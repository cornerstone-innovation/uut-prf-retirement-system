<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KycCaseNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'investor_id',
        'author_id',
        'note',
        'note_type',
        'is_pinned',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}