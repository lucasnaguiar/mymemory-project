import { useState } from 'react';
import { Link } from 'react-router-dom';
import AdminGuard from './AdminGuard';
import {
  useCostReport,
  useCreatePlan,
  useDeletePlan,
  usePlans,
  useSoftDeletedSummary,
  useHardDeleteMonth,
  useUpdatePlan,
} from '../hooks/useAdmin';
import type { CreatePlanPayload, Plan, CostReportParams } from '../adminService';

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

type Tab = 'planos' | 'custos' | 'eliminacao';

const MEDIA_OPTIONS = [
  { value: 'all',      label: 'Todos' },
  { value: 'text',     label: 'Texto' },
  { value: 'audio',    label: 'Áudio' },
  { value: 'image',    label: 'Imagem' },
  { value: 'video',    label: 'Vídeo' },
  { value: 'document', label: 'Documento' },
  { value: 'url',      label: 'URL' },
];

// ---------------------------------------------------------------------------
// Sub-components
// ---------------------------------------------------------------------------

function PlanRow({ plan, onEdit, onDelete }: { plan: Plan; onEdit: (p: Plan) => void; onDelete: (id: number) => void }) {
  const total = (plan.user_count ?? 0) + (plan.group_count ?? 0);
  return (
    <tr className="border-t border-gray-100 hover:bg-gray-50 text-sm">
      <td className="py-2 px-3 font-medium">{plan.name}</td>
      <td className="py-2 px-3 text-gray-500 capitalize">{plan.type}</td>
      <td className="py-2 px-3">
        <span className={`px-2 py-0.5 rounded-full text-xs ${plan.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'}`}>
          {plan.is_active ? 'Ativo' : 'Inativo'}
        </span>
      </td>
      <td className="py-2 px-3 text-right">{plan.storage_gb} GB</td>
      <td className="py-2 px-3 text-right">{plan.price_cents === 0 ? 'Gratuito' : `R$ ${(plan.price_cents / 100).toFixed(2)}`}</td>
      <td className="py-2 px-3 text-right text-gray-500">{total}</td>
      <td className="py-2 px-3 text-right space-x-2">
        <button onClick={() => onEdit(plan)} className="text-blue-600 hover:underline text-xs">Editar</button>
        <button
          onClick={() => onDelete(plan.id)}
          disabled={total > 0}
          title={total > 0 ? `${total} assinante(s) vinculado(s)` : 'Excluir plano'}
          className="text-red-500 hover:underline text-xs disabled:opacity-40 disabled:cursor-not-allowed"
        >
          Excluir
        </button>
      </td>
    </tr>
  );
}

function PlanForm({ initial, onSubmit, onCancel }: {
  initial?: Partial<CreatePlanPayload>;
  onSubmit: (p: CreatePlanPayload) => void;
  onCancel: () => void;
}) {
  const [form, setForm] = useState<CreatePlanPayload>({
    name: initial?.name ?? '',
    type: initial?.type ?? 'individual',
    is_active: initial?.is_active ?? true,
    storage_gb: initial?.storage_gb ?? 5,
    price_cents: initial?.price_cents ?? 0,
    max_memos: initial?.max_memos ?? null,
    api_credits_per_month: initial?.api_credits_per_month ?? null,
    downloads_per_month: initial?.downloads_per_month ?? null,
    supports_audio_video: initial?.supports_audio_video ?? false,
    supports_chunking: initial?.supports_chunking ?? false,
  });

  const field = (key: keyof CreatePlanPayload, label: string, type: string = 'text') => (
    <label key={key} className="flex flex-col gap-0.5">
      <span className="text-xs text-gray-600">{label}</span>
      <input
        type={type}
        className="border border-gray-200 rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400"
        value={(form[key] as string | number | undefined) ?? ''}
        onChange={e => {
          const raw = e.target.value;
          const numericFields: (keyof CreatePlanPayload)[] = ['storage_gb', 'price_cents', 'max_memos', 'api_credits_per_month', 'downloads_per_month'];
          setForm(f => ({
            ...f,
            [key]: numericFields.includes(key) ? (raw === '' ? null : Number(raw)) : raw,
          }));
        }}
      />
    </label>
  );

  return (
    <form className="bg-gray-50 border border-gray-200 rounded-lg p-4 grid grid-cols-2 gap-3" onSubmit={e => { e.preventDefault(); onSubmit(form); }}>
      {field('name', 'Nome')}
      <label className="flex flex-col gap-0.5">
        <span className="text-xs text-gray-600">Tipo</span>
        <select className="border border-gray-200 rounded px-2 py-1 text-sm" value={form.type} onChange={e => setForm(f => ({ ...f, type: e.target.value as 'individual' | 'group' }))}>
          <option value="individual">Individual</option>
          <option value="group">Grupo</option>
        </select>
      </label>
      {field('storage_gb', 'Armazenamento (GB)', 'number')}
      {field('price_cents', 'Preço (centavos)', 'number')}
      {field('max_memos', 'Máx. memos (vazio=ilimitado)', 'number')}
      {field('api_credits_per_month', 'Créditos IA/mês', 'number')}
      {field('downloads_per_month', 'Downloads/mês', 'number')}

      <label className="flex items-center gap-2 col-span-2">
        <input type="checkbox" checked={form.is_active} onChange={e => setForm(f => ({ ...f, is_active: e.target.checked }))} />
        <span className="text-sm">Ativo</span>
      </label>
      <label className="flex items-center gap-2">
        <input type="checkbox" checked={form.supports_audio_video} onChange={e => setForm(f => ({ ...f, supports_audio_video: e.target.checked }))} />
        <span className="text-sm">Suporte áudio/vídeo</span>
      </label>
      <label className="flex items-center gap-2">
        <input type="checkbox" checked={form.supports_chunking} onChange={e => setForm(f => ({ ...f, supports_chunking: e.target.checked }))} />
        <span className="text-sm">Suporte chunking</span>
      </label>

      <div className="col-span-2 flex gap-2 justify-end pt-2">
        <button type="button" onClick={onCancel} className="px-3 py-1 text-sm border border-gray-200 rounded hover:bg-gray-100">Cancelar</button>
        <button type="submit" className="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">Salvar</button>
      </div>
    </form>
  );
}

