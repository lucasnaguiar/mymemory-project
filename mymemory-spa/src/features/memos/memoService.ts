import { http, unwrap } from '../../services/http';
import type {
  ConfirmTextPayload,
  ConfirmUrlPayload,
  CreateTextPayload,
  CreateUrlPayload,
  ProcessTextPayload,
  ProcessUrlPayload,
} from '../../types/api';
import type { Memo } from '../../types/models';

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
