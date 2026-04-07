<?php

namespace App\Services\Me;

use App\Models\MemoFile;
use App\Models\MonthlyUsage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UsageService
{
    /**
     * Returns current usage vs plan limits for the authenticated user.
     * When in a group workspace, limits are sourced from the group's plan.
     */
    public function getUsage(User $user): array
    {
        $user->loadMissing('subscriptionPlan', 'activeWorkspaceGroup.subscriptionPlan');

        $plan = $user->active_workspace_group_id !== null
            ? $user->activeWorkspaceGroup?->subscriptionPlan
            : $user->subscriptionPlan;

        [$year, $month] = [now()->year, now()->month];

        $monthly = MonthlyUsage::query()
            ->where('user_id', $user->id)
            ->where('group_id', $user->active_workspace_group_id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        $memoCount = $user->memos()
            ->when($user->active_workspace_group_id, fn ($q) => $q->where('group_id', $user->active_workspace_group_id))
            ->where('status', 'confirmed')
            ->count();

        $storageBytes = (int) MemoFile::query()
            ->join('memos', 'memos.id', '=', 'memo_files.memo_id')
            ->where('memos.user_id', $user->id)
            ->when($user->active_workspace_group_id, fn ($q) => $q->where('memos.group_id', $user->active_workspace_group_id))
            ->whereNull('memos.deleted_at')
            ->sum('memo_files.size_bytes');

        return [
            'plan_name'     => $plan?->name ?? 'No plan',
            'memos'         => [
                'used'  => $memoCount,
                'limit' => $plan?->max_memos,
            ],
            'storage_bytes' => [
                'used'     => $storageBytes,
                'limit_gb' => (float) ($plan?->storage_gb ?? 0),
            ],
            'api_credits'   => [
                'used'  => $monthly?->api_credits_used ?? 0,
                'limit' => $plan?->api_credits_per_month,
            ],
            'downloads'     => [
                'used'  => $monthly?->downloads_count ?? 0,
                'limit' => $plan?->downloads_per_month,
            ],
        ];
    }

    /**
     * Returns per-media-type file upload limits from the active plan.
     */
    public function getMediaLimits(User $user): array
    {
        $user->loadMissing('subscriptionPlan.mediaSettings', 'activeWorkspaceGroup.subscriptionPlan.mediaSettings');

        $plan = $user->active_workspace_group_id !== null
            ? $user->activeWorkspaceGroup?->subscriptionPlan
            : $user->subscriptionPlan;

        $settings = $plan?->mediaSettings ?? collect();
        $defaults  = ['image' => 10, 'audio' => 50, 'video' => 200, 'document' => 20];

        $limits = [];
        foreach (['image', 'audio', 'video', 'document'] as $type) {
            $setting = $settings->firstWhere('media_type', $type);
            $limits[$type] = [
                'max_file_size_mb'                   => $setting?->max_file_size_mb ?? $defaults[$type],
                'max_chunk_minutes'                  => $setting?->max_chunk_minutes,
                'ocr_correction_threshold_default'   => $setting?->ocr_correction_threshold_default,
            ];
        }

        return $limits;
    }
}
