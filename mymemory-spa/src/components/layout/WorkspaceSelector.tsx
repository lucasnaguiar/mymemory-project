import clsx from 'clsx';
import { useMe, useUpdateWorkspace, useWorkspaceGroups } from '../../features/me/hooks/useMe';

export default function WorkspaceSelector() {
  const { data: user } = useMe();
  const { data: groups = [], isLoading } = useWorkspaceGroups();
  const { mutate: switchWorkspace, isPending } = useUpdateWorkspace();

  const activeId = user?.active_workspace_group_id ?? null;

  function handleChange(e: React.ChangeEvent<HTMLSelectElement>) {
    const val = e.target.value;
    switchWorkspace({ group_id: val === 'personal' ? null : Number(val) });
  }

  if (!user) return null;

  return (
    <div className="flex items-center gap-2">
      <span className="text-xs text-gray-500 hidden sm:inline">Workspace</span>
      <select
        className={clsx(
          'text-sm rounded-lg border border-gray-200 bg-white px-2 py-1.5 pr-7',
          'focus:outline-none focus:ring-2 focus:ring-indigo-500',
          (isLoading || isPending) && 'opacity-60 pointer-events-none',
        )}
        value={activeId ?? 'personal'}
        onChange={handleChange}
        aria-label="Switch workspace"
      >
        <option value="personal">Personal</option>
        {groups.map((g) => (
          <option key={g.id} value={g.id}>
            {g.name}
          </option>
        ))}
      </select>
    </div>
  );
}
