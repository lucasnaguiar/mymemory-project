import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { useMemoDetail, useUpdateMemo } from '../hooks/useMemos';
import { getMemoFileUrl } from '../memoService';

const TYPE_LABEL: Record<string, string> = {
  text: 'Texto', url: 'URL', image: 'Imagem', audio: 'Áudio', video: 'Vídeo', document: 'Documento',
};

export default function MemoEditPage() {
  const { id: idParam } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const memoId = Number(idParam);

  const { data: memo, isLoading, isError } = useMemoDetail(memoId);
  const { mutate: doUpdate, isPending: saving, error: saveError } = useUpdateMemo();

  const [title,    setTitle]    = useState('');
  const [summary,  setSummary]  = useState('');
  const [keywords, setKeywords] = useState('');
  const [content,  setContent]  = useState('');

  useEffect(() => {
    if (!memo) return;
    setTitle(memo.title ?? '');
    setSummary(memo.summary ?? '');
    setKeywords(Array.isArray(memo.keywords) ? memo.keywords.join(', ') : '');
    setContent(memo.content ?? '');
  }, [memo]);

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    const kwList = keywords
      .split(',')
      .map((k) => k.trim())
      .filter(Boolean);

    doUpdate(
      {
        id: memoId,
        payload: {
          title:    title.trim() || undefined,
          summary:  summary.trim() || undefined,
          keywords: kwList.length ? kwList : undefined,
          content:  memo?.type === 'text' ? content.trim() || undefined : undefined,
        },
      },
      { onSuccess: () => navigate('/') },
    );
  }

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center text-gray-400 text-sm">
        Carregando memo…
      </div>
    );
  }

  if (isError || !memo) {
    return (
      <div className="min-h-screen flex flex-col items-center justify-center gap-4">
        <p className="text-sm text-red-600">Memo não encontrado ou sem permissão.</p>
        <Link to="/" className="text-sm text-indigo-600 hover:underline">← Voltar</Link>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-white border-b border-gray-200 px-6 py-4 flex items-center gap-4">
        <Link to="/" className="text-gray-500 hover:text-indigo-600 text-sm">← Home</Link>
        <h1 className="text-xl font-bold text-gray-900">
          Editar memo — {TYPE_LABEL[memo.type] ?? memo.type}
        </h1>
      </header>

      <main className="max-w-2xl mx-auto px-4 py-8">
        <form onSubmit={handleSubmit} className="bg-white rounded-xl border border-gray-200 shadow-sm p-6 flex flex-col gap-5">
          {/* Title */}
          <div className="flex flex-col gap-1">
            <label className="text-sm font-medium text-gray-700" htmlFor="edit-title">Título</label>
            <input
              id="edit-title"
              type="text"
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              placeholder="Título opcional"
              maxLength={255}
              className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
            />
          </div>

          {/* Summary */}
          <div className="flex flex-col gap-1">
            <label className="text-sm font-medium text-gray-700" htmlFor="edit-summary">Resumo</label>
            <textarea
              id="edit-summary"
              value={summary}
              onChange={(e) => setSummary(e.target.value)}
              rows={3}
              maxLength={2000}
              className="border border-gray-200 rounded-lg px-3 py-2 text-sm resize-y focus:outline-none focus:ring-2 focus:ring-indigo-400"
            />
          </div>

          {/* Keywords */}
          <div className="flex flex-col gap-1">
            <label className="text-sm font-medium text-gray-700" htmlFor="edit-keywords">
              Palavras-chave <span className="text-gray-400 font-normal">(separadas por vírgula)</span>
            </label>
            <input
              id="edit-keywords"
              type="text"
              value={keywords}
              onChange={(e) => setKeywords(e.target.value)}
              placeholder="ex: react, frontend, typescript"
              className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
            />
          </div>

          {/* Content — only for text memos */}
          {memo.type === 'text' && (
            <div className="flex flex-col gap-1">
              <label className="text-sm font-medium text-gray-700" htmlFor="edit-content">Conteúdo</label>
              <textarea
                id="edit-content"
                value={content}
                onChange={(e) => setContent(e.target.value)}
                rows={8}
                className="border border-gray-200 rounded-lg px-3 py-2 text-sm resize-y font-mono text-xs focus:outline-none focus:ring-2 focus:ring-indigo-400"
              />
            </div>
          )}

          {/* File info for media memos */}
          {memo.file && (
            <div className="bg-gray-50 border border-gray-100 rounded-lg p-3 flex items-center justify-between">
              <div className="text-xs text-gray-600">
                <p className="font-medium">{memo.file.original_filename}</p>
                <p className="text-gray-400">{memo.file.mime_type}</p>
              </div>
              <a
                href={getMemoFileUrl(memo.id)}
                target="_blank"
                rel="noopener noreferrer"
                className="text-xs text-indigo-600 hover:underline"
              >
                Download
              </a>
            </div>
          )}

          {/* Source URL info */}
          {memo.source_url && (
            <div className="text-xs text-gray-500">
              URL: <a href={memo.source_url} target="_blank" rel="noopener noreferrer" className="text-blue-600 hover:underline">{memo.source_url}</a>
            </div>
          )}

          {saveError && (
            <p className="text-sm text-red-600">{saveError.message}</p>
          )}

          <div className="flex gap-3 pt-2 border-t border-gray-100">
            <button
              type="submit"
              disabled={saving}
              className="flex-1 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 disabled:opacity-50"
            >
              {saving ? 'Salvando…' : 'Salvar alterações'}
            </button>
            <Link
              to="/"
              className="px-4 py-2 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 text-gray-600 text-center"
            >
              Cancelar
            </Link>
          </div>
        </form>
      </main>
    </div>
  );
}
