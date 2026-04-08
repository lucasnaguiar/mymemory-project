import { useMemo } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { useConfirmImage } from '../hooks/useMemoMedia';
import { isMediaReviewState, MemoMediaReviewLayout } from './MemoMediaReviewBase';

export default function MemoImageReviewPage() {
  const navigate   = useNavigate();
  const location   = useLocation();
  const state      = useMemo(() => (isMediaReviewState(location.state) ? location.state : null), [location.state]);
  const confirm    = useConfirmImage();

  if (!state) {
    return (
      <div className="min-h-screen flex items-center justify-center text-neutral-500">
        <div className="text-center space-y-4">
          <p>Nenhuma imagem para revisar.</p>
          <button onClick={() => navigate('/')} className="text-indigo-600 underline text-sm">Voltar ao início</button>
        </div>
      </div>
    );
  }

  const preview = state.fileName ? (
    <div className="rounded-xl border border-neutral-200 bg-neutral-100 flex items-center justify-center h-32">
      <svg className="w-12 h-12 text-neutral-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
    </div>
  ) : null;

  return (
    <MemoMediaReviewLayout
      state={state}
      label="Imagem"
      preview={preview}
      isBusy={confirm.isPending}
      errorMessage={confirm.error?.message}
      onConfirm={async (summary, keywords) => {
        await confirm.mutateAsync({ memo_id: state.memoId, summary, keywords });
      }}
    />
  );
}
