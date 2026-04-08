import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import clsx from 'clsx';
import { useMe, useMediaLimits } from '../../features/me/hooks/useMe';
import { useProcessText, useCreateText } from '../../features/memos/hooks/useMemoText';
import { useProcessUrl, useCreateUrl } from '../../features/memos/hooks/useMemoUrl';
import {
  useProcessImage,
  useProcessAudio,
  useProcessVideo,
  useProcessDocument,
} from '../../features/memos/hooks/useMemoMedia';
import FileUploadInput from './FileUploadInput';
import type { Memo, AiLevel } from '../../types/models';

type InputMode = 'text' | 'url' | 'image' | 'audio' | 'video' | 'document';

const MODE_LABELS: Record<InputMode, string> = {
  text: 'Texto',
  url: 'URL',
  image: 'Imagem',
  audio: 'Áudio',
  video: 'Vídeo',
  document: 'Documento',
};

const MEDIA_MODES: InputMode[] = ['image', 'audio', 'video', 'document'];

const ACCEPT: Record<InputMode, string> = {
  text: '',
  url: '',
  image: 'image/jpeg,image/jpg,image/png,image/gif,image/webp',
  audio: 'audio/mpeg,audio/wav,audio/ogg,audio/opus,audio/webm,audio/m4a,audio/flac,audio/aac',
  video: 'video/mp4,video/avi,video/quicktime,video/x-ms-wmv,video/x-flv,video/x-matroska,video/webm',
  document: 'application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,message/rfc822,text/plain',
};

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
  const { data: mediaLimits } = useMediaLimits();

  const [mode, setMode] = useState<InputMode>('text');
  const [content, setContent] = useState('');
  const [url, setUrl] = useState('');
  const [file, setFile] = useState<File | null>(null);
  const [uploadProgress, setUploadProgress] = useState(0);
  const [aiLevel, setAiLevel] = useState<AiLevel>('full');
  const [skipReview, setSkipReview] = useState(false);

  const processText   = useProcessText();
  const createText    = useCreateText();
  const processUrl    = useProcessUrl();
  const createUrl     = useCreateUrl();
  const processImage  = useProcessImage();
  const processAudio  = useProcessAudio();
  const processVideo  = useProcessVideo();
  const processDoc    = useProcessDocument();

  const isBusy =
    processText.isPending || createText.isPending ||
    processUrl.isPending  || createUrl.isPending  ||
    processImage.isPending || processAudio.isPending ||
    processVideo.isPending || processDoc.isPending;

  const errorMessage =
    processText.error?.message ?? createText.error?.message ??
    processUrl.error?.message  ?? createUrl.error?.message  ??
    processImage.error?.message ?? processAudio.error?.message ??
    processVideo.error?.message ?? processDoc.error?.message;

  const confirmBeforeProcessing = me?.preferences?.confirm_before_processing ?? true;
  const goDirectly = skipReview || !confirmBeforeProcessing;
  const isMediaMode = MEDIA_MODES.includes(mode);

  function maxSizeMbForMode(): number | undefined {
    if (!mediaLimits || !isMediaMode) return undefined;
    const key = mode as 'image' | 'audio' | 'video' | 'document';
    return mediaLimits[key]?.max_file_size_mb ?? undefined;
  }

  function navToReview(draft: Memo, mediaFile?: File) {
    const state = {
      memoId: draft.id,
      memoType: draft.type,
      originalContent: draft.content,
      suggestedSummary: draft.summary ?? '',
      suggestedKeywords: draft.keywords?.join(', ') ?? '',
      aiLevel,
      groupId,
      fileName: mediaFile?.name,
    };

    const paths: Record<InputMode, string> = {
      text:     '/memos/text/review',
      url:      '/memos/text/review',
      image:    '/memos/image/review',
      audio:    '/memos/audio/review',
      video:    '/memos/video/review',
      document: '/memos/document/review',
    };

    navigate(paths[mode], { state });
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setUploadProgress(0);

    if (mode === 'text') {
      if (!content.trim()) return;
      if (goDirectly) {
        const memo = await createText.mutateAsync({ content: content.trim(), ai_level: aiLevel, group_id: groupId });
        setContent('');
        onRegistered?.(memo);
      } else {
        const draft = await processText.mutateAsync({ content: content.trim(), ai_level: aiLevel, group_id: groupId });
        navToReview(draft);
      }
    } else if (mode === 'url') {
      if (!url.trim()) return;
      if (goDirectly) {
        const memo = await createUrl.mutateAsync({ url: url.trim(), ai_level: aiLevel, group_id: groupId });
        setUrl('');
        onRegistered?.(memo);
      } else {
        const draft = await processUrl.mutateAsync({ url: url.trim(), ai_level: aiLevel, group_id: groupId });
        navToReview(draft);
      }
    } else {
      // Media upload modes
      if (!file) return;
      const progressCb = (pct: number) => setUploadProgress(pct);
      const payload = { file, aiLevel, groupId, onUploadProgress: progressCb };

      let draft: Memo;
      if (mode === 'image')    draft = await processImage.mutateAsync(payload);
      else if (mode === 'audio')   draft = await processAudio.mutateAsync(payload);
      else if (mode === 'video')   draft = await processVideo.mutateAsync(payload);
      else                         draft = await processDoc.mutateAsync(payload);

      navToReview(draft, file);
    }
  }

  const canSubmit = mode === 'text'
    ? content.trim().length > 0
    : mode === 'url'
    ? url.trim().length > 0
    : file !== null;

  return (
    <div className="rounded-2xl border border-neutral-200 bg-white shadow-sm">
      {/* Tab bar */}
      <div className="flex flex-wrap border-b border-neutral-200">
        {(Object.keys(MODE_LABELS) as InputMode[]).map((m) => (
          <button
            key={m}
            type="button"
            onClick={() => { setMode(m); setFile(null); }}
            className={clsx(
              'flex-1 min-w-[4rem] px-3 py-2.5 text-xs font-medium transition-colors',
              mode === m
                ? 'border-b-2 border-indigo-600 text-indigo-600'
                : 'text-neutral-500 hover:text-neutral-700',
            )}
          >
            {MODE_LABELS[m]}
          </button>
        ))}
      </div>

      <form onSubmit={handleSubmit} className="p-4 space-y-4">
        {/* Input area */}
        {mode === 'text' && (
          <textarea
            value={content}
            onChange={(e) => setContent(e.target.value)}
            placeholder="Cole ou escreva o texto que deseja memorizar…"
            rows={5}
            className="w-full resize-none rounded-lg border border-neutral-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
            disabled={isBusy}
          />
        )}

        {mode === 'url' && (
          <input
            type="url"
            value={url}
            onChange={(e) => setUrl(e.target.value)}
            placeholder="https://exemplo.com/artigo"
            className="w-full rounded-lg border border-neutral-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
            disabled={isBusy}
          />
        )}

        {isMediaMode && (
          <FileUploadInput
            accept={ACCEPT[mode]}
            maxSizeMb={maxSizeMbForMode()}
            onChange={setFile}
            label={`Selecione um arquivo de ${MODE_LABELS[mode].toLowerCase()}`}
            disabled={isBusy}
          />
        )}

        {/* Upload progress bar */}
        {isBusy && isMediaMode && uploadProgress > 0 && (
          <div className="space-y-1">
            <div className="h-1.5 rounded-full bg-neutral-100 overflow-hidden">
              <div
                className="h-full bg-indigo-500 transition-all duration-200"
                style={{ width: `${uploadProgress}%` }}
              />
            </div>
            <p className="text-xs text-neutral-400 text-right">{uploadProgress}%</p>
          </div>
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

        {/* Skip review toggle (not applicable for media — always goes to review) */}
        {!isMediaMode && (
          <label className="flex items-center gap-2 cursor-pointer select-none">
            <input
              type="checkbox"
              checked={skipReview}
              onChange={(e) => setSkipReview(e.target.checked)}
              className="rounded border-neutral-300 text-indigo-600 focus:ring-indigo-500"
            />
            <span className="text-xs text-neutral-600">Salvar sem revisão</span>
          </label>
        )}

        {/* Error */}
        {errorMessage && <p className="text-xs text-red-600">{errorMessage}</p>}

        {/* Submit */}
        <button
          type="submit"
          disabled={isBusy || !canSubmit}
          className="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          {isBusy
            ? isMediaMode ? 'Enviando…' : 'Processando…'
            : isMediaMode || !goDirectly
            ? 'Processar com IA'
            : 'Salvar memo'}
        </button>
      </form>
    </div>
  );
}
