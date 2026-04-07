import clsx from 'clsx';
import { useCallback, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import type { AiLevel } from '../../../types/models';
import type { UpdatePreferencesPayload } from '../../../types/api';
import { useMe, useUpdatePreferences, useUsage } from '../hooks/useMe';

// ---------------------------------------------------------------------------
// Types / constants
// ---------------------------------------------------------------------------

type Tab = 'prefs' | 'usage';

const AI_LEVELS: { value: AiLevel; label: string }[] = [
  { value: 'none', label: 'Sem IA' },
  { value: 'basic', label: 'Básico (palavras-chave)' },
  { value: 'full', label: 'Completo (resumo)' },
];

const AI_LEVEL_FIELDS: { key: keyof UpdatePreferencesPayload; label: string; hint: string }[] = [
  { key: 'ai_level_text', label: 'Texto', hint: 'Memos criados como texto digitado.' },
  { key: 'ai_level_url', label: 'Página URL', hint: 'Memos a partir de endereço web.' },
  { key: 'ai_level_image', label: 'Imagem', hint: 'Fotos e arquivos de imagem.' },
  { key: 'ai_level_audio', label: 'Áudio', hint: 'Gravações e arquivos de áudio.' },
  { key: 'ai_level_video', label: 'Vídeo', hint: 'Gravações e arquivos de vídeo.' },
  { key: 'ai_level_document', label: 'Documento', hint: 'PDF, Office e documentos semelhantes.' },
];

// ---------------------------------------------------------------------------
// Sub-components
// ---------------------------------------------------------------------------

function UsageBar({ used, limit }: { used: number; limit: number | null }) {
  const pct = limit == null || limit <= 0 ? (used > 0 ? 100 : 0) : Math.min(100, (used / limit) * 100);
  return (
    <div
      role="progressbar"
      aria-valuenow={Math.round(pct)}
      aria-valuemin={0}
      aria-valuemax={100}
      className="h-1.5 w-full rounded-full bg-gray-100 overflow-hidden"
    >
      <div
        className={clsx('h-full rounded-full transition-all', pct >= 90 ? 'bg-red-500' : pct >= 70 ? 'bg-amber-400' : 'bg-indigo-500')}
        style={{ width: `${pct}%` }}
      />
    </div>
  );
}

function UsageRow({
  icon, label, used, limit, suffix = '',
}: {
  icon: string; label: string; used: number; limit: number | null; suffix?: string;
}) {
  const limitStr = limit == null ? '∞' : `${limit}`;
  return (
    <div className="space-y-1.5">
      <div className="flex justify-between text-sm">
        <span className="flex items-center gap-1.5 text-gray-700 font-medium">
          <span aria-hidden>{icon}</span>
          {label}
        </span>
        <span className="text-gray-500">
          {used}{suffix} / {limitStr}{suffix}
        </span>
      </div>
      <UsageBar used={used} limit={limit} />
    </div>
  );
}

// ---------------------------------------------------------------------------
// Page
// ---------------------------------------------------------------------------

export default function UserPreferencesPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const tab: Tab = searchParams.get('tab') === 'usage' ? 'usage' : 'prefs';

  const setTab = useCallback(
    (t: Tab) => {
      if (t === 'prefs') setSearchParams({}, { replace: true });
      else setSearchParams({ tab: 'usage' }, { replace: true });
    },
    [setSearchParams],
  );

  const { data: user, isLoading: userLoading } = useMe();
  const { data: usage, isLoading: usageLoading } = useUsage();
  const { mutate: savePreferences, isPending: saving, error: saveError, isSuccess: saved } = useUpdatePreferences();

  // Local draft of the preferences form
  const prefs = user?.preferences;
  const [draft, setDraft] = useState<UpdatePreferencesPayload>({});

  const updateDraft = useCallback(<K extends keyof UpdatePreferencesPayload>(key: K, value: UpdatePreferencesPayload[K]) => {
    setDraft((prev) => ({ ...prev, [key]: value }));
  }, []);

  function handleSave() {
    if (Object.keys(draft).length === 0) return;
    savePreferences(draft, { onSuccess: () => setDraft({}) });
  }

  if (userLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <p className="text-gray-400 animate-pulse">Carregando…</p>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-2xl mx-auto px-4 py-10">
        <h1 className="text-2xl font-semibold text-gray-900 mb-1">Minha conta</h1>
        <p className="text-sm text-gray-500 mb-6">{user?.email}</p>

        {/* Tabs */}
        <div role="tablist" className="flex gap-1 border-b border-gray-200 mb-8">
          {([['prefs', 'Preferências de memo'], ['usage', 'Sua utilização']] as const).map(([t, label]) => (
            <button
              key={t}
              type="button"
              role="tab"
              aria-selected={tab === t}
              onClick={() => setTab(t)}
              className={clsx(
                'px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors',
                tab === t
                  ? 'border-indigo-600 text-indigo-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700',
              )}
            >
              {label}
            </button>
          ))}
        </div>

        {/* ---------------------------------------------------------------- */}
        {/* Tab: Preferences                                                  */}
        {/* ---------------------------------------------------------------- */}
        {tab === 'prefs' && prefs && (
          <div className="space-y-8">
            {/* Flow & sound */}
            <section>
              <h2 className="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-4">Fluxo e áudio</h2>
              <div className="bg-white rounded-2xl shadow-sm divide-y divide-gray-100">

                <label className="flex items-start gap-4 px-5 py-4 cursor-pointer hover:bg-gray-50 transition-colors">
                  <input
                    type="checkbox"
                    className="mt-0.5 h-4 w-4 rounded text-indigo-600 border-gray-300 focus:ring-indigo-500"
                    defaultChecked={prefs.confirm_before_processing}
                    onChange={(e) => updateDraft('confirm_before_processing', e.target.checked)}
                  />
                  <span>
                    <span className="block text-sm font-medium text-gray-800">Confirmar antes do envio à IA</span>
                    <span className="block text-xs text-gray-500 mt-0.5">
                      Após capturar o arquivo, você confirma o registro antes de enviar ao processamento.
                    </span>
                  </span>
                </label>

                <label className="flex items-start gap-4 px-5 py-4 cursor-pointer hover:bg-gray-50 transition-colors">
                  <input
                    type="checkbox"
                    className="mt-0.5 h-4 w-4 rounded text-indigo-600 border-gray-300 focus:ring-indigo-500"
                    defaultChecked={prefs.sound_enabled}
                    onChange={(e) => updateDraft('sound_enabled', e.target.checked)}
                  />
                  <span>
                    <span className="block text-sm font-medium text-gray-800">Áudio para palavras-chave por voz</span>
                    <span className="block text-xs text-gray-500 mt-0.5">
                      Permite usar o microfone para inserir palavras-chave ao registrar memos.
                    </span>
                  </span>
                </label>

                {/* OCR threshold */}
                <div className="px-5 py-4">
                  <label htmlFor="ocr-threshold" className="block text-sm font-medium text-gray-800 mb-1">
                    Confiança mínima do OCR (imagem)
                  </label>
                  <p className="text-xs text-gray-500 mb-2">
                    1–100: se a leitura automática ficar abaixo deste valor, o sistema usa visão por IA. Deixe vazio para desligar.
                  </p>
                  <input
                    id="ocr-threshold"
                    type="number"
                    min={1}
                    max={100}
                    step={1}
                    defaultValue={prefs.ocr_correction_threshold ?? ''}
                    placeholder="Desligado"
                    className="w-28 rounded-lg border border-gray-200 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    onChange={(e) => {
                      const raw = e.target.value.trim();
                      updateDraft('ocr_correction_threshold', raw === '' ? null : Number(raw));
                    }}
                  />
                </div>
              </div>
            </section>

            {/* AI levels per media type */}
            <section>
              <h2 className="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-4">
                Uso de IA por tipo de mídia (padrão)
              </h2>
              <div className="bg-white rounded-2xl shadow-sm divide-y divide-gray-100">
                {AI_LEVEL_FIELDS.map(({ key, label, hint }) => {
                  const currentValue = (draft[key] as AiLevel | undefined) ?? (prefs[key as keyof typeof prefs] as AiLevel);
                  return (
                    <div key={key} className="px-5 py-4">
                      <div className="flex items-center justify-between gap-4">
                        <span>
                          <span className="block text-sm font-medium text-gray-800">{label}</span>
                          <span className="block text-xs text-gray-500">{hint}</span>
                        </span>
                        <select
                          className="text-sm rounded-lg border border-gray-200 px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                          value={currentValue}
                          onChange={(e) => updateDraft(key, e.target.value as AiLevel)}
                        >
                          {AI_LEVELS.map(({ value, label: optLabel }) => (
                            <option key={value} value={value}>{optLabel}</option>
                          ))}
                        </select>
                      </div>
                    </div>
                  );
                })}
              </div>
            </section>

            {/* Actions */}
            <div className="flex items-center gap-3">
              <button
                type="button"
                disabled={saving || Object.keys(draft).length === 0}
                onClick={handleSave}
                className={clsx(
                  'px-5 py-2 rounded-lg text-sm font-medium transition-colors',
                  'bg-indigo-600 text-white hover:bg-indigo-700',
                  'disabled:opacity-50 disabled:cursor-not-allowed',
                )}
              >
                {saving ? 'Salvando…' : 'Salvar preferências'}
              </button>

              {saved && Object.keys(draft).length === 0 && (
                <span className="text-sm text-green-600">Preferências salvas.</span>
              )}
              {saveError && (
                <span className="text-sm text-red-600">{saveError.message}</span>
              )}
            </div>
          </div>
        )}

        {/* ---------------------------------------------------------------- */}
        {/* Tab: Usage                                                        */}
        {/* ---------------------------------------------------------------- */}
        {tab === 'usage' && (
          <div className="bg-white rounded-2xl shadow-sm p-6 space-y-6">
            <div>
              <p className="text-lg font-semibold text-gray-900">
                Plano {usage?.memos ? '' : '…'}
              </p>
              <p className="text-sm text-gray-500">{user?.email}</p>
            </div>

            {usageLoading && <p className="text-gray-400 animate-pulse text-sm">Carregando…</p>}

            {usage && !usageLoading && (
              <div className="space-y-5">
                <UsageRow icon="📄" label="Memos" used={usage.memos.used} limit={usage.memos.limit} />
                <UsageRow
                  icon="🗃"
                  label="Armazenamento"
                  used={Math.round((usage.storage_bytes.used / 1e9) * 100) / 100}
                  limit={usage.storage_bytes.limit_gb}
                  suffix=" GB"
                />
                <UsageRow
                  icon="🧠"
                  label="Créditos IA (mês)"
                  used={usage.api_credits.used}
                  limit={usage.api_credits.limit}
                />
                <UsageRow
                  icon="⬇"
                  label="Downloads (mês)"
                  used={usage.downloads.used}
                  limit={usage.downloads.limit}
                />
              </div>
            )}

            <p className="text-xs text-gray-400 pt-2 border-t border-gray-100">
              Créditos e downloads são contados no mês civil atual (servidor). Limites vêm do plano associado à sua
              assinatura individual ou ao grupo ativo.
            </p>
          </div>
        )}
      </div>
    </div>
  );
}
