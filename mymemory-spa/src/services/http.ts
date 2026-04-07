import axios, { AxiosError, type AxiosInstance, type AxiosResponse } from 'axios';

export interface ApiSuccessResponse<T = unknown> {
  data: T;
  meta?: Record<string, unknown>;
}

export interface ApiErrorResponse {
  error: true;
  message: string;
  error_code?: string;
  errors?: Record<string, string[]>;
}

export class ApiError extends Error {
  readonly status: number;
  readonly errorCode: string | undefined;
  readonly errors: Record<string, string[]> | undefined;

  constructor(
    message: string,
    status: number,
    errorCode?: string,
    errors?: Record<string, string[]>,
  ) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.errorCode = errorCode;
    this.errors = errors;
  }
}

function createHttpClient(): AxiosInstance {
  const client = axios.create({
    baseURL: import.meta.env.VITE_API_URL ?? 'http://localhost:7100/api/v1',
    withCredentials: true, // send/receive cookies (Sanctum SPA auth)
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
  });

  client.interceptors.response.use(
    (response: AxiosResponse) => response,
    (error: AxiosError<ApiErrorResponse>) => {
      const status = error.response?.status ?? 0;
      const body = error.response?.data;

      const message = body?.message ?? error.message ?? 'Unexpected error.';
      const errorCode = body?.error_code;
      const errors = body?.errors;

      return Promise.reject(new ApiError(message, status, errorCode, errors));
    },
  );

  return client;
}

export const http = createHttpClient();

/**
 * Extracts the `data` field from a standard API success response.
 */
export function unwrap<T>(response: AxiosResponse<ApiSuccessResponse<T>>): T {
  return response.data.data;
}
