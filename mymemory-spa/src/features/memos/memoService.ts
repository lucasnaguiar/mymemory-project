import { http, unwrap } from '../../services/http';
import type {
  ConfirmMediaPayload,
  ConfirmTextPayload,
  ConfirmUrlPayload,
  CreateTextPayload,
  CreateUrlPayload,
  ProcessTextPayload,
  ProcessUrlPayload,
} from '../../types/api';
import type { Memo, MemoType } from '../../types/models';

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
