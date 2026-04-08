import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import type {
  CreateCategoryPayload,
  CreateFieldPayload,
  CreateSubcategoryPayload,
  UpdateCategoryPayload,
  UpdateFieldPayload,
  UpdateSubcategoryPayload,
} from '../../../types/api';
import type { MemoContextCategory, MemoContextField, MemoContextSubcategory } from '../../../types/models';
import {
  createCategory,
  createField,
  createSubcategory,
  deleteCategory,
  deleteField,
  deleteSubcategory,
  fetchContextGroups,
  fetchEditorMeta,
  fetchStructure,
  updateCategory,
  updateField,
  updateSubcategory,
  type ContextGroup,
  type EditorMeta,
  type StructureParams,
} from '../memoContextService';

// ---------------------------------------------------------------------------
// Keys
// ---------------------------------------------------------------------------

export const CTX_KEYS = {
  groups:    ['memo-context', 'groups'] as const,
  meta:      ['memo-context', 'editor-meta'] as const,
  structure: (params: StructureParams) => ['memo-context', 'structure', params] as const,
} as const;

// ---------------------------------------------------------------------------
// Queries
// ---------------------------------------------------------------------------

export function useContextGroups() {
  return useQuery<ContextGroup[], Error>({
    queryKey: CTX_KEYS.groups,
    queryFn:  fetchContextGroups,
  });
}

export function useEditorMeta() {
  return useQuery<EditorMeta, Error>({
    queryKey: CTX_KEYS.meta,
    queryFn:  fetchEditorMeta,
  });
}

export function useStructure(params: StructureParams = {}) {
  return useQuery<MemoContextCategory[], Error>({
    queryKey: CTX_KEYS.structure(params),
    queryFn:  () => fetchStructure(params),
  });
}

// ---------------------------------------------------------------------------
// Category mutations
// ---------------------------------------------------------------------------

function useInvalidateStructure() {
  const qc = useQueryClient();
  return () => qc.invalidateQueries({ queryKey: ['memo-context', 'structure'] });
}

export function useCreateCategory() {
  const invalidate = useInvalidateStructure();
  return useMutation<MemoContextCategory, Error, CreateCategoryPayload>({
    mutationFn: createCategory,
    onSuccess:  invalidate,
  });
}

export function useUpdateCategory() {
  const invalidate = useInvalidateStructure();
  return useMutation<MemoContextCategory, Error, { id: number; payload: UpdateCategoryPayload }>({
    mutationFn: ({ id, payload }) => updateCategory(id, payload),
    onSuccess:  invalidate,
  });
}

export function useDeleteCategory() {
  const invalidate = useInvalidateStructure();
  return useMutation<void, Error, number>({
    mutationFn: deleteCategory,
    onSuccess:  invalidate,
  });
}

// ---------------------------------------------------------------------------
// Subcategory mutations
// ---------------------------------------------------------------------------

export function useCreateSubcategory() {
  const invalidate = useInvalidateStructure();
  return useMutation<MemoContextSubcategory, Error, { categoryId: number; payload: CreateSubcategoryPayload }>({
    mutationFn: ({ categoryId, payload }) => createSubcategory(categoryId, payload),
    onSuccess:  invalidate,
  });
}

export function useUpdateSubcategory() {
  const invalidate = useInvalidateStructure();
  return useMutation<MemoContextSubcategory, Error, { id: number; payload: UpdateSubcategoryPayload }>({
    mutationFn: ({ id, payload }) => updateSubcategory(id, payload),
    onSuccess:  invalidate,
  });
}

export function useDeleteSubcategory() {
  const invalidate = useInvalidateStructure();
  return useMutation<void, Error, number>({
    mutationFn: deleteSubcategory,
    onSuccess:  invalidate,
  });
}

// ---------------------------------------------------------------------------
// Field mutations
// ---------------------------------------------------------------------------

export function useCreateField() {
  const invalidate = useInvalidateStructure();
  return useMutation<MemoContextField, Error, { categoryId: number; payload: CreateFieldPayload }>({
    mutationFn: ({ categoryId, payload }) => createField(categoryId, payload),
    onSuccess:  invalidate,
  });
}

export function useUpdateField() {
  const invalidate = useInvalidateStructure();
  return useMutation<MemoContextField, Error, { id: number; payload: UpdateFieldPayload }>({
    mutationFn: ({ id, payload }) => updateField(id, payload),
    onSuccess:  invalidate,
  });
}

export function useDeleteField() {
  const invalidate = useInvalidateStructure();
  return useMutation<void, Error, number>({
    mutationFn: deleteField,
    onSuccess:  invalidate,
  });
}
