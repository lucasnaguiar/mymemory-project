<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Group\GroupPlanResource;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;

class GroupPlanController extends Controller
{
    /** GET /api/v1/group-plans (public) */
    public function index(): JsonResponse
    {
        $plans = SubscriptionPlan::where('type', 'group')
            ->where('is_active', true)
            ->orderBy('price_cents')
            ->get();

        return ApiResponse::success(GroupPlanResource::collection($plans));
    }
}
