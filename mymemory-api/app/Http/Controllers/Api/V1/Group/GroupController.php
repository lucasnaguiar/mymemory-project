<?php

namespace App\Http\Controllers\Api\V1\Group;

use App\DTOs\Group\AcceptInviteDTO;
use App\DTOs\Group\CreateGroupDTO;
use App\DTOs\Group\CreateInviteDTO;
use App\Exceptions\ResourceNotFoundException;
use App\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Group\AcceptInviteRequest;
use App\Http\Requests\Group\CreateGroupRequest;
use App\Http\Requests\Group\CreateInviteRequest;
use App\Http\Resources\Group\GroupInviteResource;
use App\Http\Resources\Group\GroupOwnerPanelResource;
use App\Http\Resources\Group\GroupResource;
use App\Models\Group;
use App\Services\Group\GroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function __construct(private readonly GroupService $groupService) {}

    /** POST /api/v1/groups */
    public function store(CreateGroupRequest $request): JsonResponse
    {
        $dto   = CreateGroupDTO::fromArray($request->validated());
        $group = $this->groupService->createGroup($request->user(), $dto);

        return ApiResponse::created(new GroupResource($group));
    }

    /** GET /api/v1/groups/{id}/owner-panel */
    public function ownerPanel(Request $request, int $id): JsonResponse
    {
        $group = Group::find($id);

        if (!$group) {
            throw new ResourceNotFoundException('Grupo');
        }

        $this->authorize('ownerPanel', $group);

        $data = $this->groupService->ownerPanel($group);

        return ApiResponse::success(new GroupOwnerPanelResource($data));
    }

    /** POST /api/v1/groups/{id}/invites */
    public function invite(CreateInviteRequest $request, int $id): JsonResponse
    {
        $group = Group::find($id);

        if (!$group) {
            throw new ResourceNotFoundException('Grupo');
        }

        $this->authorize('invite', $group);

        $dto    = CreateInviteDTO::fromArray($request->validated());
        $invite = $this->groupService->createInvite($request->user(), $group, $dto);

        return ApiResponse::created(new GroupInviteResource($invite));
    }

    /** POST /api/v1/group-invites/accept */
    public function acceptInvite(AcceptInviteRequest $request): JsonResponse
    {
        $dto   = AcceptInviteDTO::fromArray($request->validated());
        $group = $this->groupService->acceptInvite($request->user(), $dto);

        return ApiResponse::success(new GroupResource($group), ['message' => 'Convite aceito com sucesso.']);
    }
}
