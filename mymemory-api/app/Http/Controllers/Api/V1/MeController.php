<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Me\UpdatePreferencesDTO;
use App\DTOs\Me\UpdateWorkspaceDTO;
use App\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Me\UpdatePreferencesRequest;
use App\Http\Requests\Me\UpdateWorkspaceRequest;
use App\Http\Resources\Me\MediaLimitsResource;
use App\Http\Resources\Me\UserPreferenceResource;
use App\Http\Resources\Me\UserProfileResource;
use App\Http\Resources\Me\UsageResource;
use App\Http\Resources\Me\WorkspaceGroupResource;
use App\Services\Me\UsageService;
use App\Services\Me\UserPreferenceService;
use App\Services\Me\UserProfileService;
use App\Services\Me\WorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __construct(
        private readonly UserProfileService $profileService,
        private readonly UsageService $usageService,
        private readonly UserPreferenceService $preferenceService,
        private readonly WorkspaceService $workspaceService,
    ) {}

    /**
     * GET /api/v1/me
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $this->profileService->getProfile($request->user());

        return ApiResponse::success(
            new UserProfileResource($user)
        );
    }

    /**
     * GET /api/v1/me/usage
     */
    public function usage(Request $request): JsonResponse
    {
        $data = $this->usageService->getUsage($request->user());

        return ApiResponse::success(
            new UsageResource($data)
        );
    }

    /**
     * GET /api/v1/me/media-limits
     */
    public function mediaLimits(Request $request): JsonResponse
    {
        $limits = $this->usageService->getMediaLimits($request->user());

        return ApiResponse::success(
            new MediaLimitsResource($limits)
        );
    }

    /**
     * PATCH /api/v1/me/preferences
     */
    public function updatePreferences(UpdatePreferencesRequest $request): JsonResponse
    {
        $dto        = UpdatePreferencesDTO::fromArray($request->validated());
        $preference = $this->preferenceService->update($request->user(), $dto);

        $this->authorize('update', $preference);

        return ApiResponse::success(
            new UserPreferenceResource($preference)
        );
    }

    /**
     * GET /api/v1/me/workspace-groups
     */
    public function workspaceGroups(Request $request): JsonResponse
    {
        $groups = $this->workspaceService->getWorkspaceGroups($request->user());

        return ApiResponse::success(
            WorkspaceGroupResource::collection($groups)
        );
    }

    /**
     * PATCH /api/v1/me/workspace
     */
    public function updateWorkspace(UpdateWorkspaceRequest $request): JsonResponse
    {
        $dto  = UpdateWorkspaceDTO::fromArray($request->validated());
        $user = $this->workspaceService->updateWorkspace($request->user(), $dto);

        return ApiResponse::success([
            'active_workspace_group_id' => $user->active_workspace_group_id,
        ]);
    }
}
