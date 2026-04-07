import { http, unwrap } from '../../services/http';
import type {
  UpdatePreferencesPayload,
  UpdateWorkspacePayload,
} from '../../types/api';
import type {
  MediaLimits,
  User,
  UserPreferences,
  UsageSummary,
  Workspace,
  Group,
} from '../../types/models';

export async function fetchMe(): Promise<User> {
  const res = await http.get<{ data: User }>('/me');
  return unwrap(res);
}

export async function fetchUsage(): Promise<UsageSummary> {
  const res = await http.get<{ data: UsageSummary }>('/me/usage');
  return unwrap(res);
}

export async function fetchMediaLimits(): Promise<MediaLimits> {
  const res = await http.get<{ data: MediaLimits }>('/me/media-limits');
  return unwrap(res);
}

export async function fetchPreferences(): Promise<UserPreferences> {
  // Preferences are embedded in the /me response; this helper extracts them.
  // When a dedicated GET /me/preferences endpoint is added, update here.
  const user = await fetchMe();
  if (!user.preferences) throw new Error('Preferences not loaded.');
  return user.preferences;
}

export async function updatePreferences(payload: UpdatePreferencesPayload): Promise<UserPreferences> {
  const res = await http.patch<{ data: UserPreferences }>('/me/preferences', payload);
  return unwrap(res);
}

export async function fetchWorkspaceGroups(): Promise<Group[]> {
  const res = await http.get<{ data: Group[] }>('/me/workspace-groups');
  return unwrap(res);
}

export async function updateWorkspace(payload: UpdateWorkspacePayload): Promise<Workspace> {
  const res = await http.patch<{ data: { active_workspace_group_id: number | null } }>(
    '/me/workspace',
    payload,
  );
  const data = unwrap(res);
  return {
    type: data.active_workspace_group_id ? 'group' : 'personal',
    group: null, // caller should refetch workspace-groups if needed
  };
}
