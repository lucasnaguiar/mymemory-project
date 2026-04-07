<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'type',
        'is_active',
        'max_memos',
        'storage_gb',
        'api_credits_per_month',
        'downloads_per_month',
        'supports_audio_video',
        'supports_chunking',
        'price_cents',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'supports_audio_video' => 'boolean',
            'supports_chunking' => 'boolean',
            'storage_gb' => 'decimal:2',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    public function mediaSettings(): HasMany
    {
        return $this->hasMany(PlanMediaSetting::class);
    }
}
