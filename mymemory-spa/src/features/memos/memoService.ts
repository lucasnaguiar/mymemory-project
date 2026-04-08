import { http, unwrap } from '../../services/http';
import type {
  ConfirmMediaPayload,
  ConfirmTextPayload,
  ConfirmUrlPayload,
  CreateTextPayload,
  CreateUrlPayload,
  ProcessTextPayload,
  ProcessUrlPayload,
  SearchMemosPayload,
  UpdateMemoPayload,
} from '../../types/api';
import type { Memo, MemoType } from '../../types/models';

export interface RecentMemosParams {
  limit?: number;
  group_id?: number | null;
}

export interface SearchResult {
  items: Memo[];
  total: number;
  query: string;
  operator: string;
  highlight_terms: string[];
}

export interface AuthorEntry {
  id: number;
  name: string | null;
  email: string;
}

// ---------------------------------------------------------------------------
// Text memo
// ---------------------------------------------------------------------------

export async function processText(payload: ProcessTextPayload): Promise<Memo> {
  const res = await http.post<{ data: Memo }>('/memos/text/process', payload);
  return unwrap(res);
}

export async function confirmText(payload: ConfirmTextPayload): Promise<Memo> {
  const res = await http.post<{ data: Memo }>('/memos/text/confirm', payload);
  return unwrap(res);
}

export async function createText(payload: CreateTextPayload): Promise<Memo> {
  const res = await http.post<{ data: Memo }>('/memos/text', payload);
  return unwrap(res);
}

// ---------------------------------------------------------------------------
// URL memo
// ---------------------------------------------------------------------------

export async function processUrl(payload: ProcessUrlPayload): Promise<Memo> {
  const res = await http.post<{ data: Memo }>('/memos/url/process', payload);
  return unwrap(res);
}

export async function confirmUrl(payload: ConfirmUrlPayload): Promise<Memo> {
  const res = await http.post<{ data: Memo }>('/memos/url/confirm', payload);
  return unwrap(res);
}

export async function createUrl(payload: CreateUrlPayload): Promise<Memo> {
  const res = await http.post<{ data: Memo }>('/memos/url', payload);
  return unwrap(res);
}

// ---------------------------------------------------------------------------
// Media memos (image, audio, video, document) — multipart uploads
// ---------------------------------------------------------------------------

export interface ProcessMediaPayload {
  file: File;
  aiLevel?: string;
  groupId?: number | null;
  onUploadProgress?: (pct: number) => void;
}

async function processMedia(
  type: Extract<MemoType, 'image' | 'audio' | 'video' | 'document'>,
  payload: ProcessMediaPayload,
): Promise<Memo> {
  const form = new FormData();
  form.append('file', payload.file);
  if (payload.aiLevel) form.append('ai_level', payload.aiLevel);
  if (payload.groupId != null) form.append('group_id', String(payload.groupId));

  const res = await http.post<{ data: Memo }>(`/memos/${type}/process`, form, {
    headers: { 'Content-Type': 'multipart/form-data' },
    onUploadProgress: payload.onUploadProgress
      ? (e) => {
          if (e.total) payload.onUploadProgress!(Math.round((e.loaded / e.total) * 100));
        }
      : undefined,
  });
  return unwrap(res);
}

export const processImage    = (p: ProcessMediaPayload) => processMedia('image', p);
export const processAudio    = (p: ProcessMediaPayload) => processMedia('audio', p);
export const processVideo    = (p: ProcessMediaPayload) => processMedia('video', p);
export const processDocument = (p: ProcessMediaPayload) => processMedia('document', p);

async function confirmMedia(
  type: Extract<MemoType, 'image' | 'audio' | 'video' | 'document'>,
  payload: ConfirmMediaPayload,
): Promise<Memo> {
  const res = await http.post<{ data: Memo }>(`/memos/${type}/confirm`, payload);
  return unwrap(res);
}

export const confirmImage    = (p: ConfirmMediaPayload) => confirmMedia('image', p);
export const confirmAudio    = (p: ConfirmMediaPayload) => confirmMedia('audio', p);
export const confirmVideo    = (p: ConfirmMediaPayload) => confirmMedia('video', p);
export const confirmDocument = (p: ConfirmMediaPayload) => confirmMedia('document', p);

// ---------------------------------------------------------------------------
// Listing, search, CRUD
// ---------------------------------------------------------------------------

export async function fetchRecentMemos(params: RecentMemosParams = {}): Promise<{ items: Memo[]; total: number }> {
  const query = new URLSearchParams();
  if (params.limit) query.set('limit', String(params.limit));
  if (params.group_id != null) query.set('group_id', String(params.group_id));

  const res = await http.get<{ data: Memo[]; meta: { total: number } }>(`/memos/recent?${query}`);
  return { items: res.data.data, total: res.data.meta.total };
}

export async function searchMemos(payload: SearchMemosPayload): Promise<SearchResult> {
  const res = await http.post<{
    data: Memo[];
    meta: { total: number; query: string; operator: string; highlight_terms: string[] };
  }>('/memos/search', payload);
  return {
    items: res.data.data,
    total: res.data.meta.total,
    query: res.data.meta.query,
    operator: res.data.meta.operator,
    highlight_terms: res.data.meta.highlight_terms,
  };
}

export async function fetchSynonyms(query: string): Promise<string[]> {
  const res = await http.post<{ data: { synonyms: string[] } }>('/memos/search/synonyms', { query });
  return res.data.data.synonyms;
}

export async function fetchAuthors(group_id?: number | null): Promise<AuthorEntry[]> {
  const query = group_id != null ? `?group_id=${group_id}` : '';
  const res   = await http.get<{ data: AuthorEntry[] }>(`/memos/search/authors${query}`);
  return res.data.data;
}

export async function fetchMemo(id: number): Promise<Memo> {
  const res = await http.get<{ data: Memo }>(`/memos/${id}`);
  return unwrap(res);
}

export async function updateMemo(id: number, payload: UpdateMemoPayload): Promise<Memo> {
  const res = await http.patch<{ data: Memo }>(`/memos/${id}`, payload);
  return unwrap(res);
}

export async function deleteMemo(id: number): Promise<void> {
  await http.delete(`/memos/${id}`);
}

export function getMemoFileUrl(id: number): string {
  const base = import.meta.env.VITE_API_URL ?? 'http://localhost:7100/api/v1';
  return `${base}/memos/${id}/file`;
}
