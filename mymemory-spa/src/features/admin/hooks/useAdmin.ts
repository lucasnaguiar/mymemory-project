import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  createPlan,
  deletePlan,
  fetchCostReport,
  fetchDocumentAiRouting,
  fetchMediaSettings,
  fetchPlans,
  fetchSoftDeletedSummary,
  hardDeleteMonth,
  updateDocumentAiRouting,
  updatePlan,
  upsertMediaSetting,
  type CostReport,
  type CostReportParams,
  type CreatePlanPayload,
  type DocumentAiRouting,
  type MediaSetting,
  type Plan,
  type SoftDeletedMonth,
  type UpsertMediaSettingPayload,
} from '../adminService';

// ---------------------------------------------------------------------------
// Keys
// ---------------------------------------------------------------------------

export const ADMIN_KEYS = {
  plans:            ['admin', 'plans'] as const,
  mediaSettings:    (planId: number) => ['admin', 'media-settings', planId] as const,
  documentAi:       ['admin', 'document-ai-routing'] as const,
  costReport:       (params: CostReportParams) => ['admin', 'cost-report', params] as const,
  softDeletedMonths:['admin', 'soft-deleted-monthly'] as const,
} as const;

// ---------------------------------------------------------------------------
// Queries
// ---------------------------------------------------------------------------

export function usePlans() {
  return useQuery<Plan[], Error>({
    queryKey: ADMIN_KEYS.plans,
    queryFn:  fetchPlans,
  });
}

export function useMediaSettings(planId: number | null) {
  return useQuery<MediaSetting[], Error>({
    queryKey: ADMIN_KEYS.mediaSettings(planId ?? 0),
    queryFn:  () => fetchMediaSettings(planId!),
    enabled:  planId !== null,
  });
}

export function useDocumentAiRouting() {
  return useQuery<DocumentAiRouting, Error>({
    queryKey: ADMIN_KEYS.documentAi,
    queryFn:  fetchDocumentAiRouting,
  });
}

export function useCostReport(params: CostReportParams) {
  return useQuery<CostReport, Error>({
    queryKey: ADMIN_KEYS.costReport(params),
    queryFn:  () => fetchCostReport(params),
  });
}

export function useSoftDeletedSummary() {
  return useQuery<SoftDeletedMonth[], Error>({
    queryKey: ADMIN_KEYS.softDeletedMonths,
    queryFn:  fetchSoftDeletedSummary,
  });
}

// ---------------------------------------------------------------------------
// Mutations
// ---------------------------------------------------------------------------

export function useCreatePlan() {
  const qc = useQueryClient();
  return useMutation<Plan, Error, CreatePlanPayload>({
    mutationFn: createPlan,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ADMIN_KEYS.plans });
    },
  });
}

export function useUpdatePlan() {
  const qc = useQueryClient();
  return useMutation<Plan, Error, { id: number; payload: Partial<CreatePlanPayload> }>({
    mutationFn: ({ id, payload }) => updatePlan(id, payload),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ADMIN_KEYS.plans });
    },
  });
}

export function useDeletePlan() {
  const qc = useQueryClient();
  return useMutation<void, Error, number>({
    mutationFn: deletePlan,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ADMIN_KEYS.plans });
    },
  });
}

export function useUpsertMediaSetting(planId: number) {
  const qc = useQueryClient();
  return useMutation<MediaSetting, Error, UpsertMediaSettingPayload>({
    mutationFn: (payload) => upsertMediaSetting(planId, payload),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ADMIN_KEYS.mediaSettings(planId) });
    },
  });
}

export function useUpdateDocumentAiRouting() {
  const qc = useQueryClient();
  return useMutation<DocumentAiRouting, Error, Record<string, string>>({
    mutationFn: updateDocumentAiRouting,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ADMIN_KEYS.documentAi });
    },
  });
}

export function useHardDeleteMonth() {
  const qc = useQueryClient();
  return useMutation<{ deleted_count: number }, Error, { year: number; month: number }>({
    mutationFn: ({ year, month }) => hardDeleteMonth(year, month),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ADMIN_KEYS.softDeletedMonths });
    },
  });
}
