import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useCreateGroup, useGroupPlans } from '../hooks/useGroups';
import type { GroupPlan } from '../groupService';

function formatPrice(cents: number): string {
  if (cents <= 0) return 'Gratuito';
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100) + '/mês';
}

function PlanCard({
  plan,
  selected,
  onSelect,
}: {
  plan: GroupPlan;
  selected: boolean;
  onSelect: () => void;
}) {
  return (
    <button
      type="button"
      onClick={onSelect}
      className={`w-full text-left border-2 rounded-xl p-4 transition-all ${
        selected
          ? 'border-indigo-500 bg-indigo-50 shadow-md'
          : 'border-gray-200 bg-white hover:border-indigo-300 hover:shadow-sm'
      }`}
    >
      <div className="flex items-start justify-between mb-2">
        <h3 className="font-semibold text-gray-900">{plan.name}</h3>
        <span className={`text-sm font-bold ${selected ? 'text-indigo-700' : 'text-gray-700'}`}>
          {formatPrice(plan.price_cents)}
        </span>
      </div>
      <ul className="text-xs text-gray-500 space-y-1 mt-2">
        <li>📝 {plan.max_memos != null ? `${plan.max_memos} memos` : 'Memos ilimitados'}</li>
        <li>💾 {plan.storage_gb} GB de armazenamento</li>
        {plan.supports_audio_video && <li>🎬 Suporte a áudio e vídeo</li>}
        {plan.api_credits_per_month != null && (
          <li>⚡ {plan.api_credits_per_month} créditos IA/mês</li>
        )}
      </ul>
      {selected && (
        <div className="mt-2 text-xs font-medium text-indigo-600 flex items-center gap-1">
          <span>✓</span> Selecionado
        </div>
      )}
    </button>
  );
}

export default function GroupCreatePage() {
  const navigate = useNavigate();
  const { data: plans, isLoading: loadingPlans, isError: plansError } = useGroupPlans();
  const { mutate: doCreate, isPending: creating, error: createError } = useCreateGroup();

  const [selectedPlanId, setSelectedPlanId] = useState<number | null>(null);
  const [name, setName]                     = useState('');

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (!selectedPlanId || !name.trim()) return;

    doCreate(
      { name: name.trim(), subscription_plan_id: selectedPlanId },
      {
        onSuccess: (group) => {
          navigate(`/grupos/${group.id}/painel`);
        },
      },
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-white border-b border-gray-200 px-6 py-4 flex items-center gap-4">
        <Link to="/" className="text-gray-500 hover:text-indigo-600 text-sm">← Home</Link>
        <h1 className="text-xl font-bold text-gray-900">Criar grupo</h1>
      </header>

      <main className="max-w-3xl mx-auto px-4 py-8">
        <form onSubmit={handleSubmit} className="flex flex-col gap-8">
          {/* Step 1 — Plan selection */}
          <section className="bg-white rounded-xl border border-gray-200 shadow-sm p-6 flex flex-col gap-4">
            <h2 className="text-base font-semibold text-gray-800">1. Escolha o plano de grupo</h2>

            {loadingPlans && <p className="text-sm text-gray-400">Carregando planos…</p>}
            {plansError && <p className="text-sm text-red-600">Não foi possível carregar os planos.</p>}

            {!loadingPlans && plans && plans.length === 0 && (
              <p className="text-sm text-gray-400">Nenhum plano de grupo disponível no momento.</p>
            )}

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              {(plans ?? []).map((plan) => (
                <PlanCard
                  key={plan.id}
                  plan={plan}
                  selected={selectedPlanId === plan.id}
                  onSelect={() => setSelectedPlanId(plan.id)}
                />
              ))}
            </div>
          </section>

          {/* Step 2 — Group name */}
          <section className="bg-white rounded-xl border border-gray-200 shadow-sm p-6 flex flex-col gap-4">
            <h2 className="text-base font-semibold text-gray-800">2. Nome do grupo</h2>
            <input
              type="text"
              value={name}
              onChange={(e) => setName(e.target.value)}
              placeholder="Ex: Equipe de Marketing"
              maxLength={120}
              required
              className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
            />
          </section>

          {createError && (
            <p className="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-4 py-2">
              {createError.message}
            </p>
          )}

          <div className="flex gap-3">
            <button
              type="submit"
              disabled={creating || !selectedPlanId || !name.trim()}
              className="flex-1 py-3 bg-indigo-600 text-white text-sm font-medium rounded-xl hover:bg-indigo-700 disabled:opacity-50"
            >
              {creating ? 'Criando…' : 'Criar grupo'}
            </button>
            <Link
              to="/"
              className="px-5 py-3 text-sm border border-gray-200 rounded-xl hover:bg-gray-50 text-gray-600 text-center"
            >
              Cancelar
            </Link>
          </div>
        </form>
      </main>
    </div>
  );
}
