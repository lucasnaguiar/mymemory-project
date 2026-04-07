import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import type { UpdatePreferencesPayload, UpdateWorkspacePayload } from '../../../types/api';
import {
  fetchMe,
  fetchMediaLimits,
  fetchUsage,
  fetchWorkspaceGroups,
  updatePreferences,
  updateWorkspace,
} from '../meService';

export const ME_KEYS = {
  profile: ['me', 'profile'] as const,
  usage: ['me', 'usage'] as const,
  mediaLimits: ['me', 'media-limits'] as const,
  workspaceGroups: ['me', 'workspace-groups'] as const,
};

export function useMe() {
  return useQuery({
    queryKey: ME_KEYS.profile,
    queryFn: fetchMe,
    staleTime: 60_000,
  });
}

export function useUsage() {
  return useQuery({
    queryKey: ME_KEYS.usage,
    queryFn: fetchUsage,
    staleTime: 30_000,
  });
}

export function useMediaLimits() {
  return useQuery({
    queryKey: ME_KEYS.mediaLimits,
    queryFn: fetchMediaLimits,
    staleTime: 5 * 60_000,
  });
}

export function useWorkspaceGroups() {
  return useQuery({
    queryKey: ME_KEYS.workspaceGroups,
    queryFn: fetchWorkspaceGroups,
    staleTime: 60_000,
  });
}

export function useUpdatePreferences() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: UpdatePreferencesPayload) => updatePreferences(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ME_KEYS.profile });
    },
  });
}

export function useUpdateWorkspace() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: UpdateWorkspacePayload) => updateWorkspace(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ME_KEYS.profile });
      queryClient.invalidateQueries({ queryKey: ME_KEYS.usage });
    },
  });
}
