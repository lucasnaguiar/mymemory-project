import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import AdminGuard from './AdminGuard';
import { useDocumentAiRouting, useUpdateDocumentAiRouting } from '../hooks/useAdmin';

const DEFAULT_JSON = JSON.stringify({
  pdf:  'extract_text',
  docx: 'extract_text',
  msg:  'extract_text',
  eml:  'extract_text',
  txt:  'extract_text',
}, null, 2);

export default function AdminDocumentAiPage() {
  const { data: routing, isLoading, isError } = useDocumentAiRouting();
  const { mutate: save, isPending: saving }    = useUpdateDocumentAiRouting();

  const [jsonText, setJsonText] = useState('');
  const [jsonErr, setJsonErr]   = useState<string | null>(null);
  const [saveOk, setSaveOk]     = useState(false);
  const [saveErr, setSaveErr]   = useState<string | null>(null);

  useEffect(() => {
    if (routing) {
      setJsonText(JSON.stringify(routing.config, null, 2));
    }
  }, [routing]);

  function handleSave(e: React.FormEvent) {
    e.preventDefault();
    setJsonErr(null);
    setSaveOk(false);
    setSaveErr(null);

    let config: Record<string, string>;
    try {
      config = JSON.parse(jsonText);
    } catch {
      setJsonErr('JSON inválido. Verifique a sintaxe antes de salvar.');
      return;
    }

    save(config, {
      onSuccess: () => setSaveOk(true),
      onError:   (e) => setSaveErr(e.message),
    });
  }

  function handleReset() {
    setJsonText(DEFAULT_JSON);
    setJsonErr(null);
  }

  return (
    <AdminGuard>
      <div className="min-h-screen bg-gray-50">
        <header className="bg-white border-b border-gray-200 px-6 py-3 flex items-center gap-3">
          <Link to="/admin" className="text-gray-400 hover:text-gray-700 text-sm">← Admin</Link>
          <span className="text-gray-300">|</span>
          <h1 className="font-semibold text-gray-800">Roteamento de IA para Documentos</h1>
        </header>

        <div className="p-6 max-w-2xl mx-auto space-y-4">
          <p className="text-sm text-gray-500">
            Mapeamento de extensão de arquivo → pipeline de IA. Cada chave é uma extensão (sem ponto), o valor é o nome do pipeline.
            {routing?.using_defaults && (
              <span className="ml-2 text-amber-600 font-medium">(Usando configuração padrão)</span>
            )}
          </p>

          {routing?.updated_by && routing.updated_at && (
            <div className="text-xs text-gray-400">
              Última atualização por <strong>{routing.updated_by.name}</strong> em {new Date(routing.updated_at).toLocaleString('pt-BR')}
            </div>
          )}

          {isLoading && <div className="text-gray-400 text-sm">Carregando configuração...</div>}
          {isError   && <div className="text-red-500 text-sm">Erro ao carregar configuração.</div>}

          {saveOk && (
            <div className="bg-green-50 border border-green-200 text-green-700 text-sm px-3 py-2 rounded">
              Configuração salva com sucesso.
            </div>
          )}
          {saveErr && (
            <div className="bg-red-50 border border-red-200 text-red-700 text-sm px-3 py-2 rounded">{saveErr}</div>
          )}

          <form onSubmit={handleSave} className="space-y-3">
            <div>
              <textarea
                className={`w-full h-64 font-mono text-sm border rounded-lg p-3 focus:outline-none focus:ring-1 ${
                  jsonErr ? 'border-red-400 focus:ring-red-400' : 'border-gray-200 focus:ring-blue-400'
                }`}
                value={jsonText}
                onChange={e => { setJsonText(e.target.value); setJsonErr(null); setSaveOk(false); }}
                spellCheck={false}
              />
              {jsonErr && <p className="text-red-500 text-xs mt-1">{jsonErr}</p>}
            </div>

            <div className="flex gap-2">
              <button type="button" onClick={handleReset} className="px-3 py-1.5 text-sm border border-gray-200 rounded hover:bg-gray-100">
                Restaurar padrão
              </button>
              <button type="submit" disabled={saving || isLoading} className="px-4 py-1.5 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50">
                {saving ? 'Salvando...' : 'Salvar configuração'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </AdminGuard>
  );
}