// ---------------------------------------------------------------------------
// Main Page
// ---------------------------------------------------------------------------

export default function AdminPage() {
  const [tab, setTab] = useState<Tab>('planos');

  return (
    <AdminGuard>
      <div className="min-h-screen bg-gray-50">
        {/* Header */}
        <header className="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <Link to="/" className="text-gray-400 hover:text-gray-700 text-sm">← Início</Link>
            <span className="text-gray-300">|</span>
            <h1 className="font-semibold text-gray-800">Painel Admin</h1>
          </div>
          <div className="flex gap-2 text-sm">
            <Link to="/admin/media-settings" className="text-blue-600 hover:underline">Config. Mídia</Link>
            <span className="text-gray-300">·</span>
            <Link to="/admin/document-ai" className="text-blue-600 hover:underline">IA Documentos</Link>
          </div>
        </header>

        {/* Tabs */}
        <div className="border-b border-gray-200 bg-white px-6">
          <nav className="flex gap-4">
            {(['planos', 'custos', 'eliminacao'] as Tab[]).map(t => (
              <button
                key={t}
                onClick={() => setTab(t)}
                className={`py-3 text-sm border-b-2 transition-colors capitalize ${tab === t ? 'border-blue-600 text-blue-700 font-medium' : 'border-transparent text-gray-500 hover:text-gray-700'}`}
              >
                {t === 'planos' ? 'Planos' : t === 'custos' ? 'Relatório de Custos' : 'Eliminação Permanente'}
              </button>
            ))}
          </nav>
        </div>

        {/* Content */}
        <div className="p-6 max-w-6xl mx-auto">
          {tab === 'planos'    && <PlansTab />}
          {tab === 'custos'    && <CostReportTab />}
          {tab === 'eliminacao'&& <EliminationTab />}
        </div>
      </div>
    </AdminGuard>
  );
}

// ---------------------------------------------------------------------------
// Plans Tab
// ---------------------------------------------------------------------------

