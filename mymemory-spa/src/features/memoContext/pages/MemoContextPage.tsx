import { useState } from 'react';
import { Link } from 'react-router-dom';
import type { MemoContextCategory, MemoContextField, MemoContextSubcategory } from '../../../types/models';
import {
  useContextGroups,
  useCreateCategory,
  useCreateField,
  useCreateSubcategory,
  useDeleteCategory,
  useDeleteField,
  useDeleteSubcategory,
  useEditorMeta,
  useStructure,
  useUpdateCategory,
  useUpdateField,
  useUpdateSubcategory,
} from '../hooks/useMemoContext';

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

const MEDIA_OPTIONS = [
  { value: '', label: 'Todas as mídias' },
  { value: 'text', label: 'Texto' },
  { value: 'url', label: 'URL' },
  { value: 'image', label: 'Imagem' },
  { value: 'audio', label: 'Áudio' },
  { value: 'video', label: 'Vídeo' },
  { value: 'document', label: 'Documento' },
];

const FIELD_TYPES = [
  { value: 'text', label: 'Texto' },
  { value: 'number', label: 'Número' },
  { value: 'date', label: 'Data' },
  { value: 'boolean', label: 'Booleano' },
];

// ---------------------------------------------------------------------------
// Small helpers
// ---------------------------------------------------------------------------

function mediaLabel(filter: string | null): string {
  return MEDIA_OPTIONS.find((o) => o.value === (filter ?? ''))?.label ?? 'Qualquer mídia';
}

// ---------------------------------------------------------------------------
// Inline editing components
// ---------------------------------------------------------------------------

function InlineInput({
  defaultValue,
  onSave,
  onCancel,
}: {
  defaultValue: string;
  onSave: (v: string) => void;
  onCancel: () => void;
}) {
  const [val, setVal] = useState(defaultValue);
  return (
    <form
      className="flex gap-1"
      onSubmit={(e) => { e.preventDefault(); onSave(val.trim()); }}
    >
      <input
        autoFocus
        value={val}
        onChange={(e) => setVal(e.target.value)}
        className="border border-indigo-300 rounded px-2 py-0.5 text-sm flex-1 focus:outline-none focus:ring-1 focus:ring-indigo-400"
      />
      <button type="submit" className="text-xs px-2 py-0.5 bg-indigo-600 text-white rounded hover:bg-indigo-700">
        ✓
      </button>
      <button type="button" onClick={onCancel} className="text-xs px-2 py-0.5 border border-gray-200 rounded hover:bg-gray-50">
        ✕
      </button>
    </form>
  );
}

// ---------------------------------------------------------------------------
// Sub-row: field
// ---------------------------------------------------------------------------

function FieldRow({
  field,
  canEdit,
}: {
  field: MemoContextField;
  canEdit: boolean;
}) {
  const [editing, setEditing]   = useState(false);
  const [editName, setEditName] = useState(field.name);
  const { mutate: doUpdate }    = useUpdateField();
  const { mutate: doDelete }    = useDeleteField();

  function save() {
    doUpdate({ id: field.id, payload: { name: editName.trim() } }, { onSuccess: () => setEditing(false) });
  }

  return (
    <div className="flex items-center gap-2 py-0.5 pl-6 text-xs text-gray-600 group">
      <span className="shrink-0 text-gray-400">⚙</span>
      {editing ? (
        <InlineInput
          defaultValue={field.name}
          onSave={(v) => { setEditName(v); doUpdate({ id: field.id, payload: { name: v } }, { onSuccess: () => setEditing(false) }); }}
          onCancel={() => setEditing(false)}
        />
      ) : (
        <span className="flex-1">{field.name} <span className="text-gray-400">({field.field_type}{field.is_required ? ', obrigatório' : ''})</span></span>
      )}
      {canEdit && !editing && (
        <div className="hidden group-hover:flex gap-1">
          <button type="button" onClick={() => setEditing(true)} className="text-indigo-500 hover:text-indigo-700 text-xs">Editar</button>
          <button type="button" onClick={() => doDelete(field.id)} className="text-red-400 hover:text-red-600 text-xs">Excluir</button>
        </div>
      )}
    </div>
  );
}

// ---------------------------------------------------------------------------
// Sub-row: subcategory
// ---------------------------------------------------------------------------

