<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupInvite extends Model
{
    protected $fillable = [
        'group_id',
        'email',
        'role',
        'token',
        'invited_by_user_id',
        'expires_at',
        'accepted_at',
    ];

    protected $hidden = ['token'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
