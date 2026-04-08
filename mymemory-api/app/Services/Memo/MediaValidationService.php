<?php

namespace App\Services\Memo;

use App\Exceptions\BusinessRuleException;
use App\Models\PlanMediaSetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class MediaValidationService
{
    /**
     * Assert the uploaded file is allowed given the user's plan.
     *
     * @param  'image'|'audio'|'video'|'document'  $type
     *
     * @throws BusinessRuleException
     */
    public function assertFileAllowed(UploadedFile $file, string $type, User $user): void
    {
        $user->loadMissing('subscriptionPlan');
        $plan = $user->subscriptionPlan;

        // Audio/video require plan support
        if (in_array($type, ['audio', 'video'], true) && $plan && !$plan->supports_audio_video) {
            throw new BusinessRuleException(
                'Your plan does not support audio or video memos.',
                'audio_video_not_supported',
            );
        }

        // Check plan-specific file size limit
        $settings = $plan
            ? PlanMediaSetting::where('subscription_plan_id', $plan->id)
                ->where('media_type', $type)
                ->first()
            : null;

        if ($settings) {
            $maxSizeBytes = $settings->max_file_size_mb * 1024 * 1024;
            if ($file->getSize() > $maxSizeBytes) {
                throw new BusinessRuleException(
                    "File size exceeds the {$settings->max_file_size_mb}MB limit for your plan.",
                    'file_too_large',
                );
            }
        }
    }
}
