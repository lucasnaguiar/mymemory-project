import { http, unwrap } from '../../services/http';

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

export interface Plan {
  id: number;
  name: string;
  type: 'individual' | 'group';
  is_active: boolean;
  max_memos: number | null;
  storage_gb: number;
  api_credits_per_month: number | null;
  downloads_per_month: number | null;
  supports_audio_video: boolean;
  supports_chunking: boolean;
  price_cents: number;
  user_count: number | null;
  group_count: number | null;
}

export interface CreatePlanPayload {
  name: string;
  type: 'individual' | 'group';
  is_active?: boolean;
  max_memos?: number | null;
  storage_gb: number;
  api_credits_per_month?: number | null;
  downloads_per_month?: number | null;
  supports_audio_video?: boolean;
  supports_chunking?: boolean;
  price_cents?: number;
}

export interface MediaSetting {
  id: number;
  subscription_plan_id: number;
  media_type: 'image' | 'audio' | 'video' | 'document';
  max_file_size_mb: number;
  max_chunk_minutes: number | null;
  ocr_correction_threshold_default: number | null;
}

export interface UpsertMediaSettingPayload {
  media_type: 'image' | 'audio' | 'video' | 'document';
  max_file_size_mb: number;
  max_chunk_minutes?: number | null;
  ocr_correction_threshold_default?: number | null;
}

export interface DocumentAiRouting {
  config: Record<string, string>;
  using_defaults: boolean;
  updated_by: { id: number; name: string } | null;
  updated_at: string | null;
}

export interface CostReportRow {
  type: string;
  memo_count: number;
}

export interface CostReport {
  rows: CostReportRow[];
  total_memos: number;
  period: { from: string | null; to: string | null };
}

export interface SoftDeletedMonth {
  year: number;
  month: number;
  total: number;
  by_type: Record<string, number>;
}

// ---------------------------------------------------------------------------
// Subscription plans
// ---------------------------------------------------------------------------

export const fetchPlans = (): Promise<Plan[]> =>
  http.get('/admin/subscription-plans').then(r => unwrap<Plan[]>(r));

export const createPlan = (payload: CreatePlanPayload): Promise<Plan> =>
  http.post('/admin/subscription-plans', payload).then(r => unwrap<Plan>(r));

export const updatePlan = (id: number, payload: Partial<CreatePlanPayload>): Promise<Plan> =>
  http.patch(`/admin/subscription-plans/${id}`, payload).then(r => unwrap<Plan>(r));

export const deletePlan = (id: number): Promise<void> =>
  http.delete(`/admin/subscription-plans/${id}`).then(() => undefined);

// ---------------------------------------------------------------------------
// Media settings
// ---------------------------------------------------------------------------

export const fetchMediaSettings = (planId: number): Promise<MediaSetting[]> =>
  http.get(`/admin/subscription-plans/${planId}/media-settings`).then(r => unwrap<MediaSetting[]>(r));

export const upsertMediaSetting = (planId: number, payload: UpsertMediaSettingPayload): Promise<MediaSetting> =>
  http.put(`/admin/subscription-plans/${planId}/media-settings`, payload).then(r => unwrap<MediaSetting>(r));

// ---------------------------------------------------------------------------
// Document AI routing
// ---------------------------------------------------------------------------

export const fetchDocumentAiRouting = (): Promise<DocumentAiRouting> =>
  http.get('/admin/document-ai-routing').then(r => unwrap<DocumentAiRouting>(r));

export const updateDocumentAiRouting = (config: Record<string, string>): Promise<DocumentAiRouting> =>
  http.put('/admin/document-ai-routing', { config }).then(r => unwrap<DocumentAiRouting>(r));

// ---------------------------------------------------------------------------
// Reports & soft-delete
// ---------------------------------------------------------------------------

export interface CostReportParams {
  date_from?: string;
  date_to?: string;
  media_type?: string;
  plan_id?: number;
}

export const fetchCostReport = (params: CostReportParams = {}): Promise<CostReport> =>
  http.get('/admin/cost-report', { params }).then(r => unwrap<CostReport>(r));

export const fetchSoftDeletedSummary = (): Promise<SoftDeletedMonth[]> =>
  http.get('/admin/soft-deleted-memos/monthly-summary').then(r => unwrap<SoftDeletedMonth[]>(r));

export const hardDeleteMonth = (year: number, month: number): Promise<{ deleted_count: number }> =>
  http.delete('/admin/soft-deleted-memos/hard-delete-month', { data: { year, month } })
    .then(r => unwrap<{ deleted_count: number }>(r));