function PlansTab() {
  const { data: plans = [], isLoading, isError } = usePlans();
  const { mutate: createPlan, isPending: creating } = useCreatePlan();
  const { mutate: updatePlan, isPending: updating } = useUpdatePlan();
  const { mutate: deletePlan, isPending: deleting } = useDeletePlan();

  const [showForm, setShowForm]     = useState(false);
  const [editPlan, setEditPlan]     = useState<Plan | null>(null);
  const [deleteError, setDeleteErr] = useState<string | null>(null);

  function handleCreate(payload: CreatePlanPayload) {
    createPlan(payload, {
      onSuccess: () => setShowForm(false),
    });
  }

  function handleUpdate(payload: CreatePlanPayload) {
    if (!editPlan) return;
    updatePlan({ id: editPlan.id, payload }, { onSuccess: () => setEditPlan(null) });
  }

  function handleDelete(id: number) {
    setDeleteErr(null);
    deletePlan(id, {
      onError: (e) => setDeleteErr(e.message),
    });
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h2 className="font-semibold text-gray-800">Planos de assinatura</h2>
        <button
          onClick={() => { setShowForm(true); setEditPlan(null); }}
          className="px-3 py-1.5 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
        >
          + Novo plano
        </button>
      </div>

      {deleteError && <div className="bg-red-50 border border-red-200 text-red-700 text-sm px-3 py-2 rounded">{deleteError}</div>}

      {showForm && (
        <PlanForm onSubmit={handleCreate} onCancel={() => setShowForm(false)} />
      )}

      {editPlan && (
        <PlanForm
          initial={editPlan}
          onSubmit={handleUpdate}
          onCancel={() => setEditPlan(null)}
        />
      )}

      {isLoading && <div className="text-gray-400 text-sm">Carregando planos...</div>}
      {isError   && <div className="text-red-500 text-sm">Erro ao carregar planos.</div>}

      {plans.length > 0 && (
        <div className="bg-white rounded-lg border border-gray-200 overflow-x-auto">
          <table className="w-full">
            <thead className="bg-gray-50">
              <tr>
                {['Nome', 'Tipo', 'Status', 'Storage', 'Preço', 'Assinantes', 'Ações'].map(h => (
                  <th key={h} className="py-2 px-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">{h}</th>
                ))}
              </tr>
            </thead>
            <tbody>
              {plans.map(p => (
                <PlanRow key={p.id} plan={p} onEdit={setEditPlan} onDelete={handleDelete} />
              ))}
            </tbody>
          </table>
        </div>
      )}

      {!isLoading && plans.length === 0 && (
        <div className="text-gray-400 text-sm text-center py-8">Nenhum plano cadastrado.</div>
      )}

      {(creating || updating || deleting) && (
        <div className="text-sm text-gray-400">Salvando...</div>
      )}
    </div>
  );
}

// ---------------------------------------------------------------------------
// Cost Report Tab
// ---------------------------------------------------------------------------

