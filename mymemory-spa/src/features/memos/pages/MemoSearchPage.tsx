import { useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import MemoCard, { applyHighlight } from '../../../components/memo/MemoCard';
import { useMe } from '../../me/hooks/useMe';
import { useDeleteMemo, useFetchSynonyms, useSearchAuthors, useSearchMemos } from '../hooks/useMemos';
import type { Memo } from '../../../types/models';
import type { SearchMemosPayload } from '../../../types/api';

function formatDateInput(iso: string): string {
  return iso.split('T')[0] ?? iso;
}

export default function MemoSearchPage() {
  const { data: me } = useMe();
  const groupId = me?.workspace?.id ?? null;

  // Form state
  const [query, setQuery] = useState('');
  const [operator, setOperator] = useState<'AND' | 'OR'>('AND');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [authorId, setAuthorId] = useState<number | null>(null);

  // Results
  const [results, setResults] = useState<Memo[]>([]);
  const [total, setTotal]     = useState(0);
  const [highlightTerms, setHighlightTerms] = useState<string[]>([]);
  const [searched, setSearched] = useState(false);
  const [page, setPage] = useState(1);

  // Synonyms
  const [synonyms, setSynonyms] = useState<string[]>([]);

  // Delete dialog
  const [confirmDeleteId, setConfirmDeleteId] = useState<number | null>(null);

  const { data: authors } = useSearchAuthors(groupId);
  const { mutate: doSearch, isPending: searching } = useSearchMemos();
  const { mutate: doSynonyms, isPending: loadingSynonyms } = useFetchSynonyms();
  const { mutate: doDelete, isPending: deleting } = useDeleteMemo();

  const PER_PAGE = 20;

  function runSearch(p = 1) {
    if (!query.trim()) return;

    const payload: SearchMemosPayload = {
      query:     query.trim(),
      operator,
      page:      p,
      per_page:  PER_PAGE,
      group_id:  groupId,
    };
    if (dateFrom) payload.date_from = dateFrom;
    if (dateTo)   payload.date_to   = dateTo;
    if (authorId) payload.author_id = authorId;

    doSearch(payload, {
      onSuccess: (res) => {
        setResults(res.items);
        setTotal(res.total);
        setHighlightTerms(res.highlight_terms);
        setPage(p);
        setSearched(true);
      },
    });
  }

  function expandSynonyms() {
    if (!query.trim()) return;
    doSynonyms(query.trim(), {
      onSuccess: (list) => setSynonyms(list),
    });
  }

  function applySynonym(term: string) {
    setQuery((q) => (q.trim() ? `${q.trim()} ${term}` : term));
    setSynonyms([]);
  }

  function confirmDelete() {
    if (!confirmDeleteId) return;
    doDelete(confirmDeleteId, {
      onSuccess: () => {
        setConfirmDeleteId(null);
        setResults((prev) => prev.filter((m) => m.id !== confirmDeleteId));
      },
    });
  }

  const totalPages = Math.ceil(total / PER_PAGE);

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-white border-b border-gray-200 px-6 py-4 flex items-center gap-4">
        <Link to="/" className="text-gray-500 hover:text-indigo-600 text-sm">← Home</Link>
        <h1 className="text-xl font-bold text-gray-900">Buscar memos</h1>
      </header>

      <main className="max-w-4xl mx-auto px-4 py-8 flex flex-col gap-6">
        {/* Search form */}
        <section className="bg-white rounded-xl border border-gray-200 p-5 shadow-sm flex flex-col gap-4">
          {/* Query + operator */}
          <div className="flex gap-2">
            <input
              type="search"
              value={query}
              onChange={(e) => setQuery(e.target.value)}
              onKeyDown={(e) => e.key === 'Enter' && runSearch(1)}
              placeholder="Pesquisar memos…"
              className="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
            />
            <select
              value={operator}
              onChange={(e) => setOperator(e.target.value as 'AND' | 'OR')}
              className="border border-gray-200 rounded-lg px-2 py-2 text-sm bg-white"
            >
              <option value="AND">AND</option>
              <option value="OR">OR</option>
            </select>
            <button
              type="button"
              onClick={() => runSearch(1)}
              disabled={searching || !query.trim()}
              className="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 disabled:opacity-50"
            >
              {searching ? 'Buscando…' : 'Buscar'}
            </button>
          </div>

          {/* Synonyms */}
          <div className="flex items-center gap-2 flex-wrap">
            <button
              type="button"
              onClick={expandSynonyms}
              disabled={loadingSynonyms || !query.trim()}
              className="text-xs text-indigo-600 hover:text-indigo-800 disabled:opacity-50"
            >
              {loadingSynonyms ? 'Expandindo…' : '✨ Expandir com sinônimos'}
            </button>
            {synonyms.map((s) => (
              <button
                key={s}
                type="button"
                onClick={() => applySynonym(s)}
                className="px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded-full text-xs hover:bg-indigo-100"
              >
                + {s}
              </button>
            ))}
          </div>

          {/* Filters */}
          <details className="text-sm">
            <summary className="cursor-pointer text-gray-500 hover:text-gray-700 select-none">
              Filtros avançados
            </summary>
            <div className="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div className="flex flex-col gap-1">
                <label className="text-xs text-gray-500">De</label>
                <input
                  type="date"
                  value={dateFrom}
                  onChange={(e) => setDateFrom(e.target.value)}
                  className="border border-gray-200 rounded-lg px-2 py-1.5 text-sm"
                />
              </div>
              <div className="flex flex-col gap-1">
                <label className="text-xs text-gray-500">Até</label>
                <input
                  type="date"
                  value={dateTo}
                  onChange={(e) => setDateTo(e.target.value)}
                  className="border border-gray-200 rounded-lg px-2 py-1.5 text-sm"
                />
              </div>
              <div className="flex flex-col gap-1">
                <label className="text-xs text-gray-500">Autor</label>
                <select
                  value={authorId ?? ''}
                  onChange={(e) => setAuthorId(e.target.value ? Number(e.target.value) : null)}
                  className="border border-gray-200 rounded-lg px-2 py-1.5 text-sm bg-white"
                >
                  <option value="">Todos</option>
                  {(authors ?? []).map((a) => (
                    <option key={a.id} value={a.id}>
                      {a.name ?? a.email}
                    </option>
                  ))}
                </select>
              </div>
            </div>
          </details>
        </section>

        {/* Results */}
        {searched && (
          <section>
            <p className="text-sm text-gray-500 mb-3">
              {total === 0
                ? 'Nenhum resultado encontrado.'
                : `${total} resultado${total !== 1 ? 's' : ''} encontrado${total !== 1 ? 's' : ''}.`}
            </p>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              {results.map((m) => (
                <MemoCard
                  key={m.id}
                  memo={m}
                  highlightTerms={highlightTerms}
                  currentUserId={me?.user?.id}
                  onDelete={setConfirmDeleteId}
                />
              ))}
            </div>

            {/* Pagination */}
            {totalPages > 1 && (
              <div className="flex justify-center gap-2 mt-6">
                <button
                  type="button"
                  disabled={page <= 1 || searching}
                  onClick={() => runSearch(page - 1)}
                  className="px-3 py-1.5 text-sm border rounded-lg disabled:opacity-40"
                >
                  ← Anterior
                </button>
                <span className="px-3 py-1.5 text-sm text-gray-600">
                  Página {page} de {totalPages}
                </span>
                <button
                  type="button"
                  disabled={page >= totalPages || searching}
                  onClick={() => runSearch(page + 1)}
                  className="px-3 py-1.5 text-sm border rounded-lg disabled:opacity-40"
                >
                  Próxima →
                </button>
              </div>
            )}
          </section>
        )}
      </main>

      {/* Delete dialog */}
      {confirmDeleteId !== null && (
        <div
          className="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
          role="presentation"
          onClick={(e) => { if (e.target === e.currentTarget && !deleting) setConfirmDeleteId(null); }}
        >
          <div className="bg-white rounded-xl shadow-xl p-6 max-w-sm w-full mx-4" role="dialog" aria-modal="true">
            <h3 className="text-base font-semibold text-gray-900 mb-2">Mover para lixeira?</h3>
            <p className="text-sm text-gray-500 mb-5">O memo será excluído logicamente.</p>
            <div className="flex gap-3 justify-end">
              <button
                type="button"
                disabled={deleting}
                onClick={() => setConfirmDeleteId(null)}
                className="px-4 py-2 text-sm rounded-lg border border-gray-200 hover:bg-gray-50"
              >
                Cancelar
              </button>
              <button
                type="button"
                disabled={deleting}
                onClick={confirmDelete}
                className="px-4 py-2 text-sm rounded-lg bg-red-600 text-white hover:bg-red-700 disabled:opacity-50"
              >
                {deleting ? 'Excluindo…' : 'Confirmar'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
