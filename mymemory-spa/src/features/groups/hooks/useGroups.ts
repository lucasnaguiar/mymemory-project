import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import type { AcceptInvitePayload, CreateGroupPayload, InviteMemberPayload } from '../../../types/api';
import {
  acceptInvite,
  createGroup,
  createInvite,
  fetchGroupPlans,
  fetchOwnerPanel,
  type GroupInviteEntry,
  type GroupOwnerPanel,
  type GroupPlan,
} from '../groupService';
import type { Group } from '../../../types/models';

// ---------------------------------------------------------------------------
// Keys
// ---------------------------------------------------------------------------

export const GROUP_KEYS = {
  plans:  ['groups', 'plans'] as const,
  panel:  (id: number) => ['groups', 'panel', id] as const,
} as const;

// ---------------------------------------------------------------------------
// Queries
// ---------------------------------------------------------------------------

export function useGroupPlans() {
  return useQuery<GroupPlan[], Error>({
    queryKey: GROUP_KEYS.plans,
    queryFn:  fetchGroupPlans,
  });
}

export function useOwnerPanel(groupId: number) {
  return useQuery<GroupOwnerPanel, Error>({
    queryKey: GROUP_KEYS.panel(groupId),
    queryFn:  () => fetchOwnerPanel(groupId),
    enabled:  groupId > 0,
  });
}

// ---------------------------------------------------------------------------
// Mutations
// ---------------------------------------------------------------------------

export function useCreateGroup() {
  return useMutation<Group, Error, CreateGroupPayload>({
    mutationFn: createGroup,
  });
}

export function useCreateInvite(groupId: number) {
  const qc = useQueryClient();
  return useMutation<GroupInviteEntry, Error, InviteMemberPayload>({
    mutationFn: (payload) => createInvite(groupId, payload),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: GROUP_KEYS.panel(groupId) });
    },
  });
}

export function useAcceptInvite() {
  return useMutation<Group, Error, AcceptInvitePayload>({
    mutationFn: acceptInvite,
  });
}
