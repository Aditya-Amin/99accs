import { ApiError as ApiErrorType } from './types';

const BASE = process.env.NEXT_PUBLIC_API_BASE_URL ?? 'http://localhost:3000/api/mock';

export class ApiError extends Error {
  constructor(public status: number, public body: ApiErrorType) {
    super(body.message);
  }
}

const TIMEOUT_MS = 5000;

export async function api<T>(
  path: string,
  init: RequestInit & { token?: string } = {}
): Promise<T> {
  const { token, signal, ...fetchInit } = init;
  const timeoutSignal = AbortSignal.timeout(TIMEOUT_MS);
  const composedSignal = signal ? AbortSignal.any([signal, timeoutSignal]) : timeoutSignal;
  const url = `${BASE}${path}`;
  let res: Response;
  try {
    res = await fetch(url, {
      ...fetchInit,
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
        ...(fetchInit.headers ?? {}),
      },
      cache: (fetchInit.cache as RequestCache) ?? 'no-store',
      signal: composedSignal,
    });
  } catch (err) {
    if (err instanceof Error && err.name === 'TimeoutError') {
      throw new ApiError(504, {
        message: `Request to ${url} timed out after ${TIMEOUT_MS}ms — is the API server running?`,
      } as ApiErrorType);
    }
    throw err;
  }
  const json = await res.json();
  if (!res.ok) throw new ApiError(res.status, json as ApiErrorType);
  return json as T;
}
