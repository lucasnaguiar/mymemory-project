<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exceptions\ResourceNotFoundException;
use App\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateMediaSettingRequest;
use App\Http\Resources\Admin\MediaSettingResource;
use App\Models\PlanMediaSetting;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;

class AdminMediaSettingsController extends Controller
{
    /** GET /api/v1/admin/subscription-plans/:id/media-settings */
    public function index(int $planId): JsonResponse
    {
        $plan = SubscriptionPlan::find($planId);
        if (!$plan) throw new ResourceNotFoundException('Plano');

        $settings = PlanMediaSetting::where('subscription_plan_id', $planId)->get();
        return ApiResponse::success(MediaSettingResource::collection($settings));
    }

    /** PUT /api/v1/admin/subscription-plans/:id/media-settings (upsert) */
    public function upsert(UpdateMediaSettingRequest $request, int $planId): JsonResponse
    {
        $plan = SubscriptionPlan::find($planId);
        if (!$plan) throw new ResourceNotFoundException('Plano');

        $data    = $request->validated();
        $setting = PlanMediaSetting::updateOrCreate(
            [
                'subscription_plan_id' => $planId,
                'media_type'           => $data['media_type'],
            ],
            [
                'max_file_size_mb'                 => $data['max_file_size_mb'],
                'max_chunk_minutes'                => $data['max_chunk_minutes'] ?? null,
                'ocr_correction_threshold_default' => $data['ocr_correction_threshold_default'] ?? null,
            ]
        );

        return ApiResponse::success(new MediaSettingResource($setting));
    }
}
