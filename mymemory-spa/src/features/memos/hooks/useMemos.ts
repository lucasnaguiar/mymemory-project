import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import type { SearchMemosPayload, UpdateMemoPayload } from '../../../types/api';
import {
  deleteMemo,
  fetchAuthors,
  fetchMemo,
  fetchRecentMemos,
  fetchSynonyms,
  searchMemos,
  updateMemo,
  type RecentMemosParams,
  type SearchResult,
} from '../memoService';

// ---------------------------------------------------------------------------
// Cache key factory
// ---------------------------------------------------------------------------
export const MEMO_KEYS = {
  all:     ['memos'] as const,
  recent:  (params: RecentMemosParams) => ['memos', 'recent', params] as const,
  detail:  (id: number)                 => ['memos', 'detail', id] as const,
  authors: (groupId?: number | null)    => ['memos', 'authors', groupId] as const,
} as const;

// ---------------------------------------------------------------------------
// Queries
// ---------------------------------------------------------------------------

export function useRecentMemos(params: RecentMemosParams = {}) {
  return useQuery({
    queryKey: MEMO_KEYS.recent(params),
    queryFn:  () => fetchRecentMemos(params),
  });
}

export function useMemoDetail(id: number) {
  return useQuery({
    queryKey: MEMO_KEYS.detail(id),
    queryFn:  () => fetchMemo(id),
    enabled:  id > 0,
  });
}

export function useSearchAuthors(groupId?: number | null) {
  return useQuery({
    queryKey: MEMO_KEYS.authors(groupId),
    queryFn:  () => fetchAuthors(groupId),
  });
}

// ---------------------------------------------------------------------------
// Mutations
// ---------------------------------------------------------------------------

export function useSearchMemos() {
  return useMutation<SearchResult, Error, SearchMemosPayload>({
    mutationFn: searchMemos,
  });
}

export function useFetchSynonyms() {
  return useMutation<string[], Error, string>({
    mutationFn: fetchSynonyms,
  });
}

export function useUpdateMemo() {
  const qc = useQueryClient();
  return useMutation<
    Awaited<ReturnType<typeof updateMemo>>,
    Error,
    { id: number; payload: UpdateMemoPayload }
  >({
    mutationFn: ({ id, payload }) => updateMemo(id, payload),
    onSuccess: (memo) => {
      qc.setQueryData(MEMO_KEYS.detail(memo.id), memo);
      qc.invalidateQueries({ queryKey: MEMO_KEYS.all });
    },
  });
}

export function useDeleteMemo() {
  const qc = useQueryClient();
  return useMutation<void, Error, number>({
    mutationFn: deleteMemo,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: MEMO_KEYS.all });
    },
  });
}