function SubcategoryRow({
  sub,
  canEdit,
}: {
  sub: MemoContextSubcategory;
  canEdit: boolean;
}) {
  const [editing, setEditing] = useState(false);
  const { mutate: doUpdate }  = useUpdateSubcategory();
  const { mutate: doDelete }  = useDeleteSubcategory();

  return (
    <div className="flex items-center gap-2 py-0.5 pl-6 text-xs text-gray-600 group">
      <span className="shrink-0 text-gray-400">◦</span>
      {editing ? (
        <InlineInput
          defaultValue={sub.name}
          onSave={(v) => doUpdate({ id: sub.id, payload: { name: v } }, { onSuccess: () => setEditing(false) })}
          onCancel={() => setEditing(false)}
        />
      ) : (
        <span className="flex-1">{sub.name}</span>
      )}
      {canEdit && !editing && (
        <div className="hidden group-hover:flex gap-1">
          <button type="button" onClick={() => setEditing(true)} className="text-indigo-500 hover:text-indigo-700 text-xs">Editar</button>
          <button type="button" onClick={() => doDelete(sub.id)} className="text-red-400 hover:text-red-600 text-xs">Excluir</button>
        </div>
      )}
    </div>
  );
}

// ---------------------------------------------------------------------------
// Category card
// ---------------------------------------------------------------------------

function AddSubForm({
  label,
  onAdd,
}: {
  label: string;
  onAdd: (name: string) => void;
}) {
  const [open, setOpen] = useState(false);
  const [val, setVal]   = useState('');

  if (!open) {
    return (
      <button type="button" onClick={() => setOpen(true)} className="text-xs text-indigo-500 hover:text-indigo-700 mt-1">
        + {label}
      </button>
    );
  }

  return (
    <form
      className="flex gap-1 mt-1"
      onSubmit={(e) => {
        e.preventDefault();
        if (val.trim()) { onAdd(val.trim()); setVal(''); setOpen(false); }
      }}
    >
      <input
        autoFocus
        value={val}
        onChange={(e) => setVal(e.target.value)}
        placeholder={`Nome do ${label.toLowerCase()}…`}
        className="border border-gray-200 rounded px-2 py-0.5 text-xs flex-1 focus:outline-none focus:ring-1 focus:ring-indigo-400"
      />
      <button type="submit" className="text-xs px-2 py-0.5 bg-indigo-600 text-white rounded">OK</button>
      <button type="button" onClick={() => setOpen(false)} className="text-xs px-2 py-0.5 border border-gray-200 rounded">✕</button>
    </form>
  );
}

