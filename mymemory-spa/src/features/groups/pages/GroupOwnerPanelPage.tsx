import { useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { useCreateInvite, useOwnerPanel } from '../hooks/useGroups';
import type { GroupMemberEntry, GroupInviteEntry } from '../groupService';

const ROLE_LABELS: Record<string, string> = { editor: 'Editor', viewer: 'Viewer', owner: 'Dono' };
const STATUS_LABELS: Record<string, string> = { pending: 'Pendente', accepted: 'Aceito', expired: 'Expirado' };
const STATUS_COLORS: Record<string, string> = {
  pending: 'bg-yellow-50 text-yellow-700',
  accepted: 'bg-green-50 text-green-700',
  expired: 'bg-gray-100 text-gray-500',
};

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', year: 'numeric' });
}

function MemberRow({ m }: { m: GroupMemberEntry }) {
  return (
    <div className="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
      <div>
        <p className="text-sm font-medium text-gray-800">{m.name ?? m.email}</p>
        {m.name && <p className="text-xs text-gray-400">{m.email}</p>}
      </div>
      <span className="text-xs px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded-full">
        {ROLE_LABELS[m.role] ?? m.role}
      </span>
    </div>
  );
}

function InviteRow({ inv }: { inv: GroupInviteEntry }) {
  return (
    <div className="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
      <div>
        <p className="text-sm text-gray-700">{inv.email}</p>
        <p className="text-xs text-gray-400">{ROLE_LABELS[inv.role] ?? inv.role} · enviado {formatDate(inv.created_at)}</p>
      </div>
      <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${STATUS_COLORS[inv.status] ?? 'bg-gray-100 text-gray-500'}`}>
        {STATUS_LABELS[inv.status] ?? inv.status}
      </span>
    </div>
  );
}

export default function GroupOwnerPanelPage() {
  const { id: idParam } = useParams<{ id: string }>();
  const groupId = Number(idParam);
  const [activeTab, setActiveTab] = useState<'overview' | 'invites'>('overview');

  // Invite form
  const [email, setEmail] = useState('');
  const [role, setRole]   = useState<'editor' | 'viewer'>('viewer');
  const [inviteOk, setInviteOk] = useState<string | null>(null);

  const { data: panel, isLoading, isError } = useOwnerPanel(groupId);
  const { mutate: doInvite, isPending: inviting, error: inviteError } = useCreateInvite(groupId);

  function handleInvite(e: React.FormEvent) {
    e.preventDefault();
    setInviteOk(null);
    doInvite(
      { email: email.trim(), role },
      {
        onSuccess: () => {
          setInviteOk(`Convite enviado para ${email.trim()}.`);
          setEmail('');
        },
      },
    );
  }

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center text-gray-400 text-sm">
        Carregando painel…
      </div>
    );
  }

  if (isError || !panel) {
    return (
      <div className="min-h-screen flex flex-col items-center justify-center gap-4">
        <p className="text-sm text-red-600">Grupo não encontrado ou sem permissão.</p>
        <Link to="/" className="text-sm text-indigo-600 hover:underline">← Home</Link>
      </div>
    );
  }

  const { group, members, invites, memo_count } = panel;
  const pendingInvites = invites.filter((i) => i.status === 'pending');

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-white border-b border-gray-200 px-6 py-4 flex items-center gap-4">
        <Link to="/" className="text-gray-500 hover:text-indigo-600 text-sm">← Home</Link>
        <h1 className="text-xl font-bold text-gray-900">Painel do grupo — {group.name}</h1>
      </header>

      <main className="max-w-3xl mx-auto px-4 py-8 flex flex-col gap-6">
        {/* Stats */}
        <div className="grid grid-cols-3 gap-4">
          {[
            { label: 'Memos', value: memo_count },
            { label: 'Membros', value: members.length },
            { label: 'Convites pendentes', value: pendingInvites.length },
          ].map(({ label, value }) => (
            <div key={label} className="bg-white rounded-xl border border-gray-200 p-4 text-center shadow-sm">
              <p className="text-2xl font-bold text-indigo-600">{value}</p>
              <p className="text-xs text-gray-500 mt-1">{label}</p>
            </div>
          ))}
        </div>

        {/* Tabs */}
        <div className="flex border-b border-gray-200">
          {(['overview', 'invites'] as const).map((tab) => (
            <button
              key={tab}
              type="button"
              onClick={() => setActiveTab(tab)}
              className={`px-5 py-2.5 text-sm font-medium border-b-2 transition-colors ${
                activeTab === tab
                  ? 'border-indigo-600 text-indigo-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700'
              }`}
            >
              {tab === 'overview' ? 'Membros' : 'Convites'}
            </button>
          ))}
        </div>

        {/* Overview tab — member list */}
        {activeTab === 'overview' && (
          <section className="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex flex-col gap-1">
            <h2 className="text-sm font-semibold text-gray-700 mb-2">Membros ({members.length})</h2>
            {members.length === 0 ? (
              <p className="text-sm text-gray-400">Nenhum membro ainda. Convide alguém na aba Convites.</p>
            ) : (
              members.map((m) => <MemberRow key={m.id} m={m} />)
            )}
          </section>
        )}

        {/* Invites tab */}
        {activeTab === 'invites' && (
          <div className="flex flex-col gap-5">
            {/* Invite form */}
            <section className="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
              <h2 className="text-sm font-semibold text-gray-700 mb-3">Convidar membro</h2>
              <form onSubmit={handleInvite} className="flex flex-col gap-3">
                <div className="flex gap-2">
                  <input
                    type="email"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    placeholder="email@exemplo.com"
                    required
                    className="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                  />
                  <select
                    value={role}
                    onChange={(e) => setRole(e.target.value as 'editor' | 'viewer')}
                    className="border border-gray-200 rounded-lg px-2 py-2 text-sm bg-white"
                  >
                    <option value="editor">Editor</option>
                    <option value="viewer">Viewer</option>
                  </select>
                  <button
                    type="submit"
                    disabled={inviting || !email.trim()}
                    className="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                  >
                    {inviting ? 'Enviando…' : 'Convidar'}
                  </button>
                </div>
                {inviteError && (
                  <p className="text-xs text-red-600">{inviteError.message}</p>
                )}
                {inviteOk && (
                  <p className="text-xs text-green-600">{inviteOk}</p>
                )}
              </form>
            </section>

            {/* Invite list */}
            <section className="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex flex-col gap-1">
              <h2 className="text-sm font-semibold text-gray-700 mb-2">
                Histórico de convites ({invites.length})
              </h2>
              {invites.length === 0 ? (
                <p className="text-sm text-gray-400">Nenhum convite enviado ainda.</p>
              ) : (
                invites.map((inv) => <InviteRow key={inv.id} inv={inv} />)
              )}
            </section>
          </div>
        )}
      </main>
    </div>
  );
}
