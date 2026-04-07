<?php

namespace App\Services\Me;

use App\DTOs\Me\UpdatePreferencesDTO;
use App\Models\User;
use App\Models\UserPreference;

class UserPreferenceService
{
    public function getOrCreate(User $user): UserPreference
    {
        return UserPreference::firstOrCreate(
            ['user_id' => $user->id],
            [
                'ai_level_text'             => 'full',
                'ai_level_url'              => 'full',
                'ai_level_image'            => 'full',
                'ai_level_audio'            => 'full',
                'ai_level_video'            => 'full',
                'ai_level_document'         => 'full',
                'confirm_before_processing' => true,
                'sound_enabled'             => true,
                'ocr_correction_threshold'  => null,
            ]
        );
    }

    public function update(User $user, UpdatePreferencesDTO $dto): UserPreference
    {
        $preference = $this->getOrCreate($user);

        $fields = $dto->toUpdateArray();

        if (!empty($fields)) {
            $preference->update($fields);
        }

        return $preference->fresh();
    }
}
