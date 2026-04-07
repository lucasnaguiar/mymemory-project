import { http, unwrap } from './http';

export interface HealthStatus {
  status: 'ok' | 'degraded';
  database: 'ok' | 'unreachable';
  version: string;
}

export async function fetchHealth(): Promise<HealthStatus> {
  const response = await http.get<{ data: HealthStatus }>('/health');
  return unwrap(response);
}
