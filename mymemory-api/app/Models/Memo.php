<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Memo extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'group_id',
        'type',
        'status',
        'title',
        'content',
        'summary',
        'keywords',
        'source_url',
        'ai_level',
    ];

    protected function casts(): array
    {
        return [
            'keywords' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function file(): HasOne
    {
        return $this->hasOne(MemoFile::class);
    }

    public function isPersonal(): bool
    {
        return $this->group_id === null;
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }
}
