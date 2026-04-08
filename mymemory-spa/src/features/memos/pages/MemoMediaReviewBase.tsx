/**
 * Shared layout for all media memo review pages.
 * Each concrete review page provides the header icon/label and a preview section.
 */
import { type ReactNode, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import clsx from 'clsx';

export interface MediaReviewState {
  memoId: number;
  memoType: string;
  originalContent: string | null;
  suggestedSummary: string;
  suggestedKeywords: string;
  aiLevel: string;
  groupId: number | null;
  fileName?: string;
}

export function isMediaReviewState(x: unknown): x is MediaReviewState {
  if (!x || typeof x !== 'object') return false;
  const o = x as Record<string, unknown>;
  return typeof o.memoId === 'number' && typeof o.suggestedSummary === 'string';
}

const MAX_SUMMARY_CHARS = 2000;

interface Props {
  state: MediaReviewState;
  label: string;
  preview: ReactNode;
  isBusy: boolean;
  errorMessage?: string;
  onConfirm: (summary: string, keywords: string[]) => Promise<void>;
}

export function MemoMediaReviewLayout({ state, label, preview, isBusy, errorMessage, onConfirm }: Props) {
  const navigate = useNavigate();
  const [summary, setSummary] = useState(state.suggestedSummary);
  const [keywords, setKeywords] = useState(state.suggestedKeywords);
  const overLimit = summary.length > MAX_SUMMARY_CHARS;

  async function handleConfirm() {
    if (overLimit) return;
    const kws = keywords.split(',').map((k) => k.trim()).filter(Boolean);
    await onConfirm(summary.trim(), kws);
    navigate('/');
  }

  return (
    <div className="min-h-screen bg-neutral-50">
      <header className="border-b border-neutral-200 bg-white px-4 py-4">
        <div className="max-w-2xl mx-auto flex items-center gap-3">
          <button onClick={() => navigate(-1)} className="text-neutral-500 hover:text-neutral-700" aria-label="Voltar">
            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <h1 className="text-lg font-semibold text-neutral-900">Revisar memo — {label}</h1>
        </div>
      </header>

      <main className="max-w-2xl mx-auto px-4 py-6 space-y-6">
        {/* File info */}
        {state.fileName && (
          <div className="flex items-center gap-2 rounded-lg border border-neutral-200 bg-white p-3 text-sm">
            <svg className="w-4 h-4 text-neutral-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
            </svg>
            <span className="truncate text-neutral-700">{state.fileName}</span>
          </div>
        )}

        {/* Media-specific preview */}
        {preview}

        {/* Extracted content (transcription / OCR) */}
        {state.originalContent && (
          <section className="space-y-1">
            <h2 className="text-xs font-semibold uppercase tracking-wider text-neutral-400">
              Conteúdo extraído
            </h2>
            <div className="rounded-lg border border-neutral-200 bg-white p-3 text-sm text-neutral-700 max-h-48 overflow-y-auto whitespace-pre-wrap leading-relaxed">
              {state.originalContent}
            </div>
          </section>
        )}

        {/* Summary — editable */}
        <section className="space-y-1">
          <div className="flex items-center justify-between">
            <h2 className="text-xs font-semibold uppercase tracking-wider text-neutral-400">Resumo</h2>
            <span className={clsx('text-xs tabular-nums', overLimit ? 'text-red-500 font-semibold' : 'text-neutral-400')}>
              {summary.length}/{MAX_SUMMARY_CHARS}
            </span>
          </div>
          <textarea
            value={summary}
            onChange={(e) => setSummary(e.target.value)}
            rows={5}
            placeholder="Edite ou escreva o resumo…"
            className={clsx(
              'w-full resize-none rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2',
              overLimit ? 'border-red-400 focus:ring-red-400' : 'border-neutral-200 focus:ring-indigo-500',
            )}
          />
          {overLimit && <p className="text-xs text-red-500">Resumo excede {MAX_SUMMARY_CHARS} caracteres.</p>}
        </section>

        {/* Keywords — editable */}
        <section className="space-y-1">
          <h2 className="text-xs font-semibold uppercase tracking-wider text-neutral-400">Palavras-chave</h2>
          <input
            type="text"
            value={keywords}
            onChange={(e) => setKeywords(e.target.value)}
            placeholder="palavra1, palavra2, palavra3"
            className="w-full rounded-lg border border-neutral-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
          <p className="text-xs text-neutral-400">Separe com vírgulas.</p>
        </section>

        {errorMessage && (
          <div className="rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-700">
            {errorMessage}
          </div>
        )}

        {/* Actions */}
        <div className="flex gap-3 pt-2">
          <button
            onClick={() => navigate(-1)}
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
