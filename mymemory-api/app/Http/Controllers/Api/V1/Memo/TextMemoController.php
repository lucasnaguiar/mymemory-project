<?php

namespace App\Http\Controllers\Api\V1\Memo;

use App\DTOs\Memo\ConfirmTextDTO;
use App\DTOs\Memo\CreateTextDTO;
use App\DTOs\Memo\ProcessTextDTO;
use App\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Memo\ConfirmTextRequest;
use App\Http\Requests\Memo\CreateTextRequest;
use App\Http\Requests\Memo\ProcessTextRequest;
use App\Http\Resources\Memo\MemoResource;
use App\Services\Memo\TextMemoService;
use Illuminate\Http\JsonResponse;

class TextMemoController extends Controller
{
    public function __construct(private readonly TextMemoService $service) {}

    /**
     * POST /api/v1/memos/text/process
     */
    public function process(ProcessTextRequest $request): JsonResponse
    {
        $dto  = ProcessTextDTO::fromArray($request->validated());
        $memo = $this->service->process($request->user(), $dto);

        return ApiResponse::success(new MemoResource($memo));
    }

    /**
     * POST /api/v1/memos/text/confirm
     */
    public function confirm(ConfirmTextRequest $request): JsonResponse
    {
        $dto  = ConfirmTextDTO::fromArray($request->validated());
        $memo = $this->service->confirm($request->user(), $dto);

        return ApiResponse::created(new MemoResource($memo));
    }

    /**
     * POST /api/v1/memos/text
     */
    public function create(CreateTextRequest $request): JsonResponse
    {
        $dto  = CreateTextDTO::fromArray($request->validated());
        $memo = $this->service->create($request->user(), $dto);

        return ApiResponse::created(new MemoResource($memo));
    }
}
