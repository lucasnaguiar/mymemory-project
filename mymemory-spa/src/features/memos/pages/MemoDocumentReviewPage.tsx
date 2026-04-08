import { useMemo } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { useConfirmDocument } from '../hooks/useMemoMedia';
import { isMediaReviewState, MemoMediaReviewLayout } from './MemoMediaReviewBase';

export default function MemoDocumentReviewPage() {
  const navigate = useNavigate();
  const location = useLocation();
  const state    = useMemo(() => (isMediaReviewState(location.state) ? location.state : null), [location.state]);
  const confirm  = useConfirmDocument();

  if (!state) {
    return (
      <div className="min-h-screen flex items-center justify-center text-neutral-500">
        <div className="text-center space-y-4">
          <p>Nenhum documento para revisar.</p>
          <button onClick={() => navigate('/')} className="text-indigo-600 underline text-sm">Voltar ao início</button>
        </div>
      </div>
    );
  }

  const preview = (
    <div className="rounded-xl border border-neutral-200 bg-white p-4 flex items-center gap-3">
      <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100">
        <svg className="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
      </div>
      <div>
        <p className="text-sm font-medium text-neutral-700">Documento</p>
        <p className="text-xs text-neutral-400">{state.fileName ?? 'sem nome'}</p>
      </div>
    </div>
  );

  return (
    <MemoMediaReviewLayout
      state={state}
      label="Documento"
      preview={preview}
      isBusy={confirm.isPending}
      errorMessage={confirm.error?.message}
      onConfirm={async (summary, keywords) => {
        await confirm.mutateAsync({ memo_id: state.memoId, summary, keywords });
      }}
    />
  );
}