function CategoryCard({
  cat,
  canEdit,
}: {
  cat: MemoContextCategory;
  canEdit: boolean;
}) {
  const [expanded, setExpanded]  = useState(false);
  const [editingName, setEditing] = useState(false);

  const { mutate: doUpdateCat } = useUpdateCategory();
  const { mutate: doDeleteCat } = useDeleteCategory();
  const { mutate: doAddSub }    = useCreateSubcategory();
  const { mutate: doAddField }  = useCreateField();

  const subs   = cat.subcategories ?? [];
  const fields = cat.fields ?? [];

  return (
    <div className="border border-gray-200 rounded-xl bg-white shadow-sm overflow-hidden">
      {/* Header */}
      <div
        className="flex items-center gap-2 px-4 py-3 cursor-pointer hover:bg-gray-50 transition-colors"
        onClick={() => setExpanded((v) => !v)}
        role="button"
        tabIndex={0}
        onKeyDown={(e) => e.key === 'Enter' && setExpanded((v) => !v)}
      >
        <span className="text-gray-400 text-sm">{expanded ? '▾' : '▸'}</span>

        {editingName ? (
          <span onClick={(e) => e.stopPropagation()} className="flex-1">
            <InlineInput
              defaultValue={cat.name}
              onSave={(v) => doUpdateCat({ id: cat.id, payload: { name: v } }, { onSuccess: () => setEditing(false) })}
              onCancel={() => setEditing(false)}
            />
          </span>
        ) : (
          <h3 className="flex-1 font-medium text-gray-800 text-sm">{cat.name}</h3>
        )}

        <div className="flex items-center gap-2 shrink-0" onClick={(e) => e.stopPropagation()}>
          {cat.media_type_filter && (
            <span className="text-xs px-2 py-0.5 bg-indigo-50 text-indigo-600 rounded-full">
              {mediaLabel(cat.media_type_filter)}
            </span>
          )}
          <span className={`text-xs px-2 py-0.5 rounded-full ${cat.scope === 'global' ? 'bg-gray-100 text-gray-500' : 'bg-blue-50 text-blue-600'}`}>
            {cat.scope === 'global' ? 'Global' : 'Grupo'}
          </span>
          {canEdit && !editingName && (
            <>
              <button
                type="button"
                onClick={() => setEditing(true)}
                className="text-xs text-indigo-500 hover:text-indigo-700"
              >
                Editar
              </button>
              <button
                type="button"
                onClick={() => doDeleteCat(cat.id)}
                className="text-xs text-red-400 hover:text-red-600"
              >
                Excluir
              </button>
            </>
          )}
        </div>
      </div>

      {/* Expanded content */}
      {expanded && (
        <div className="border-t border-gray-100 px-4 py-3 flex flex-col gap-2">
          {/* Subcategories */}
          {subs.length > 0 && (
            <div>
              <p className="text-xs font-medium text-gray-500 mb-1">Subcategorias</p>
              {subs.map((s) => <SubcategoryRow key={s.id} sub={s} canEdit={canEdit} />)}
            </div>
          )}
          {canEdit && (
            <AddSubForm
              label="Subcategoria"
              onAdd={(name) => doAddSub({ categoryId: cat.id, payload: { name } })}
            />
          )}

          {/* Fields */}
          {fields.length > 0 && (
            <div>
              <p className="text-xs font-medium text-gray-500 mb-1 mt-2">Campos</p>
              {fields.map((f) => <FieldRow key={f.id} field={f} canEdit={canEdit} />)}
            </div>
          )}
          {canEdit && (
            <AddSubForm
              label="Campo"
              onAdd={(name) => doAddField({ categoryId: cat.id, payload: { name, field_type: 'text', is_required: false } })}
            />
          )}

          {subs.length === 0 && fields.length === 0 && !canEdit && (
            <p className="text-xs text-gray-400">Nenhuma subcategoria ou campo cadastrado.</p>
          )}
        </div>
      )}
    </div>
  );
}

// ---------------------------------------------------------------------------
// Add category modal
// ---------------------------------------------------------------------------

