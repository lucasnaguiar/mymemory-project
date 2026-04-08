import { useMutation, useQueryClient } from '@tanstack/react-query';
import { ME_KEYS } from '../../me/hooks/useMe';
import {
  confirmAudio,
  confirmDocument,
  confirmImage,
  confirmVideo,
  processAudio,
  processDocument,
  processImage,
  processVideo,
} from '../memoService';

// ---------------------------------------------------------------------------
// Image
// ---------------------------------------------------------------------------

export function useProcessImage() {
  return useMutation({ mutationFn: processImage });
}

export function useConfirmImage() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: confirmImage,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ME_KEYS.usage }),
  });
}

// ---------------------------------------------------------------------------
// Audio
// ---------------------------------------------------------------------------

export function useProcessAudio() {
  return useMutation({ mutationFn: processAudio });
}

export function useConfirmAudio() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: confirmAudio,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ME_KEYS.usage }),
  });
}

// ---------------------------------------------------------------------------
// Video
// ---------------------------------------------------------------------------

export function useProcessVideo() {
  return useMutation({ mutationFn: processVideo });
}

export function useConfirmVideo() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: confirmVideo,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ME_KEYS.usage }),
  });
}

// ---------------------------------------------------------------------------
// Document
// ---------------------------------------------------------------------------

export function useProcessDocument() {
  return useMutation({ mutationFn: processDocument });
}

export function useConfirmDocument() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: confirmDocument,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ME_KEYS.usage }),
  });
}
