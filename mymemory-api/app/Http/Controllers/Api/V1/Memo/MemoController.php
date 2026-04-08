<?php

namespace App\Http\Controllers\Api\V1\Memo;

use App\DTOs\Memo\ProcessMediaDTO;
use App\DTOs\Memo\SearchMemosDTO;
use App\DTOs\Memo\UpdateMemoDTO;
use App\Exceptions\ResourceNotFoundException;
use App\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Memo\SearchMemosRequest;
use App\Http\Requests\Memo\UpdateMemoRequest;
use App\Http\Requests\Memo\UploadMemoRequest;
use App\Http\Resources\Memo\MemoResource;
use App\Models\Memo;
use App\Services\Memo\AudioMemoService;
use App\Services\Memo\DocumentMemoService;
use App\Services\Memo\FileStorageService;
use App\Services\Memo\ImageMemoService;
use App\Services\Memo\MemoQueryService;
use App\Services\Memo\MemoUpdateService;
use App\Services\Memo\VideoMemoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MemoController extends Controller
{
    public function __construct(
        private readonly MemoQueryService  $query,
        private readonly MemoUpdateService $updater,
        private readonly FileStorageService $storage,
        private readonly ImageMemoService    $imageSvc,
        private readonly AudioMemoService    $audioSvc,
        private readonly VideoMemoService    $videoSvc,
        private readonly DocumentMemoService $documentSvc,
    ) {}

    /** GET /api/v1/memos/recent */
    public function recent(Request $request): JsonResponse
    {
        $limit   = min((int) ($request->query('limit', 12)), 50);
        $groupId = $request->query('group_id') !== null ? (int) $request->query('group_id') : null;

        $memos = $this->query->recent($request->user(), $limit, $groupId);

        return ApiResponse::success(
            MemoResource::collection($memos),
            ['total' => count($memos)],
        );
    }

    /** POST /api/v1/memos/search */
    public function search(SearchMemosRequest $request): JsonResponse
    {
        $dto    = SearchMemosDTO::fromArray($request->validated());
        $result = $this->query->search($request->user(), $dto);

        return ApiResponse::success(
            MemoResource::collection($result['items']),
            [
                'total'           => $result['total'],
                'query'           => $dto->query,
                'operator'        => $dto->operator,
                'highlight_terms' => $result['highlight_terms'],
            ],
        );
    }

    /** POST /api/v1/memos/search/synonyms */
    public function synonyms(Request $request): JsonResponse
    {
        $query    = $request->input('query', '');
        $synonyms = $this->query->synonyms((string) $query);

        return ApiResponse::success(['synonyms' => $synonyms]);
    }

    /** GET /api/v1/memos/search/authors */
    public function authors(Request $request): JsonResponse
    {
        $groupId = $request->query('group_id') !== null ? (int) $request->query('group_id') : null;
        $authors = $this->query->authors($request->user(), $groupId);

        return ApiResponse::success($authors);
    }

    /** POST /api/v1/memos/upload (generic — auto-detect type) */
    public function upload(UploadMemoRequest $request): JsonResponse
    {
        $file     = $request->file('file');
        $mime     = $file->getMimeType() ?? '';
        $dto      = ProcessMediaDTO::fromArray($request->validated());
        $user     = $request->user();

        $memo = match (true) {
            str_starts_with($mime, 'image/')  => $this->imageSvc->process($user, $dto),
            str_starts_with($mime, 'audio/')  => $this->audioSvc->process($user, $dto),
            str_starts_with($mime, 'video/')  => $this->videoSvc->process($user, $dto),
            default                           => $this->documentSvc->process($user, $dto),
        };

        return ApiResponse::success(new MemoResource($memo));
    }

    /** GET /api/v1/memos/{id} */
    public function show(Request $request, int $id): JsonResponse
    {
        $memo = Memo::with(['user:id,name,email', 'file'])->find($id);

        if (!$memo) {
            throw new ResourceNotFoundException('Memo');
        }

        $this->authorize('view', $memo);

        return ApiResponse::success(new MemoResource($memo));
    }

    /** GET /api/v1/memos/{id}/file */
    public function file(Request $request, int $id): mixed
    {
        $memo = Memo::with('file')->find($id);

        if (!$memo || !$memo->file) {
            throw new ResourceNotFoundException('Memo file');
        }

        $this->authorize('view', $memo);

        $memoFile = $memo->file;
        $disk     = Storage::disk($memoFile->disk);

        if (!$disk->exists($memoFile->storage_key)) {
            throw new ResourceNotFoundException('Memo file');
        }

        return $disk->download($memoFile->storage_key, $memoFile->original_filename);
    }

    /** PATCH /api/v1/memos/{id} */
    public function update(UpdateMemoRequest $request, int $id): JsonResponse
    {
        $dto  = UpdateMemoDTO::fromArray($request->validated());
        $memo = $this->updater->update($request->user(), $id, $dto);

        return ApiResponse::success(new MemoResource($memo));
    }

    /** DELETE /api/v1/memos/{id} */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->updater->destroy($request->user(), $id);

        return ApiResponse::noContent();
    }
}
