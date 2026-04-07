import { useQuery } from '@tanstack/react-query';
import clsx from 'clsx';
import { fetchHealth, type HealthStatus } from '../../services/healthService';

export default function HealthCheckPage() {
  const { data, isLoading, isError, error, refetch } = useQuery<HealthStatus, Error>({
    queryKey: ['health'],
    queryFn: fetchHealth,
    retry: 1,
  });

  const statusColor = (value?: string) =>
    clsx({
      'text-green-600': value === 'ok',
      'text-red-600': value === 'degraded' || value === 'unreachable',
      'text-gray-400': value === undefined,
    });

  return (
    <div className="min-h-screen bg-gray-50 flex items-center justify-center p-6">
      <div className="bg-white rounded-2xl shadow-md p-8 w-full max-w-md">
        <h1 className="text-2xl font-semibold text-gray-800 mb-1">API Health Check</h1>
        <p className="text-sm text-gray-500 mb-6">
          <code className="bg-gray-100 px-1.5 py-0.5 rounded text-xs">GET /api/v1/health</code>
        </p>

        {isLoading && (
          <p className="text-gray-400 animate-pulse">Connecting to API…</p>
        )}

        {isError && (
          <div className="rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-700">
            <p className="font-medium mb-1">Connection failed</p>
            <p className="text-xs break-all">{error?.message}</p>
          </div>
        )}

        {data && (
          <dl className="space-y-3 text-sm">
            <div className="flex justify-between">
              <dt className="text-gray-500">Status</dt>
              <dd className={clsx('font-medium', statusColor(data.status))}>{data.status}</dd>
            </div>
            <div className="flex justify-between">
              <dt className="text-gray-500">Database</dt>
              <dd className={clsx('font-medium', statusColor(data.database))}>{data.database}</dd>
            </div>
            <div className="flex justify-between">
              <dt className="text-gray-500">API Version</dt>
              <dd className="font-medium text-gray-700">{data.version}</dd>
            </div>
          </dl>
        )}

        <button
          onClick={() => refetch()}
          className="mt-6 w-full text-sm bg-gray-800 hover:bg-gray-700 text-white rounded-lg px-4 py-2 transition-colors"
        >
          Refresh
        </button>
      </div>
    </div>
  );
}
