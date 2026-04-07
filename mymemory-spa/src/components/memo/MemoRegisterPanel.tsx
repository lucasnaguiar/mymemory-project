import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import clsx from 'clsx';
import { useMe } from '../../features/me/hooks/useMe';
import { useProcessText, useCreateText } from '../../features/memos/hooks/useMemoText';
import { useProcessUrl, useCreateUrl } from '../../features/memos/hooks/useMemoUrl';
import type { Memo } from '../../types/models';
import type { AiLevel } from '../../types/models';

type InputMode = 'text' | 'url';

const AI_LEVEL_LABELS: Record<AiLevel, string> = {
  none: 'Sem IA',
  basic: 'Básico',
  full: 'Completo',
};

interface Props {
  onRegistered?: (memo: Memo) => void;
  groupId?: number | null;
}

export default function MemoRegisterPanel({ onRegistered, groupId = null }: Props) {
  const navigate = useNavigate();
  const { data: me } = useMe();

  const [mode, setMode] = useState<InputMode>('text');
  const [content, setContent] = useState('');
  const [url, setUrl] = useState('');
  const [aiLevel, setAiLevel] = useState<AiLevel>(() => {
    return me?.preferences?.ai_level_text ?? 'full';
  });
  const [skipReview, setSkipReview] = useState(false);

  const processText = useProcessText();
  const createText = useCreateText();
  const processUrl = useProcessUrl();
  const createUrl = useCreateUrl();

  const isBusy =
    processText.isPending ||
    createText.isPending ||
    processUrl.isPending ||
    createUrl.isPending;

  const errorMessage =
    processText.error?.message ??
    createText.error?.message ??
    processUrl.error?.message ??
    createUrl.error?.message;

  const confirmBeforeProcessing = me?.preferences?.confirm_before_processing ?? true;
  const goDirectly = skipReview || !confirmBeforeProcessing;

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();

    if (mode === 'text') {
      if (!content.trim()) return;

      if (goDirectly) {
        const memo = await createText.mutateAsync({ content: content.trim(), ai_level: aiLevel, group_id: groupId });
        setContent('');
        onRegistered?.(memo);
      } else {
        const draft = await processText.mutateAsync({ content: content.trim(), ai_level: aiLevel, group_id: groupId });
        navigate('/memos/text/review', {
          state: {
            memoId: draft.id,
            originalContent: draft.content,
            suggestedSummary: draft.summary ?? '',
            suggestedKeywords: draft.keywords?.join(', ') ?? '',
            aiLevel,
            groupId,
          },
        });
      }
    } else {
      if (!url.trim()) return;

      if (goDirectly) {
        const memo = await createUrl.mutateAsync({ url: url.trim(), ai_level: aiLevel, group_id: groupId });
        setUrl('');
        onRegistered?.(memo);
      } else {
        const draft = await processUrl.mutateAsync({ url: url.trim(), ai_level: aiLevel, group_id: groupId });
        navigate('/memos/text/review', {
          state: {
            memoId: draft.id,
            originalContent: draft.content,
            sourceUrl: draft.source_url,
            suggestedSummary: draft.summary ?? '',
            suggestedKeywords: draft.keywords?.join(', ') ?? '',
            aiLevel,
            groupId,
            isUrl: true,
          },
        });
      }
    }
  }

  return (
    <div className="rounded-2xl border border-neutral-200 bg-white shadow-sm">
      {/* Tab bar */}
      <div className="flex border-b border-neutral-200">
        {(['text', 'url'] as InputMode[]).map((m) => (
          <button
            key={m}
            type="button"
            onClick={() => setMode(m)}
            className={clsx(
              'flex-1 px-4 py-3 text-sm font-medium transition-colors',
              mode === m
                ? 'border-b-2 border-indigo-600 text-indigo-600'
                : 'text-neutral-500 hover:text-neutral-700',
            )}
          >
            {m === 'text' ? 'Texto' : 'URL'}
          </button>
        ))}
      </div>

      <form onSubmit={handleSubmit} className="p-4 space-y-4">
        {/* Input area */}
        {mode === 'text' ? (
          <textarea
            value={content}
            onChange={(e) => setContent(e.target.value)}
            placeholder="Cole ou escreva o texto que deseja memorizar…"
            rows={5}
            className="w-full resize-none rounded-lg border border-neutral-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
            disabled={isBusy}
          />
        ) : (
          <input
            type="url"
            value={url}
            onChange={(e) => setUrl(e.target.value)}
            placeholder="https://exemplo.com/artigo"
            className="w-full rounded-lg border border-neutral-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
            disabled={isBusy}
          />
        )}

        {/* AI level selector */}
        <div className="flex items-center gap-2 flex-wrap">
          <span className="text-xs text-neutral-500 shrink-0">Nível de IA:</span>
          {(Object.keys(AI_LEVEL_LABELS) as AiLevel[]).map((lvl) => (
            <button
              key={lvl}
              type="button"
              onClick={() => setAiLevel(lvl)}
              className={clsx(
                'rounded-full px-3 py-1 text-xs font-medium border transition-colors',
                aiLevel === lvl
                  ? 'border-indigo-600 bg-indigo-50 text-indigo-700'
                  : 'border-neutral-200 text-neutral-600 hover:border-neutral-400',
              )}
            >
              {AI_LEVEL_LABELS[lvl]}
            </button>
          ))}
        </div>

        {/* Skip review toggle */}
        <label className="flex items-center gap-2 cursor-pointer select-none">
          <input
            type="checkbox"
            checked={skipReview}
            onChange={(e) => setSkipReview(e.target.checked)}
            className="rounded border-neutral-300 text-indigo-600 focus:ring-indigo-500"
          />
          <span className="text-xs text-neutral-600">Salvar sem revisão</span>
        </label>

        {/* Error */}
        {errorMessage && (
          <p className="text-xs text-red-600">{errorMessage}</p>
        )}

        {/* Submit */}
        <button
          type="submit"
          disabled={isBusy || (mode === 'text' ? !content.trim() : !url.trim())}
          className="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          {isBusy ? 'Processando…' : goDirectly ? 'Salvar memo' : 'Processar com IA'}
        </button>
      </form>
    </div>
  );
}
