/**
 * Core entity types — mymemory-spa
 *
 * Drafts aligned to the API data model (Etapa 1).
 * These will be refined as API Resources are implemented in subsequent etapas.
 */

// ---------------------------------------------------------------------------
// Enums / literals
// ---------------------------------------------------------------------------

export type UserRole = 'user' | 'admin';

export type PlanType = 'individual' | 'group';

export type MemoType = 'text' | 'url' | 'image' | 'audio' | 'video' | 'document';

export type MemoStatus = 'draft' | 'confirmed';

export type AiLevel = 'none' | 'basic' | 'full';

export type GroupMemberRole = 'editor' | 'viewer';

export type MediaType = 'image' | 'audio' | 'video' | 'document';

export type ContextScope = 'global' | 'group';

export type ContextFieldType = 'text' | 'number' | 'date' | 'boolean';

// ---------------------------------------------------------------------------
// Subscription Plans
// ---------------------------------------------------------------------------

export interface SubscriptionPlan {
  id: number;
  name: string;
  type: PlanType;
  is_active: boolean;
  max_memos: number | null;
  storage_gb: number;
  api_credits_per_month: number | null;
  downloads_per_month: number | null;
  supports_audio_video: boolean;
  supports_chunking: boolean;
  price_cents: number;
  created_at: string;
  updated_at: string;
}

// ---------------------------------------------------------------------------
// Users
// ---------------------------------------------------------------------------

export interface User {
  id: number;
  name: string;
  email: string;
  email_verified_at: string | null;
  role: UserRole;
  subscription_plan_id: number | null;
  active_workspace_group_id: number | null;
  created_at: string;
  updated_at: string;

  // Eager-loaded relations (optional)
  subscription_plan?: SubscriptionPlan;
  preferences?: UserPreferences;
  active_workspace_group?: Group;
}

// ---------------------------------------------------------------------------
// User Preferences
// ---------------------------------------------------------------------------

export interface UserPreferences {
  id: number;
  user_id: number;
  ai_level_text: AiLevel;
  ai_level_url: AiLevel;
  ai_level_image: AiLevel;
  ai_level_audio: AiLevel;
  ai_level_video: AiLevel;
  ai_level_document: AiLevel;
  confirm_before_processing: boolean;
  sound_enabled: boolean;
  ocr_correction_threshold: number | null;
  created_at: string;
  updated_at: string;
}

// ---------------------------------------------------------------------------
// Groups
// ---------------------------------------------------------------------------

export interface Group {
  id: number;
  name: string;
  owner_user_id: number;
  subscription_plan_id: number;
  deleted_at: string | null;
  created_at: string;
  updated_at: string;

  owner?: User;
  subscription_plan?: SubscriptionPlan;
}

export interface GroupMember {
  id: number;
  group_id: number;
  user_id: number;
  role: GroupMemberRole;
  joined_at: string | null;
  created_at: string;
  updated_at: string;

  user?: User;
  group?: Group;
}

export interface GroupInvite {
  id: number;
  group_id: number;
  email: string;
  role: GroupMemberRole;
  invited_by_user_id: number;
  expires_at: string | null;
  accepted_at: string | null;
  created_at: string;
  updated_at: string;
}

// ---------------------------------------------------------------------------
// Memos
// ---------------------------------------------------------------------------

export interface Memo {
  id: number;
  user_id: number;
  group_id: number | null;
  type: MemoType;
  status: MemoStatus;
  title: string | null;
  content: string | null;
  summary: string | null;
  keywords: string[] | null;
  source_url: string | null;
  ai_level: AiLevel;
  deleted_at: string | null;
  created_at: string;
  updated_at: string;

  user?: User;
  group?: Group;
  file?: MemoFile;
}

export interface MemoFile {
  id: number;
  memo_id: number;
  disk: string;
  storage_key: string;
  original_filename: string;
  mime_type: string;
  size_bytes: number;
  created_at: string;
  updated_at: string;
}

// ---------------------------------------------------------------------------
// Usage
// ---------------------------------------------------------------------------

export interface MonthlyUsage {
  id: number;
  user_id: number;
  group_id: number | null;
  year: number;
  month: number;
  api_credits_used: number;
  downloads_count: number;
  memos_created_count: number;
  created_at: string;
  updated_at: string;
}

/** Aggregated usage summary returned by GET /api/v1/me/usage */
export interface UsageSummary {
  memos: {
    used: number;
    limit: number | null;
  };
  storage_bytes: {
    used: number;
    limit_gb: number;
  };
  api_credits: {
    used: number;
    limit: number | null;
  };
  downloads: {
    used: number;
    limit: number | null;
  };
}

/** Limits for file uploads returned by GET /api/v1/me/media-limits */
export type MediaLimits = Record<
  MediaType,
  {
    max_file_size_mb: number;
    max_chunk_minutes: number | null;
    ocr_correction_threshold_default: number | null;
  }
>;

// ---------------------------------------------------------------------------
// Memo Context
// ---------------------------------------------------------------------------

export interface MemoContextCategory {
  id: number;
  name: string;
  scope: ContextScope;
  group_id: number | null;
  created_by_user_id: number;
  media_type_filter: MemoType | null;
  deleted_at: string | null;
  created_at: string;
  updated_at: string;

  subcategories?: MemoContextSubcategory[];
  fields?: MemoContextField[];
}

export interface MemoContextSubcategory {
  id: number;
  category_id: number;
  name: string;
  deleted_at: string | null;
  created_at: string;
  updated_at: string;
}

export interface MemoContextField {
  id: number;
  category_id: number;
  name: string;
  field_type: ContextFieldType;
  is_required: boolean;
  deleted_at: string | null;
  created_at: string;
  updated_at: string;
}

// ---------------------------------------------------------------------------
// Admin
// ---------------------------------------------------------------------------

export interface PlanMediaSetting {
  id: number;
  subscription_plan_id: number;
  media_type: MediaType;
  max_file_size_mb: number;
  max_chunk_minutes: number | null;
  ocr_correction_threshold_default: number | null;
  created_at: string;
  updated_at: string;
}

export interface DocumentAiRouting {
  id: number;
  config: Record<string, unknown>;
  updated_by_user_id: number | null;
  created_at: string;
  updated_at: string;
}

// ---------------------------------------------------------------------------
// Workspace
// ---------------------------------------------------------------------------

/**
 * Represents the currently active workspace context.
 * group = null means personal workspace.
 */
export interface Workspace {
  type: 'personal' | 'group';
  group: Group | null;
}
