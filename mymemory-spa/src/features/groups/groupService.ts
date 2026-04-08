import { http, unwrap } from '../../services/http';
import type { CreateGroupPayload, InviteMemberPayload, AcceptInvitePayload } from '../../types/api';
import type { Group } from '../../types/models';

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

export interface GroupPlan {
  id: number;
  name: string;
  price_cents: number;
  storage_gb: number;
  max_memos: number | null;
  api_credits_per_month: number | null;
  downloads_per_month: number | null;
  supports_audio_video: boolean;
  supports_chunking: boolean;
}

export interface GroupMemberEntry {
  id: number;
  user_id: number;
  name: string | null;
  email: string | null;
  role: 'editor' | 'viewer';
  joined_at: string | null;
}

export interface GroupInviteEntry {
  id: number;
  email: string;
  role: 'editor' | 'viewer';
  status: 'pending' | 'accepted' | 'expired';
  expires_at: string | null;
  accepted_at: string | null;
  created_at: string;
}

export interface GroupOwnerPanel {
  group: {
    id: number;
    name: string;
    owner_user_id: number;
    subscription_plan_id: number;
    plan: GroupPlan | null;
    created_at: string;
  };
  memo_count: number;
  members: GroupMemberEntry[];
  invites: GroupInviteEntry[];
}

// ---------------------------------------------------------------------------
// API calls
// ---------------------------------------------------------------------------

export async function fetchGroupPlans(): Promise<GroupPlan[]> {
  const res = await http.get<{ data: GroupPlan[] }>('/group-plans');
  return res.data.data;
}

export async function createGroup(payload: CreateGroupPayload): Promise<Group> {
  const res = await http.post<{ data: Group }>('/groups', payload);
  return unwrap(res);
}

export async function fetchOwnerPanel(groupId: number): Promise<GroupOwnerPanel> {
  const res = await http.get<{ data: GroupOwnerPanel }>(`/groups/${groupId}/owner-panel`);
  return unwrap(res);
}

export async function createInvite(groupId: number, payload: InviteMemberPayload): Promise<GroupInviteEntry> {
  const res = await http.post<{ data: GroupInviteEntry }>(`/groups/${groupId}/invites`, payload);
  return unwrap(res);
}

export async function acceptInvite(payload: AcceptInvitePayload): Promise<Group> {
  const res = await http.post<{ data: Group }>('/group-invites/accept', payload);
  return unwrap(res);
}
