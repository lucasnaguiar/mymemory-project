<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Scope memos accessible by the given user.
     * If $groupId is set: all confirmed memos for that group where user is owner or member.
     * Otherwise: only the user's own personal memos.
     */
    public function scopeAccessibleBy(Builder $query, \App\Models\User $user, ?int $groupId = null): Builder
    {
        if ($groupId !== null) {
            return $query->where('group_id', $groupId)
                ->where(function (Builder $q) use ($user, $groupId) {
                    $q->where('user_id', $user->id)
                        ->orWhereExists(fn ($sub) => $sub->selectRaw('1')
                            ->from('group_members')
                            ->where('group_id', $groupId)
                            ->where('user_id', $user->id))
                        ->orWhereExists(fn ($sub) => $sub->selectRaw('1')
                            ->from('groups')
                            ->where('id', $groupId)
                            ->where('owner_user_id', $user->id));
                });
        }

        return $query->whereNull('group_id')->where('user_id', $user->id);
    }
}
