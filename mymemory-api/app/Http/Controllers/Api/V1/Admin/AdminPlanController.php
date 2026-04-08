<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\ResourceNotFoundException;
use App\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreatePlanRequest;
use App\Http\Resources\Admin\PlanResource;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPlanController extends Controller
{
    /** GET /api/v1/admin/subscription-plans */
    public function index(): JsonResponse
    {
        $plans = SubscriptionPlan::withCount(['users', 'groups'])->orderBy('type')->orderBy('price_cents')->get();

        return ApiResponse::success(PlanResource::collection($plans));
    }

    /** POST /api/v1/admin/subscription-plans */
    public function store(CreatePlanRequest $request): JsonResponse
    {
        $plan = SubscriptionPlan::create($request->validated());
        return ApiResponse::created(new PlanResource($plan));
    }

    /** PATCH /api/v1/admin/subscription-plans/:id */
    public function update(CreatePlanRequest $request, int $id): JsonResponse
    {
        $plan = SubscriptionPlan::find($id);
        if (!$plan) throw new ResourceNotFoundException('Plano');

        $plan->update($request->validated());
        return ApiResponse::success(new PlanResource($plan));
    }

    /** DELETE /api/v1/admin/subscription-plans/:id */
    public function destroy(int $id): JsonResponse
    {
        $plan = SubscriptionPlan::find($id);
        if (!$plan) throw new ResourceNotFoundException('Plano');

        $userCount  = $plan->users()->count();
        $groupCount = $plan->groups()->count();

        if ($userCount > 0 || $groupCount > 0) {
            throw new BusinessRuleException(
                "Não é possível excluir: {$userCount} usuário(s) e {$groupCount} grupo(s) vinculados a este plano."
            );
        }

        $plan->delete();
        return ApiResponse::noContent();
    }
}
