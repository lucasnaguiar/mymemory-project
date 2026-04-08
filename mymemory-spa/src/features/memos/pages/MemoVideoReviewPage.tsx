import { useMemo } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { useConfirmVideo } from '../hooks/useMemoMedia';
import { isMediaReviewState, MemoMediaReviewLayout } from './MemoMediaReviewBase';

export default function MemoVideoReviewPage() {
  const navigate = useNavigate();
  const location = useLocation();
  const state    = useMemo(() => (isMediaReviewState(location.state) ? location.state : null), [location.state]);
  const confirm  = useConfirmVideo();

  if (!state) {
    return (
      <div className="min-h-screen flex items-center justify-center text-neutral-500">
        <div className="text-center space-y-4">
          <p>Nenhum vídeo para revisar.</p>
          <button onClick={() => navigate('/')} className="text-indigo-600 underline text-sm">Voltar ao início</button>
        </div>
      </div>
    );
  }

  const preview = (
    <div className="rounded-xl border border-neutral-200 bg-white p-4 flex items-center gap-3">
      <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-100">
        <svg className="w-5 h-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 10l4.553-2.276A1 1 0 0121 8.723v6.554a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
        </svg>
      </div>
      <div>
        <p className="text-sm font-medium text-neutral-700">Arquivo de vídeo</p>
        <p className="text-xs text-neutral-400">{state.fileName ?? 'sem nome'}</p>
      </div>
    </div>
  );

  return (
    <MemoMediaReviewLayout
      state={state}
      label="Vídeo"
      preview={preview}
      isBusy={confirm.isPending}
      errorMessage={confirm.error?.message}
      onConfirm={async (summary, keywords) => {
        await confirm.mutateAsync({ memo_id: state.memoId, summary, keywords });
      }}
    />
  );
}
