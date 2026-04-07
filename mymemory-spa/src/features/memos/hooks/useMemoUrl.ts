import { useMutation, useQueryClient } from '@tanstack/react-query';
import { ME_KEYS } from '../../me/hooks/useMe';
import { confirmUrl, createUrl, processUrl } from '../memoService';

export function useProcessUrl() {
  return useMutation({ mutationFn: processUrl });
}

export function useConfirmUrl() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: confirmUrl,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ME_KEYS.usage });
    },
  });
}

export function useCreateUrl() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: createUrl,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ME_KEYS.usage });
    },
  });
}
