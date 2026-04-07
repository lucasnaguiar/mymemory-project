import { useState, useMemo } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import clsx from 'clsx';
import { useConfirmText } from '../hooks/useMemoText';
import { useConfirmUrl } from '../hooks/useMemoUrl';

interface ReviewState {
  memoId: number;
  originalContent: string | null;
  suggestedSummary: string;
  suggestedKeywords: string;
  aiLevel: string;
  groupId: number | null;
  isUrl?: boolean;
  sourceUrl?: string | null;
}

function isReviewState(x: unknown): x is ReviewState {
  if (!x || typeof x !== 'object') return false;
  const o = x as Record<string, unknown>;
  return typeof o.memoId === 'number' && typeof o.suggestedSummary === 'string';
}

const MAX_SUMMARY_CHARS = 2000;

export default function MemoTextReviewPage() {
  const navigate = useNavigate();
  const location = useLocation();

  const state = useMemo(
    () => (isReviewState(location.state) ? location.state : null),
    [location.state],
  );

  const [summary, setSummary] = useState(state?.suggestedSummary ?? '');
  const [keywords, setKeywords] = useState(state?.suggestedKeywords ?? '');

  const confirmText = useConfirmText();
  const confirmUrl = useConfirmUrl();

  const isBusy = confirmText.isPending || confirmUrl.isPending;
  const errorMessage = confirmText.error?.message ?? confirmUrl.error?.message;
  const overLimit = summary.length > MAX_SUMMARY_CHARS;

  if (!state) {
    return (
      <div className="min-h-screen flex items-center justify-center text-neutral-500">
        <div className="text-center space-y-4">
          <p className="text-lg">Nenhum memo para revisar.</p>
          <button
            onClick={() => navigate('/')}
            className="text-indigo-600 underline text-sm"
          >
            Voltar ao início
          </button>
        </div>
      </div>
    );
  }

  async function handleConfirm() {
    if (!state) return;
    if (overLimit) return;

    const parsedKeywords = keywords
      .split(',')
      .map((k) => k.trim())
      .filter(Boolean);

    if (state.isUrl) {
      await confirmUrl.mutateAsync({
        memo_id: state.memoId,
        summary: summary.trim() || undefined,
        keywords: parsedKeywords.length > 0 ? parsedKeywords : undefined,
      });
    } else {
      await confirmText.mutateAsync({
        memo_id: state.memoId,
        content: state.originalContent ?? '',
        summary: summary.trim() || undefined,
        keywords: parsedKeywords.length > 0 ? parsedKeywords : undefined,
      });
    }

    navigate('/');
  }

  function handleDiscard() {
    navigate(-1);
  }

  return (
    <div className="min-h-screen bg-neutral-50">
      {/* Header */}
      <header className="border-b border-neutral-200 bg-white px-4 py-4">
        <div className="max-w-2xl mx-auto flex items-center gap-3">
          <button
            onClick={handleDiscard}
            className="text-neutral-500 hover:text-neutral-700"
            aria-label="Voltar"
          >
            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <h1 className="text-lg font-semibold text-neutral-900">Revisar memo</h1>
        </div>
      </header>

      <main className="max-w-2xl mx-auto px-4 py-6 space-y-6">
        {/* Source info */}
        {state.isUrl && state.sourceUrl && (
          <div className="rounded-lg border border-neutral-200 bg-white p-3 flex items-center gap-2 text-sm">
            <svg className="w-4 h-4 text-neutral-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
            </svg>
            <a
              href={state.sourceUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="truncate text-indigo-600 hover:underline"
            >
              {state.sourceUrl}
            </a>
          </div>
        )}

        {/* Original content (text memos) */}
        {!state.isUrl && state.originalContent && (
          <section className="space-y-1">
            <h2 className="text-xs font-semibold uppercase tracking-wider text-neutral-400">
              Conteúdo original
            </h2>
            <div className="rounded-lg border border-neutral-200 bg-white p-3 text-sm text-neutral-700 max-h-40 overflow-y-auto whitespace-pre-wrap leading-relaxed">
              {state.originalContent}
            </div>
          </section>
        )}

        {/* AI suggested summary — editable */}
        <section className="space-y-1">
          <div className="flex items-center justify-between">
            <h2 className="text-xs font-semibold uppercase tracking-wider text-neutral-400">
              Resumo / texto do memo
            </h2>
            <span
              className={clsx(
                'text-xs tabular-nums',
                overLimit ? 'text-red-500 font-semibold' : 'text-neutral-400',
              )}
            >
              {summary.length}/{MAX_SUMMARY_CHARS}
            </span>
          </div>
          <textarea
            value={summary}
            onChange={(e) => setSummary(e.target.value)}
            rows={6}
            placeholder="Edite ou escreva o resumo do memo…"
            className={clsx(
              'w-full resize-none rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2',
              overLimit
                ? 'border-red-400 focus:ring-red-400'
                : 'border-neutral-200 focus:ring-indigo-500',
            )}
          />
          {overLimit && (
            <p className="text-xs text-red-500">
              Resumo excede {MAX_SUMMARY_CHARS} caracteres.
            </p>
          )}
        </section>

        {/* Keywords — editable */}
        <section className="space-y-1">
          <h2 className="text-xs font-semibold uppercase tracking-wider text-neutral-400">
            Palavras-chave
          </h2>
          <input
            type="text"
            value={keywords}
            onChange={(e) => setKeywords(e.target.value)}
            placeholder="palavra1, palavra2, palavra3"
            className="w-full rounded-lg border border-neutral-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
          <p className="text-xs text-neutral-400">Separe com vírgulas.</p>
        </section>

        {/* Error */}
        {errorMessage && (
          <div className="rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-700">
            {errorMessage}
          </div>
        )}

        {/* Actions */}
        <div className="flex gap-3 pt-2">
          <button
            onClick={handleDiscard}
            disabled={isBusy}
            className="flex-1 rounded-lg border border-neutral-300 px-4 py-2.5 text-sm font-medium text-neutral-700 hover:bg-neutral-50 disabled:opacity-50 transition-colors"
          >
            Descartar
          </button>
          <button
            onClick={handleConfirm}
            disabled={isBusy || overLimit}
            className="flex-[2] rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            {isBusy ? 'Salvando…' : 'Confirmar memo'}
          </button>
        </div>
      </main>
    </div>
  );
}
