<?php

namespace App\Http\Controllers\Api\V1\Memo;

use App\DTOs\Memo\ConfirmMediaDTO;
use App\DTOs\Memo\ProcessMediaDTO;
use App\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Memo\ConfirmMediaRequest;
use App\Http\Requests\Memo\ProcessDocumentRequest;
use App\Http\Resources\Memo\MemoResource;
use App\Services\Memo\DocumentMemoService;
use Illuminate\Http\JsonResponse;

class DocumentMemoController extends Controller
{
    public function __construct(private readonly DocumentMemoService $service) {}

    /** POST /api/v1/memos/document/process */
    public function process(ProcessDocumentRequest $request): JsonResponse
    {
        $dto  = ProcessMediaDTO::fromArray($request->validated());
        $memo = $this->service->process($request->user(), $dto);
        return ApiResponse::success(new MemoResource($memo));
    }

    /** POST /api/v1/memos/document/confirm */
    public function confirm(ConfirmMediaRequest $request): JsonResponse
    {
        $dto  = ConfirmMediaDTO::fromArray($request->validated());
        $memo = $this->service->confirm($request->user(), $dto);
        return ApiResponse::created(new MemoResource($memo));
    }
}