function AddCategoryModal({
  canEditGlobal,
  editableGroupIds,
  contextGroups,
  onClose,
}: {
  canEditGlobal: boolean;
  editableGroupIds: number[];
  contextGroups: Array<{ id: number; name: string }>;
  onClose: () => void;
}) {
  const [name, setName]         = useState('');
  const [scope, setScope]       = useState<'global' | 'group'>(canEditGlobal ? 'global' : 'group');
  const [groupId, setGroupId]   = useState<number | null>(editableGroupIds[0] ?? null);
  const [mediaFilter, setMedia] = useState('');

  const { mutate: doCreate, isPending, error } = useCreateCategory();

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    doCreate(
      {
        name: name.trim(),
        scope,
        group_id: scope === 'group' ? groupId : undefined,
        media_type_filter: mediaFilter || undefined,
      },
      { onSuccess: onClose },
    );
  }

  const editableGroups = contextGroups.filter((g) => editableGroupIds.includes(g.id));

  return (
    <div
      className="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
      role="presentation"
      onClick={(e) => { if (e.target === e.currentTarget) onClose(); }}
    >
      <div className="bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full mx-4" role="dialog" aria-modal="true">
        <h2 className="text-base font-semibold text-gray-900 mb-4">Nova categoria</h2>

        <form onSubmit={handleSubmit} className="flex flex-col gap-3">
          <input
            autoFocus
            type="text"
            value={name}
            onChange={(e) => setName(e.target.value)}
            placeholder="Nome da categoria"
            required
            className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
          />

          <div className="flex gap-2">
            {canEditGlobal && (
              <label className="flex items-center gap-1.5 text-sm">
                <input type="radio" checked={scope === 'global'} onChange={() => setScope('global')} />
                Global (admin)
              </label>
            )}
            {editableGroupIds.length > 0 && (
              <label className="flex items-center gap-1.5 text-sm">
                <input type="radio" checked={scope === 'group'} onChange={() => setScope('group')} />
                Grupo
              </label>
            )}
          </div>

          {scope === 'group' && (
            <select
              value={groupId ?? ''}
              onChange={(e) => setGroupId(e.target.value ? Number(e.target.value) : null)}
              className="border border-gray-200 rounded-lg px-2 py-2 text-sm bg-white"
            >
              {editableGroups.map((g) => (
                <option key={g.id} value={g.id}>{g.name}</option>
              ))}
            </select>
          )}

          <select
            value={mediaFilter}
            onChange={(e) => setMedia(e.target.value)}
            className="border border-gray-200 rounded-lg px-2 py-2 text-sm bg-white"
          >
            {MEDIA_OPTIONS.map((o) => (
              <option key={o.value} value={o.value}>{o.label}</option>
            ))}
          </select>

          {error && <p className="text-xs text-red-600">{error.message}</p>}

          <div className="flex gap-2 pt-1">
            <button
              type="submit"
              disabled={isPending || !name.trim()}
              className="flex-1 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 disabled:opacity-50"
            >
              {isPending ? 'Criando…' : 'Criar'}
            </button>
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 text-sm border border-gray-200 rounded-lg hover:bg-gray-50"
            >
              Cancelar
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

// ---------------------------------------------------------------------------
// Main page
// ---------------------------------------------------------------------------

export default function MemoContextPage() {
  const [scopeGroupId, setScopeGroupId] = useState<number | null>(null);
  const [mediaFilter, setMediaFilter]   = useState('');
  const [showAddModal, setShowAddModal] = useState(false);

  const { data: meta }          = useEditorMeta();
  const { data: contextGroups } = useContextGroups();
  const { data: categories, isLoading } = useStructure({
    groupId:   scopeGroupId,
    mediaType: mediaFilter || null,
  });

  const canEditGlobal     = meta?.can_edit_global ?? false;
  const editableGroupIds  = meta?.editable_group_ids ?? [];
  const canAddCategory    = canEditGlobal || editableGroupIds.length > 0;

  function canEditCat(cat: MemoContextCategory): boolean {
    if (cat.scope === 'global') return canEditGlobal;
    return cat.group_id != null && editableGroupIds.includes(cat.group_id);
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-white border-b border-gray-200 px-6 py-4 flex items-center gap-4">
        <Link to="/" className="text-gray-500 hover:text-indigo-600 text-sm">← Home</Link>
        <h1 className="text-xl font-bold text-gray-900">Contexto de memo</h1>
      </header>

      <main className="max-w-3xl mx-auto px-4 py-8 flex flex-col gap-6">
        {/* Toolbar */}
        <div className="flex flex-wrap items-center gap-3">
          {/* Scope selector */}
          <select
            value={scopeGroupId ?? ''}
            onChange={(e) => setScopeGroupId(e.target.value ? Number(e.target.value) : null)}
            className="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white"
          >
            <option value="">Somente global</option>
            {(contextGroups ?? []).map((g) => (
              <option key={g.id} value={g.id}>Grupo: {g.name}</option>
            ))}
          </select>

          {/* Media filter */}
          <select
            value={mediaFilter}
            onChange={(e) => setMediaFilter(e.target.value)}
            className="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white"
          >
            {MEDIA_OPTIONS.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
          </select>

          {/* Add button */}
          {canAddCategory && (
            <button
              type="button"
              onClick={() => setShowAddModal(true)}
              className="ml-auto px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700"
            >
              + Nova categoria
            </button>
          )}
        </div>

        {/* Category list */}
        {isLoading ? (
          <p className="text-sm text-gray-400">Carregando estrutura…</p>
        ) : !categories || categories.length === 0 ? (
          <p className="text-sm text-gray-400">
            Nenhuma categoria cadastrada.
            {canAddCategory ? ' Use o botão acima para criar.' : ''}
          </p>
        ) : (
          <div className="flex flex-col gap-3">
            {[...categories]
              .sort((a, b) => a.scope.localeCompare(b.scope) || a.name.localeCompare(b.name))
              .map((cat) => (
                <CategoryCard
                  key={cat.id}
                  cat={cat}
                  canEdit={canEditCat(cat)}
                />
              ))}
          </div>
        )}
      </main>

      {showAddModal && (
        <AddCategoryModal
          canEditGlobal={canEditGlobal}
          editableGroupIds={editableGroupIds}
          contextGroups={contextGroups ?? []}
          onClose={() => setShowAddModal(false)}
        />
      )}
    </div>
  );
}
