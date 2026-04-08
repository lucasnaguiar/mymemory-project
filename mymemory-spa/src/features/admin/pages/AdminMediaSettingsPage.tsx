import { useState } from 'react';
import { Link } from 'react-router-dom';
import AdminGuard from './AdminGuard';
import { usePlans, useMediaSettings, useUpsertMediaSetting } from '../hooks/useAdmin';
import type { UpsertMediaSettingPayload } from '../adminService';

type MediaType = 'image' | 'audio' | 'video' | 'document';

const MEDIA_TYPES: { value: MediaType; label: string }[] = [
  { value: 'image',    label: 'Imagem' },
  { value: 'audio',    label: 'Áudio' },
  { value: 'video',    label: 'Vídeo' },
  { value: 'document', label: 'Documento' },
];

export default function AdminMediaSettingsPage() {
  const { data: plans = [], isLoading: plansLoading } = usePlans();
  const [selectedPlanId, setSelectedPlanId] = useState<number | null>(null);

  const { data: settings = [], isLoading: settingsLoading } = useMediaSettings(selectedPlanId);
  const { mutate: upsert, isPending: saving } = useUpsertMediaSetting(selectedPlanId ?? 0);

  const [editMediaType, setEditMediaType]   = useState<MediaType | null>(null);
  const [form, setForm] = useState<Partial<UpsertMediaSettingPayload>>({});
  const [saveOk, setSaveOk] = useState(false);
  const [saveErr, setSaveErr] = useState<string | null>(null);

  function openEdit(type: MediaType) {
    setSaveOk(false);
    setSaveErr(null);
    const existing = settings.find(s => s.media_type === type);
    setForm({
      media_type: type,
      max_file_size_mb: existing?.max_file_size_mb ?? 50,
      max_chunk_minutes: existing?.max_chunk_minutes ?? undefined,
      ocr_correction_threshold_default: existing?.ocr_correction_threshold_default ?? undefined,
    });
    setEditMediaType(type);
  }

  function handleSave(e: React.FormEvent) {
    e.preventDefault();
    if (!selectedPlanId || !editMediaType) return;
    setSaveOk(false);
    setSaveErr(null);

    upsert(form as UpsertMediaSettingPayload, {
      onSuccess: () => {
        setSaveOk(true);
        setEditMediaType(null);
      },
      onError: (err) => setSaveErr(err.message),
    });
  }

  return (
    <AdminGuard>
      <div className="min-h-screen bg-gray-50">
        {/* Header */}
        <header className="bg-white border-b border-gray-200 px-6 py-3 flex items-center gap-3">
          <Link to="/admin" className="text-gray-400 hover:text-gray-700 text-sm">← Admin</Link>
          <span className="text-gray-300">|</span>
          <h1 className="font-semibold text-gray-800">Configurações de Mídia por Plano</h1>
        </header>

        <div className="p-6 max-w-3xl mx-auto space-y-6">
          {/* Plan selector */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Selecione o plano</label>
            {plansLoading ? (
              <div className="text-gray-400 text-sm">Carregando planos...</div>
            ) : (
              <select
                className="border border-gray-200 rounded px-3 py-2 text-sm w-full max-w-xs"
                value={selectedPlanId ?? ''}
                onChange={e => {
                  setSelectedPlanId(e.target.value ? Number(e.target.value) : null);
                  setEditMediaType(null);
                  setSaveOk(false);
                }}
              >
                <option value="">Escolha um plano</option>
                {plans.map(p => <option key={p.id} value={p.id}>{p.name} ({p.type})</option>)}
              </select>
            )}
          </div>

          {saveOk && (
            <div className="bg-green-50 border border-green-200 text-green-700 text-sm px-3 py-2 rounded">
              Configuração salva com sucesso.
            </div>
          )}
          {saveErr && (
            <div className="bg-red-50 border border-red-200 text-red-700 text-sm px-3 py-2 rounded">{saveErr}</div>
          )}

          {selectedPlanId && (
            <div className="bg-white border border-gray-200 rounded-lg overflow-hidden">
              <table className="w-full">
                <thead className="bg-gray-50">
                  <tr>
                    {['Tipo de mídia', 'Tam. máx. (MB)', 'Max. chunk (min)', 'Limiar OCR', 'Ação'].map(h => (
                      <th key={h} className="py-2 px-4 text-left text-xs font-medium text-gray-500 uppercase">{h}</th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {settingsLoading ? (
                    <tr><td colSpan={5} className="py-4 text-center text-gray-400 text-sm">Carregando...</td></tr>
                  ) : (
                    MEDIA_TYPES.map(mt => {
                      const row = settings.find(s => s.media_type === mt.value);
                      return (
                        <tr key={mt.value} className="border-t border-gray-100 text-sm">
                          <td className="py-3 px-4 font-medium">{mt.label}</td>
                          <td className="py-3 px-4">{row ? `${row.max_file_size_mb} MB` : <span className="text-gray-400">—</span>}</td>
                          <td className="py-3 px-4">{row?.max_chunk_minutes ?? <span className="text-gray-400">—</span>}</td>
                          <td className="py-3 px-4">{row?.ocr_correction_threshold_default ?? <span className="text-gray-400">—</span>}</td>
                          <td className="py-3 px-4">
                            <button onClick={() => openEdit(mt.value)} className="text-blue-600 hover:underline text-xs">Editar</button>
                          </td>
                        </tr>
                      );
                    })
                  )}
                </tbody>
              </table>
            </div>
          )}

          {/* Edit form */}
          {editMediaType && selectedPlanId && (
            <form onSubmit={handleSave} className="bg-gray-50 border border-gray-200 rounded-lg p-4 space-y-3">
              <h3 className="font-medium text-sm text-gray-800">
                Editar: {MEDIA_TYPES.find(m => m.value === editMediaType)?.label}
              </h3>

              <label className="flex flex-col gap-1 text-xs text-gray-600">
                Tamanho máximo do arquivo (MB)
                <input
                  type="number" min={1} required
                  className="border border-gray-200 rounded px-2 py-1 text-sm w-40"
                  value={form.max_file_size_mb ?? ''}
                  onChange={e => setForm(f => ({ ...f, max_file_size_mb: Number(e.target.value) }))}
                />
              </label>

              {(editMediaType === 'audio' || editMediaType === 'video') && (
                <label className="flex flex-col gap-1 text-xs text-gray-600">
                  Max. chunk em minutos (vazio = sem chunking)
                  <input
                    type="number" min={1} max={120}
                    className="border border-gray-200 rounded px-2 py-1 text-sm w-40"
                    value={form.max_chunk_minutes ?? ''}
                    onChange={e => setForm(f => ({ ...f, max_chunk_minutes: e.target.value ? Number(e.target.value) : null }))}
                  />
                </label>
              )}

              {editMediaType === 'image' && (
                <label className="flex flex-col gap-1 text-xs text-gray-600">
                  Limiar de correção OCR (1–100, vazio = desativado)
                  <input
                    type="number" min={1} max={100}
                    className="border border-gray-200 rounded px-2 py-1 text-sm w-40"
                    value={form.ocr_correction_threshold_default ?? ''}
                    onChange={e => setForm(f => ({ ...f, ocr_correction_threshold_default: e.target.value ? Number(e.target.value) : null }))}
                  />
                </label>
              )}

              <div className="flex gap-2 pt-1">
                <button type="button" onClick={() => setEditMediaType(null)} className="px-3 py-1 text-sm border border-gray-200 rounded hover:bg-gray-100">
                  Cancelar
                </button>
                <button type="submit" disabled={saving} className="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50">
                  {saving ? 'Salvando...' : 'Salvar'}
                </button>
              </div>
            </form>
          )}
        </div>
      </div>
    </AdminGuard>
  );
}
