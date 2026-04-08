<?php

namespace App\Services\MemoContext;

use App\DTOs\MemoContext\CreateCategoryDTO;
use App\DTOs\MemoContext\CreateFieldDTO;
use App\DTOs\MemoContext\CreateSubcategoryDTO;
use App\DTOs\MemoContext\UpdateCategoryDTO;
use App\Exceptions\AuthorizationException;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\ResourceNotFoundException;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\MemoContextCategory;
use App\Models\MemoContextField;
use App\Models\MemoContextSubcategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class MemoContextService
{
    // -------------------------------------------------------------------------
    // Authorization helpers
    // -------------------------------------------------------------------------

    private function canEditCategory(User $user, MemoContextCategory $cat): bool
    {
        if ($cat->scope === 'global') {
            return $user->isAdmin();
        }

        if ($cat->group_id === null) {
            return false;
        }

        return $this->canManageGroupContext($user, $cat->group_id);
    }

    private function canManageGroupContext(User $user, int $groupId): bool
    {
        $group = Group::find($groupId);
        if (!$group) return false;

        if ($group->owner_user_id === $user->id) return true;

        return GroupMember::where('group_id', $groupId)
            ->where('user_id', $user->id)
            ->where('role', 'editor')
            ->exists();
    }

    // -------------------------------------------------------------------------
    // Queries
    // -------------------------------------------------------------------------

    /**
     * Groups where the user can manage context (owner or editor).
     *
     * @return Group[]
     */
    public function getAccessibleGroups(User $user): array
    {
        $ownedIds = Group::where('owner_user_id', $user->id)->pluck('id');

        $editorGroupIds = GroupMember::where('user_id', $user->id)
            ->where('role', 'editor')
            ->pluck('group_id');

        return Group::whereIn('id', $ownedIds->merge($editorGroupIds)->unique())
            ->orderBy('name')
            ->get()
            ->all();
    }

    /**
     * Editor metadata: can the user edit global categories and which group categories.
     *
     * @return array{can_edit_global: bool, editable_group_ids: int[]}
     */
    public function getEditorMeta(User $user): array
    {
        $editableGroupIds = array_map(
            fn (Group $g) => $g->id,
            $this->getAccessibleGroups($user)
        );

        return [
            'can_edit_global'    => $user->isAdmin(),
            'editable_group_ids' => $editableGroupIds,
        ];
    }

    /**
     * Full structure tree, optionally scoped to a group and/or filtered by media type.
     *
     * @return MemoContextCategory[]
     */
    public function getStructure(?int $groupId, ?string $mediaType): array
    {
        $query = MemoContextCategory::with(['subcategories', 'fields'])
            ->where(function ($q) use ($groupId) {
                $q->where('scope', 'global');
                if ($groupId !== null) {
                    $q->orWhere(fn ($inner) => $inner->where('scope', 'group')->where('group_id', $groupId));
                }
            });

        if ($mediaType !== null) {
            $query->where(fn ($q) => $q->whereNull('media_type_filter')
                ->orWhere('media_type_filter', $mediaType));
        }

        return $query->orderBy('scope')->orderBy('name')->get()->all();
    }

    // -------------------------------------------------------------------------
    // Category CRUD
    // -------------------------------------------------------------------------

    public function createCategory(User $user, CreateCategoryDTO $dto): MemoContextCategory
    {
        if ($dto->scope === 'global') {
            if (!$user->isAdmin()) {
                throw new AuthorizationException();
            }
        } else {
            if ($dto->groupId === null) {
                throw new BusinessRuleException('group_id é obrigatório para categorias de grupo.');
            }
            if (!$this->canManageGroupContext($user, $dto->groupId)) {
                throw new AuthorizationException();
            }
        }

        $cat = MemoContextCategory::create([
            'name'              => $dto->name,
            'scope'             => $dto->scope,
            'group_id'          => $dto->scope === 'group' ? $dto->groupId : null,
            'created_by_user_id'=> $user->id,
            'media_type_filter' => $dto->mediaTypeFilter,
        ]);

        return $cat->load(['subcategories', 'fields']);
    }

    public function updateCategory(User $user, int $categoryId, UpdateCategoryDTO $dto): MemoContextCategory
    {
        $cat = MemoContextCategory::find($categoryId);
        if (!$cat) throw new ResourceNotFoundException('Categoria');

        if (!$this->canEditCategory($user, $cat)) {
            throw new AuthorizationException();
        }

        $updates = [];
        if ($dto->name !== null)               $updates['name']              = $dto->name;
        if ($dto->mediaTypeFilter !== null)     $updates['media_type_filter'] = $dto->mediaTypeFilter;
        if ($dto->clearMediaTypeFilter)         $updates['media_type_filter'] = null;

        if (!empty($updates)) $cat->update($updates);

        return $cat->fresh(['subcategories', 'fields']);
    }

    public function deleteCategory(User $user, int $categoryId): void
    {
        $cat = MemoContextCategory::find($categoryId);
        if (!$cat) throw new ResourceNotFoundException('Categoria');

        if (!$this->canEditCategory($user, $cat)) {
            throw new AuthorizationException();
        }

        $cat->delete();
    }

    // -------------------------------------------------------------------------
    // Subcategory CRUD
    // -------------------------------------------------------------------------

    public function createSubcategory(User $user, int $categoryId, CreateSubcategoryDTO $dto): MemoContextSubcategory
    {
        $cat = MemoContextCategory::find($categoryId);
        if (!$cat) throw new ResourceNotFoundException('Categoria');

        if (!$this->canEditCategory($user, $cat)) {
            throw new AuthorizationException();
        }

        return MemoContextSubcategory::create([
            'category_id' => $categoryId,
            'name'        => $dto->name,
        ]);
    }

    public function updateSubcategory(User $user, int $subcategoryId, string $name): MemoContextSubcategory
    {
        $sub = MemoContextSubcategory::find($subcategoryId);
        if (!$sub) throw new ResourceNotFoundException('Subcategoria');

        if (!$this->canEditCategory($user, $sub->category)) {
            throw new AuthorizationException();
        }

        $sub->update(['name' => $name]);
        return $sub;
    }

    public function deleteSubcategory(User $user, int $subcategoryId): void
    {
        $sub = MemoContextSubcategory::find($subcategoryId);
        if (!$sub) throw new ResourceNotFoundException('Subcategoria');

        if (!$this->canEditCategory($user, $sub->category)) {
            throw new AuthorizationException();
        }

        $sub->delete();
    }

    // -------------------------------------------------------------------------
    // Field CRUD
    // -------------------------------------------------------------------------

    public function createField(User $user, int $categoryId, CreateFieldDTO $dto): MemoContextField
    {
        $cat = MemoContextCategory::find($categoryId);
        if (!$cat) throw new ResourceNotFoundException('Categoria');

        if (!$this->canEditCategory($user, $cat)) {
            throw new AuthorizationException();
        }

        return MemoContextField::create([
            'category_id' => $categoryId,
            'name'        => $dto->name,
            'field_type'  => $dto->fieldType,
            'is_required' => $dto->isRequired,
        ]);
    }

    public function updateField(User $user, int $fieldId, array $data): MemoContextField
    {
        $field = MemoContextField::find($fieldId);
        if (!$field) throw new ResourceNotFoundException('Campo');

        if (!$this->canEditCategory($user, $field->category)) {
            throw new AuthorizationException();
        }

        $updates = array_filter([
            'name'        => $data['name'] ?? null,
            'field_type'  => $data['field_type'] ?? null,
            'is_required' => isset($data['is_required']) ? (bool) $data['is_required'] : null,
        ], fn ($v) => $v !== null);

        if (!empty($updates)) $field->update($updates);

        return $field->fresh();
    }

    public function deleteField(User $user, int $fieldId): void
    {
        $field = MemoContextField::find($fieldId);
        if (!$field) throw new ResourceNotFoundException('Campo');

        if (!$this->canEditCategory($user, $field->category)) {
            throw new AuthorizationException();
        }

        $field->delete();
    }
}
