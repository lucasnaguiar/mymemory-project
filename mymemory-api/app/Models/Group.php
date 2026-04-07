<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Group extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'owner_user_id',
        'subscription_plan_id',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(GroupMember::class);
    }

    public function invites(): HasMany
    {
        return $this->hasMany(GroupInvite::class);
    }

    public function memos(): HasMany
    {
        return $this->hasMany(Memo::class);
    }
}
