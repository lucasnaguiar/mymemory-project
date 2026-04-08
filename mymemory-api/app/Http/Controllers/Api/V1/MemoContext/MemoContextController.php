<?php

namespace App\Http\Controllers\Api\V1\MemoContext;

use App\DTOs\MemoContext\CreateCategoryDTO;
use App\DTOs\MemoContext\CreateFieldDTO;
use App\DTOs\MemoContext\CreateSubcategoryDTO;
use App\DTOs\MemoContext\UpdateCategoryDTO;
use App\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\MemoContext\CreateCategoryRequest;
use App\Http\Requests\MemoContext\CreateFieldRequest;
use App\Http\Requests\MemoContext\CreateSubcategoryRequest;
use App\Http\Requests\MemoContext\UpdateCategoryRequest;
use App\Http\Resources\MemoContext\CategoryResource;
use App\Http\Resources\MemoContext\FieldResource;
use App\Http\Resources\MemoContext\SubcategoryResource;
use App\Services\MemoContext\MemoContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemoContextController extends Controller
{
    public function __construct(private readonly MemoContextService $svc) {}

    // -------------------------------------------------------------------------
    // Read — public-ish (auth required)
    // -------------------------------------------------------------------------

    /** GET /api/v1/memo-context/groups */
    public function groups(Request $request): JsonResponse
    {
        $groups = $this->svc->getAccessibleGroups($request->user());
        $data   = array_map(fn ($g) => ['id' => $g->id, 'name' => $g->name], $groups);

        return ApiResponse::success($data);
    }

    /** GET /api/v1/memo-context/editor-meta */
    public function editorMeta(Request $request): JsonResponse
    {
        $meta = $this->svc->getEditorMeta($request->user());
        return ApiResponse::success($meta);
    }

    /** GET /api/v1/memo-context/structure */
    public function structure(Request $request): JsonResponse
    {
        $groupId   = $request->query('groupId') !== null ? (int) $request->query('groupId') : null;
        $mediaType = $request->query('mediaType');

        $cats = $this->svc->getStructure($groupId, $mediaType ?: null);

        return ApiResponse::success(CategoryResource::collection($cats));
    }

    /** GET /api/v1/memo-context/groups/:groupId/structure */
    public function groupStructure(Request $request, int $groupId): JsonResponse
    {
        $mediaType = $request->query('mediaType');
        $cats      = $this->svc->getStructure($groupId, $mediaType ?: null);

        return ApiResponse::success(CategoryResource::collection($cats));
    }

    // -------------------------------------------------------------------------
    // Category CRUD
    // -------------------------------------------------------------------------

    /** POST /api/v1/memo-context/categories */
    public function createCategory(CreateCategoryRequest $request): JsonResponse
    {
        $dto = CreateCategoryDTO::fromArray($request->validated());
        $cat = $this->svc->createCategory($request->user(), $dto);

        return ApiResponse::created(new CategoryResource($cat));
    }

    /** PATCH /api/v1/memo-context/categories/:id */
    public function updateCategory(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        $dto = UpdateCategoryDTO::fromArray($request->validated());
        $cat = $this->svc->updateCategory($request->user(), $id, $dto);

        return ApiResponse::success(new CategoryResource($cat));
    }

    /** DELETE /api/v1/memo-context/categories/:id */
    public function deleteCategory(Request $request, int $id): JsonResponse
    {
        $this->svc->deleteCategory($request->user(), $id);
        return ApiResponse::noContent();
    }

    // -------------------------------------------------------------------------
    // Subcategory CRUD
    // -------------------------------------------------------------------------

    /** POST /api/v1/memo-context/categories/:id/subcategories */
    public function createSubcategory(CreateSubcategoryRequest $request, int $categoryId): JsonResponse
    {
        $dto = CreateSubcategoryDTO::fromArray($request->validated());
        $sub = $this->svc->createSubcategory($request->user(), $categoryId, $dto);

        return ApiResponse::created(new SubcategoryResource($sub));
    }

    /** PATCH /api/v1/memo-context/subcategories/:id */
    public function updateSubcategory(Request $request, int $id): JsonResponse
    {
        $name = $request->input('name', '');
        $sub  = $this->svc->updateSubcategory($request->user(), $id, trim($name));

        return ApiResponse::success(new SubcategoryResource($sub));
    }

    /** DELETE /api/v1/memo-context/subcategories/:id */
    public function deleteSubcategory(Request $request, int $id): JsonResponse
    {
        $this->svc->deleteSubcategory($request->user(), $id);
        return ApiResponse::noContent();
    }

    // -------------------------------------------------------------------------
    // Field CRUD
    // -------------------------------------------------------------------------

    /** POST /api/v1/memo-context/categories/:id/fields */
    public function createField(CreateFieldRequest $request, int $categoryId): JsonResponse
    {
        $dto   = CreateFieldDTO::fromArray($request->validated());
        $field = $this->svc->createField($request->user(), $categoryId, $dto);

        return ApiResponse::created(new FieldResource($field));
    }

    /** PATCH /api/v1/memo-context/fields/:id */
    public function updateField(Request $request, int $id): JsonResponse
    {
        $field = $this->svc->updateField($request->user(), $id, $request->only(['name', 'field_type', 'is_required']));

        return ApiResponse::success(new FieldResource($field));
    }

    /** DELETE /api/v1/memo-context/fields/:id */
    public function deleteField(Request $request, int $id): JsonResponse
    {
        $this->svc->deleteField($request->user(), $id);
        return ApiResponse::noContent();
    }
}
