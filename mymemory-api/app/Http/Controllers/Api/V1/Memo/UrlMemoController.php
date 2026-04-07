<?php

namespace App\Http\Controllers\Api\V1\Memo;

use App\DTOs\Memo\ConfirmUrlDTO;
use App\DTOs\Memo\CreateUrlDTO;
use App\DTOs\Memo\ProcessUrlDTO;
use App\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Memo\ConfirmUrlRequest;
use App\Http\Requests\Memo\CreateUrlRequest;
use App\Http\Requests\Memo\ProcessUrlRequest;
use App\Http\Resources\Memo\MemoResource;
use App\Services\Memo\UrlMemoService;
use Illuminate\Http\JsonResponse;

class UrlMemoController extends Controller
{
    public function __construct(private readonly UrlMemoService $service) {}

    /**
     * POST /api/v1/memos/url/process
     */
    public function process(ProcessUrlRequest $request): JsonResponse
    {
        $dto  = ProcessUrlDTO::fromArray($request->validated());
        $memo = $this->service->process($request->user(), $dto);

        return ApiResponse::success(new MemoResource($memo));
    }

    /**
     * POST /api/v1/memos/url/confirm
     */
    public function confirm(ConfirmUrlRequest $request): JsonResponse
    {
        $dto  = ConfirmUrlDTO::fromArray($request->validated());
        $memo = $this->service->confirm($request->user(), $dto);

        return ApiResponse::created(new MemoResource($memo));
    }

    /**
     * POST /api/v1/memos/url
     */
    public function create(CreateUrlRequest $request): JsonResponse
    {
        $dto  = CreateUrlDTO::fromArray($request->validated());
        $memo = $this->service->create($request->user(), $dto);

        return ApiResponse::created(new MemoResource($memo));
    }
}
