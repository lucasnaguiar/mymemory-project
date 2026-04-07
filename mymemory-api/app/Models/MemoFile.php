<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemoFile extends Model
{
    protected $fillable = [
        'memo_id',
        'disk',
        'storage_key',
        'original_filename',
        'mime_type',
        'size_bytes',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
        ];
    }

    public function memo(): BelongsTo
    {
        return $this->belongsTo(Memo::class);
    }
}
