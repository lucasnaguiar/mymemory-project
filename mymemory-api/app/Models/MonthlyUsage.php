<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyUsage extends Model
{
    protected $fillable = [
        'user_id',
        'group_id',
        'year',
        'month',
        'api_credits_used',
        'downloads_count',
        'memos_created_count',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
