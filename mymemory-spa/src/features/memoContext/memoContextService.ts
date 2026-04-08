import { http, unwrap } from '../../services/http';
import type {
  CreateCategoryPayload,
  CreateFieldPayload,
  CreateSubcategoryPayload,
  UpdateCategoryPayload,
  UpdateFieldPayload,
  UpdateSubcategoryPayload,
} from '../../types/api';
import type { MemoContextCategory, MemoContextField, MemoContextSubcategory } from '../../types/models';

// ---------------------------------------------------------------------------
// Extra types
// ---------------------------------------------------------------------------

export interface EditorMeta {
  can_edit_global: boolean;
  editable_group_ids: number[];
}

export interface ContextGroup {
  id: number;
  name: string;
}

export interface StructureParams {
  groupId?: number | null;
  mediaType?: string | null;
}

// ---------------------------------------------------------------------------
// Read
// ---------------------------------------------------------------------------

export async function fetchContextGroups(): Promise<ContextGroup[]> {
  const res = await http.get<{ data: ContextGroup[] }>('/memo-context/groups');
  return res.data.data;
}

export async function fetchEditorMeta(): Promise<EditorMeta> {
  const res = await http.get<{ data: EditorMeta }>('/memo-context/editor-meta');
  return unwrap(res);
}

export async function fetchStructure(params: StructureParams = {}): Promise<MemoContextCategory[]> {
  const q = new URLSearchParams();
  if (params.groupId != null) q.set('groupId', String(params.groupId));
  if (params.mediaType)       q.set('mediaType', params.mediaType);
  const res = await http.get<{ data: MemoContextCategory[] }>(`/memo-context/structure?${q}`);
  return res.data.data;
}

// ---------------------------------------------------------------------------
// Category
// ---------------------------------------------------------------------------

export async function createCategory(payload: CreateCategoryPayload): Promise<MemoContextCategory> {
  const res = await http.post<{ data: MemoContextCategory }>('/memo-context/categories', payload);
  return unwrap(res);
}

export async function updateCategory(id: number, payload: UpdateCategoryPayload): Promise<MemoContextCategory> {
  const res = await http.patch<{ data: MemoContextCategory }>(`/memo-context/categories/${id}`, payload);
  return unwrap(res);
}

export async function deleteCategory(id: number): Promise<void> {
  await http.delete(`/memo-context/categories/${id}`);
}

// ---------------------------------------------------------------------------
// Subcategory
// ---------------------------------------------------------------------------

export async function createSubcategory(categoryId: number, payload: CreateSubcategoryPayload): Promise<MemoContextSubcategory> {
  const res = await http.post<{ data: MemoContextSubcategory }>(`/memo-context/categories/${categoryId}/subcategories`, payload);
  return unwrap(res);
}

export async function updateSubcategory(id: number, payload: UpdateSubcategoryPayload): Promise<MemoContextSubcategory> {
  const res = await http.patch<{ data: MemoContextSubcategory }>(`/memo-context/subcategories/${id}`, payload);
  return unwrap(res);
}

export async function deleteSubcategory(id: number): Promise<void> {
  await http.delete(`/memo-context/subcategories/${id}`);
}

// ---------------------------------------------------------------------------
// Field
// ---------------------------------------------------------------------------

export async function createField(categoryId: number, payload: CreateFieldPayload): Promise<MemoContextField> {
  const res = await http.post<{ data: MemoContextField }>(`/memo-context/categories/${categoryId}/fields`, payload);
  return unwrap(res);
}

export async function updateField(id: number, payload: UpdateFieldPayload): Promise<MemoContextField> {
  const res = await http.patch<{ data: MemoContextField }>(`/memo-context/fields/${id}`, payload);
  return unwrap(res);
}

export async function deleteField(id: number): Promise<void> {
  await http.delete(`/memo-context/fields/${id}`);
}
