<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'ai_level_text',
        'ai_level_url',
        'ai_level_image',
        'ai_level_audio',
        'ai_level_video',
        'ai_level_document',
        'confirm_before_processing',
        'sound_enabled',
        'ocr_correction_threshold',
    ];

    protected function casts(): array
    {
        return [
            'confirm_before_processing' => 'boolean',
            'sound_enabled' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