function ymd(d: Date) {
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

function CostReportTab() {
  const now   = new Date();
  const first = new Date(now.getFullYear(), now.getMonth(), 1);

  const [params, setParams] = useState<CostReportParams>({
    date_from:  ymd(first),
    date_to:    ymd(now),
    media_type: 'all',
  });
  const [applied, setApplied] = useState<CostReportParams>(params);

  const { data: plans = [] } = usePlans();
  const { data: report, isLoading, isError } = useCostReport(applied);

  function apply() { setApplied({ ...params }); }

  return (
    <div className="space-y-4">
      <h2 className="font-semibold text-gray-800">Relatório de custos / uso</h2>

      {/* Filters */}
      <div className="bg-white border border-gray-200 rounded-lg p-4 flex flex-wrap gap-4 items-end">
        <label className="flex flex-col gap-1 text-xs text-gray-600">
          De
          <input type="date" className="border border-gray-200 rounded px-2 py-1 text-sm" value={params.date_from ?? ''} onChange={e => setParams(p => ({ ...p, date_from: e.target.value }))} />
        </label>
        <label className="flex flex-col gap-1 text-xs text-gray-600">
          Até
          <input type="date" className="border border-gray-200 rounded px-2 py-1 text-sm" value={params.date_to ?? ''} onChange={e => setParams(p => ({ ...p, date_to: e.target.value }))} />
        </label>
        <label className="flex flex-col gap-1 text-xs text-gray-600">
          Tipo de mídia
          <select className="border border-gray-200 rounded px-2 py-1 text-sm" value={params.media_type ?? 'all'} onChange={e => setParams(p => ({ ...p, media_type: e.target.value }))}>
            {MEDIA_OPTIONS.map(o => <option key={o.value} value={o.value}>{o.label}</option>)}
          </select>
        </label>
        <label className="flex flex-col gap-1 text-xs text-gray-600">
          Plano
          <select className="border border-gray-200 rounded px-2 py-1 text-sm" value={params.plan_id ?? ''} onChange={e => setParams(p => ({ ...p, plan_id: e.target.value ? Number(e.target.value) : undefined }))}>
            <option value="">Todos</option>
            {plans.map(p => <option key={p.id} value={p.id}>{p.name}</option>)}
          </select>
        </label>
        <button onClick={apply} className="px-4 py-1.5 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">
          Aplicar
        </button>
      </div>

      {isLoading && <div className="text-gray-400 text-sm">Carregando...</div>}
      {isError   && <div className="text-red-500 text-sm">Erro ao carregar relatório.</div>}

      {report && (
        <div className="bg-white border border-gray-200 rounded-lg overflow-hidden">
          <div className="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <span className="text-sm font-medium text-gray-700">Total de memos: <strong>{report.total_memos}</strong></span>
            <span className="text-xs text-gray-400">{report.period.from} → {report.period.to}</span>
          </div>
          <table className="w-full">
            <thead className="bg-gray-50">
              <tr>
                {['Tipo', 'Qtd. memos'].map(h => (
                  <th key={h} className="py-2 px-4 text-left text-xs font-medium text-gray-500 uppercase">{h}</th>
                ))}
              </tr>
            </thead>
            <tbody>
              {report.rows.length === 0 && (
                <tr><td colSpan={2} className="py-6 text-center text-sm text-gray-400">Nenhum memo no período.</td></tr>
              )}
              {report.rows.map(r => (
                <tr key={r.type} className="border-t border-gray-100">
                  <td className="py-2 px-4 text-sm capitalize">{r.type}</td>
                  <td className="py-2 px-4 text-sm text-right">{r.memo_count}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}

// ---------------------------------------------------------------------------
// Elimination (hard-delete) Tab
// ---------------------------------------------------------------------------

const MONTH_NAMES = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

function EliminationTab() {
  const { data: summary = [], isLoading, isError } = useSoftDeletedSummary();
  const { mutate: hardDelete, isPending } = useHardDeleteMonth();

  const [confirm, setConfirm] = useState<{ year: number; month: number } | null>(null);
  const [done, setDone]       = useState<{ year: number; month: number; count: number } | null>(null);

  function requestDelete(year: number, month: number) {
    setDone(null);
    setConfirm({ year, month });
  }

  function doDelete() {
    if (!confirm) return;
    hardDelete(confirm, {
      onSuccess: (r) => {
        setDone({ ...confirm, count: r.deleted_count });
        setConfirm(null);
      },
    });
  }

  return (
    <div className="space-y-4">
      <h2 className="font-semibold text-gray-800">Eliminação permanente de memos excluídos</h2>
      <p className="text-sm text-gray-500">Memos com soft delete aguardam eliminação permanente. A operação é irreversível.</p>

      {done && (
        <div className="bg-green-50 border border-green-200 text-green-700 text-sm px-3 py-2 rounded">
          {done.count} memo(s) de {MONTH_NAMES[done.month - 1]}/{done.year} eliminados permanentemente.
        </div>
      )}

      {/* Confirmation modal */}
      {confirm && (
        <div className="fixed inset-0 bg-black/30 flex items-center justify-center z-50">
          <div className="bg-white rounded-xl shadow-xl p-6 w-80 space-y-4">
            <h3 className="font-semibold text-gray-900">Confirmar eliminação</h3>
            <p className="text-sm text-gray-600">
              Isso eliminará permanentemente todos os memos excluídos de <strong>{MONTH_NAMES[confirm.month - 1]}/{confirm.year}</strong>. Esta operação é irreversível.
            </p>
            <div className="flex gap-2 justify-end">
              <button onClick={() => setConfirm(null)} className="px-3 py-1 text-sm border border-gray-200 rounded hover:bg-gray-50">Cancelar</button>
              <button onClick={doDelete} disabled={isPending} className="px-3 py-1 text-sm bg-red-600 text-white rounded hover:bg-red-700 disabled:opacity-50">
                {isPending ? 'Eliminando...' : 'Eliminar permanentemente'}
              </button>
            </div>
          </div>
        </div>
      )}

      {isLoading && <div className="text-gray-400 text-sm">Carregando...</div>}
      {isError   && <div className="text-red-500 text-sm">Erro ao carregar sumário.</div>}

      {!isLoading && summary.length === 0 && (
        <div className="text-gray-400 text-sm text-center py-8">Nenhum memo pendente de eliminação.</div>
      )}

      {summary.length > 0 && (
        <div className="bg-white border border-gray-200 rounded-lg overflow-hidden">
          <table className="w-full">
            <thead className="bg-gray-50">
              <tr>
                {['Mês/Ano', 'Total de memos', 'Por tipo', 'Ação'].map(h => (
                  <th key={h} className="py-2 px-4 text-left text-xs font-medium text-gray-500 uppercase">{h}</th>
                ))}
              </tr>
            </thead>
            <tbody>
              {summary.map(row => (
                <tr key={`${row.year}-${row.month}`} className="border-t border-gray-100 text-sm">
                  <td className="py-3 px-4 font-medium">{MONTH_NAMES[row.month - 1]}/{row.year}</td>
                  <td className="py-3 px-4">{row.total}</td>
                  <td className="py-3 px-4 text-gray-500 text-xs">
                    {Object.entries(row.by_type).map(([t, c]) => `${t}: ${c}`).join(', ')}
                  </td>
                  <td className="py-3 px-4">
                    <button
                      onClick={() => requestDelete(row.year, row.month)}
                      className="text-red-600 hover:underline text-xs font-medium"
                    >
                      Eliminar permanentemente
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
