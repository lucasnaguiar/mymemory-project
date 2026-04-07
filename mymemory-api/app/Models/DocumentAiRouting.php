<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAiRouting extends Model
{
    protected $fillable = [
        'config',
        'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
