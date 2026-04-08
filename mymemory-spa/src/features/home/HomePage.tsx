import { useState } from 'react';
import { Link } from 'react-router-dom';
import MemoCard from '../../components/memo/MemoCard';
import MemoRegisterPanel from '../../components/memo/MemoRegisterPanel';
import { useDeleteMemo, useRecentMemos } from '../memos/hooks/useMemos';
import { useMe } from '../me/hooks/useMe';

export default function HomePage() {
  const [confirmDeleteId, setConfirmDeleteId] = useState<number | null>(null);
  const { data: me } = useMe();
  const groupId = me?.workspace?.id ?? null;

  const { data, isLoading, refetch } = useRecentMemos({ limit: 12, group_id: groupId });
  const { mutate: doDelete, isPending: deleting } = useDeleteMemo();

  function handleMemoCreated() {
    void refetch();
  }

  function handleDeleteRequest(id: number) {
    setConfirmDeleteId(id);
  }

  function confirmDelete() {
    if (!confirmDeleteId) return;
    doDelete(confirmDeleteId, { onSuccess: () => { setConfirmDeleteId(null); void refetch(); } });
  }

  const memos = data?.items ?? [];

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
        <h1 className="text-xl font-bold text-gray-900">MyMemory</h1>
        <nav className="flex items-center gap-4 text-sm">
          <Link to="/buscar" className="text-gray-600 hover:text-indigo-600">Buscar</Link>
          <Link to="/preferences" className="text-gray-600 hover:text-indigo-600">Preferências</Link>
        </nav>
      </header>

      <main className="max-w-5xl mx-auto px-4 py-8 flex flex-col gap-10">
        {/* Register panel */}
        <section>
          <MemoRegisterPanel onMemoCreated={handleMemoCreated} />
        </section>

        {/* Recent memos */}
        <section>
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-lg font-semibold text-gray-800">Memos recentes</h2>
            <Link to="/buscar" className="text-sm text-indigo-600 hover:text-indigo-800">
              Ver todos →
            </Link>
          </div>

          {isLoading && (
            <p className="text-sm text-gray-400">Carregando…</p>
          )}

          {!isLoading && memos.length === 0 && (
            <p className="text-sm text-gray-400">Nenhum memo ainda. Registre o primeiro acima.</p>
          )}

          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            {memos.map((m) => (
              <MemoCard
                key={m.id}
                memo={m}
                currentUserId={me?.user?.id}
                onDelete={handleDeleteRequest}
              />
            ))}
          </div>
        </section>
      </main>

      {/* Delete confirmation dialog */}
      {confirmDeleteId !== null && (
        <div
          className="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
          role="presentation"
          onClick={(e) => { if (e.target === e.currentTarget && !deleting) setConfirmDeleteId(null); }}
        >
          <div className="bg-white rounded-xl shadow-xl p-6 max-w-sm w-full mx-4" role="dialog" aria-modal="true">
            <h3 className="text-base font-semibold text-gray-900 mb-2">Mover para lixeira?</h3>
            <p className="text-sm text-gray-500 mb-5">
              O memo será excluído logicamente e ficará inativo. Esta ação pode ser revertida pelo administrador.
            </p>
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
