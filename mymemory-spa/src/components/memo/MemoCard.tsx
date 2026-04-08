import { Link } from 'react-router-dom';
import type { Memo } from '../../types/models';
import { getMemoFileUrl } from '../../features/memos/memoService';

const TYPE_ICON: Record<string, string> = {
  text:     '📝',
  url:      '🔗',
  image:    '🖼️',
  audio:    '🎵',
  video:    '🎬',
  document: '📄',
};

const TYPE_LABEL: Record<string, string> = {
  text:     'Texto',
  url:      'URL',
  image:    'Imagem',
  audio:    'Áudio',
  video:    'Vídeo',
  document: 'Documento',
};

function formatDate(iso: string): string {
  try {
    return new Date(iso).toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', year: 'numeric' });
  } catch {
    return iso;
  }
}

/** Wrap highlight_terms occurrences in <mark> within a plain string. */
export function applyHighlight(text: string, terms: string[]): string {
  if (!terms.length) return text;
  const pattern = terms
    .map((t) => t.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'))
    .join('|');
  return text.replace(new RegExp(`(${pattern})`, 'gi'), '<mark>$1</mark>');
}

interface Props {
  memo: Memo;
  highlightTerms?: string[];
  currentUserId?: number | null;
  onDelete?: (id: number) => void;
}

export default function MemoCard({ memo, highlightTerms = [], currentUserId, onDelete }: Props) {
  const isOwner = currentUserId != null && memo.user_id === currentUserId;
  const headline = memo.title ?? memo.summary ?? memo.content ?? '';
  const bodyText  = memo.summary ?? memo.content ?? '';
  const kw: string[] = Array.isArray(memo.keywords) ? memo.keywords : [];

  const headlineHtml = applyHighlight(headline.slice(0, 120), highlightTerms);
  const bodyHtml     = applyHighlight(bodyText.slice(0, 300), highlightTerms);

  return (
    <article className="bg-white border border-gray-200 rounded-xl shadow-sm p-4 flex flex-col gap-3 hover:shadow-md transition-shadow">
      {/* Header */}
      <header className="flex items-start justify-between gap-2">
        <div className="flex items-center gap-2 min-w-0">
          <span className="text-xl shrink-0" title={TYPE_LABEL[memo.type] ?? memo.type}>
            {TYPE_ICON[memo.type] ?? '📋'}
          </span>
          <h3
            className="font-semibold text-gray-800 text-sm leading-tight line-clamp-2 min-w-0"
            dangerouslySetInnerHTML={{ __html: headlineHtml }}
          />
        </div>
        <span className="text-xs text-gray-400 shrink-0">#{memo.id}</span>
      </header>

      {/* Source URL */}
      {memo.source_url && (
        <a
          href={memo.source_url}
          target="_blank"
          rel="noopener noreferrer"
          className="text-xs text-blue-600 truncate hover:underline"
        >
          {memo.source_url}
        </a>
      )}

      {/* Body */}
      {bodyText && (
        <p
          className="text-sm text-gray-600 line-clamp-3 leading-relaxed"
          dangerouslySetInnerHTML={{ __html: bodyHtml + (bodyText.length > 300 ? '…' : '') }}
        />
      )}

      {/* Keywords */}
      {kw.length > 0 && (
        <div className="flex flex-wrap gap-1">
          {kw.slice(0, 6).map((k) => (
            <span key={k} className="px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded-full text-xs">
              {k}
            </span>
          ))}
          {kw.length > 6 && (
            <span className="px-2 py-0.5 bg-gray-100 text-gray-500 rounded-full text-xs">
              +{kw.length - 6}
            </span>
          )}
        </div>
      )}

      {/* File download */}
      {memo.file && (
        <a
          href={getMemoFileUrl(memo.id)}
          className="text-xs text-gray-500 hover:text-indigo-600 inline-flex items-center gap-1"
          target="_blank"
          rel="noopener noreferrer"
        >
          <span>📎</span>
          <span className="truncate">{memo.file.original_filename ?? 'Arquivo'}</span>
        </a>
      )}

      {/* Footer */}
      <footer className="flex items-center justify-between gap-2 mt-auto pt-2 border-t border-gray-100">
        <span className="text-xs text-gray-400">{formatDate(memo.created_at)}</span>

        {isOwner ? (
          <div className="flex items-center gap-2">
            <Link
              to={`/memos/${memo.id}/editar`}
              className="text-xs text-indigo-600 hover:text-indigo-800 font-medium"
            >
              Editar
            </Link>
            <button
              type="button"
              onClick={() => onDelete?.(memo.id)}
              className="text-xs text-red-500 hover:text-red-700 font-medium"
            >
              Excluir
            </button>
          </div>
        ) : (
          memo.user && (
            <span className="text-xs text-gray-400">
              {memo.user.name ?? memo.user.email}
            </span>
          )
        )}
      </footer>
    </article>
  );
}
