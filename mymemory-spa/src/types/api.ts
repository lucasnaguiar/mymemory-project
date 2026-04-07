/**
 * API contract types — request/response shapes for each endpoint group.
 * These complement the entity types in models.ts.
 */

import type { AiLevel, GroupMemberRole, MemoType } from './models';

// ---------------------------------------------------------------------------
// Shared
// ---------------------------------------------------------------------------

export interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

// ---------------------------------------------------------------------------
// Me
// ---------------------------------------------------------------------------

export interface UpdatePreferencesPayload {
  ai_level_text?: AiLevel;
  ai_level_url?: AiLevel;
  ai_level_image?: AiLevel;
  ai_level_audio?: AiLevel;
  ai_level_video?: AiLevel;
  ai_level_document?: AiLevel;
  confirm_before_processing?: boolean;
  sound_enabled?: boolean;
  ocr_correction_threshold?: number | null;
}

export interface UpdateWorkspacePayload {
  group_id: number | null;
}

// ---------------------------------------------------------------------------
// Memos
// ---------------------------------------------------------------------------

export interface ProcessTextPayload {
  content: string;
  ai_level?: AiLevel;
  group_id?: number | null;
}

export interface ConfirmTextPayload {
  memo_id: number;
  title?: string;
  content: string;
  summary?: string;
  keywords?: string[];
}

export interface CreateTextPayload {
  title?: string;
  content: string;
  summary?: string;
  keywords?: string[];
  ai_level?: AiLevel;
  group_id?: number | null;
}

export interface ProcessUrlPayload {
  url: string;
  ai_level?: AiLevel;
  group_id?: number | null;
}

export interface ConfirmUrlPayload {
  memo_id: number;
  title?: string;
  summary?: string;
  keywords?: string[];
}

export interface UpdateMemoPayload {
  title?: string;
  content?: string;
  summary?: string;
  keywords?: string[];
}

export interface SearchMemosPayload {
  query: string;
  operator?: 'AND' | 'OR';
  date_from?: string;
  date_to?: string;
  author_id?: number;
  group_id?: number | null;
  type?: MemoType;
  page?: number;
  per_page?: number;
}

export interface RecentMemosParams {
  limit?: number;
  group_id?: number | null;
}

// ---------------------------------------------------------------------------
// Groups
// ---------------------------------------------------------------------------

export interface CreateGroupPayload {
  name: string;
  subscription_plan_id: number;
}

export interface InviteMemberPayload {
  email: string;
  role: GroupMemberRole;
}

export interface AcceptInvitePayload {
  token: string;
}

// ---------------------------------------------------------------------------
// Memo Context
// ---------------------------------------------------------------------------

export interface CreateCategoryPayload {
  name: string;
  scope?: 'global' | 'group';
  group_id?: number | null;
  media_type_filter?: MemoType | null;
}

export interface UpdateCategoryPayload {
  name?: string;
  media_type_filter?: MemoType | null;
}

export interface CreateSubcategoryPayload {
  name: string;
}

export interface UpdateSubcategoryPayload {
  name: string;
}

export interface CreateFieldPayload {
  name: string;
  field_type?: 'text' | 'number' | 'date' | 'boolean';
  is_required?: boolean;
}

export interface UpdateFieldPayload {
  name?: string;
  field_type?: 'text' | 'number' | 'date' | 'boolean';
  is_required?: boolean;
}

// ---------------------------------------------------------------------------
// Admin
// ---------------------------------------------------------------------------

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

export type UpdatePlanPayload = Partial<CreatePlanPayload>;

export interface UpdateMediaSettingsPayload {
  media_type: 'image' | 'audio' | 'video' | 'document';
  max_file_size_mb: number;
  max_chunk_minutes?: number | null;
  ocr_correction_threshold_default?: number | null;
}

export interface UpdateDocumentAiRoutingPayload {
  config: Record<string, unknown>;
}

export interface CostReportParams {
  date_from: string;
  date_to: string;
  media_type?: string;
  plan_id?: number;
}

export interface HardDeleteMonthPayload {
  year: number;
  month: number;
}
