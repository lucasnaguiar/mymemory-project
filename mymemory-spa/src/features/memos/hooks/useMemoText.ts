import { useMutation, useQueryClient } from '@tanstack/react-query';
import { ME_KEYS } from '../../me/hooks/useMe';
import { confirmText, createText, processText } from '../memoService';

export function useProcessText() {
  return useMutation({ mutationFn: processText });
}

export function useConfirmText() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: confirmText,
    onSuccess: () => {
      // Invalidate usage after confirming a memo
      queryClient.invalidateQueries({ queryKey: ME_KEYS.usage });
    },
  });
}

export function useCreateText() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: createText,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ME_KEYS.usage });
    },
  });
}
