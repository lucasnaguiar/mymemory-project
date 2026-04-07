<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanMediaSetting extends Model
{
    protected $fillable = [
        'subscription_plan_id',
        'media_type',
        'max_file_size_mb',
        'max_chunk_minutes',
        'ocr_correction_threshold_default',
    ];

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }
}
