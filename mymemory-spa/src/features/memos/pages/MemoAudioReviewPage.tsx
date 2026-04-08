import { useMemo } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { useConfirmAudio } from '../hooks/useMemoMedia';
import { isMediaReviewState, MemoMediaReviewLayout } from './MemoMediaReviewBase';

export default function MemoAudioReviewPage() {
  const navigate = useNavigate();
  const location = useLocation();
  const state    = useMemo(() => (isMediaReviewState(location.state) ? location.state : null), [location.state]);
  const confirm  = useConfirmAudio();

  if (!state) {
    return (
      <div className="min-h-screen flex items-center justify-center text-neutral-500">
        <div className="text-center space-y-4">
          <p>Nenhum áudio para revisar.</p>
          <button onClick={() => navigate('/')} className="text-indigo-600 underline text-sm">Voltar ao início</button>
        </div>
      </div>
    );
  }

  const preview = (
    <div className="rounded-xl border border-neutral-200 bg-white p-4 flex items-center gap-3">
      <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-100">
        <svg className="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
        </svg>
      </div>
      <div>
        <p className="text-sm font-medium text-neutral-700">Arquivo de áudio</p>
        <p className="text-xs text-neutral-400">{state.fileName ?? 'sem nome'}</p>
      </div>
    </div>
  );

  return (
    <MemoMediaReviewLayout
      state={state}
      label="Áudio"
      preview={preview}
      isBusy={confirm.isPending}
      errorMessage={confirm.error?.message}
      onConfirm={async (summary, keywords) => {
        await confirm.mutateAsync({ memo_id: state.memoId, summary, keywords });
      }}
    />
  );
}
