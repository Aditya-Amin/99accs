import { ApiError as ApiErrorType } from './types';

// Server-side rendering and browser requests resolve the API differently.
//
// In production the public URL (https://backup.99accs.com/api/v1) goes through
// Cloudflare, whose bot challenge breaks server-to-server fetches (SSR receives
// the "Just a moment..." HTML instead of JSON). Since Next.js and Laravel run on
// the same host, SSR should hit the local origin directly — bypassing Cloudflare
// and Varnish. Set API_INTERNAL_BASE_URL (server-only, NOT NEXT_PUBLIC_) to e.g.
// http://127.0.0.1:8080/api/v1. The browser keeps using NEXT_PUBLIC_API_BASE_URL.
const IS_SERVER = typeof window === 'undefined';
const BASE =
  (IS_SERVER ? process.env.API_INTERNAL_BASE_URL : undefined) ??
  process.env.NEXT_PUBLIC_API_BASE_URL ??
  'http://localhost:3000/api/mock';

export class ApiError extends Error {
  constructor(public status: number, public body: ApiErrorType) {
    super(body.message);
  }
}

// Dev uses a single-threaded `php artisan serve` (one PHP worker, requests
// serialize), so concurrent SSR + route prefetches queue up and a 5s budget is
// easily blown. Give dev generous headroom; keep prod strict.
const TIMEOUT_MS = process.env.NODE_ENV === 'production' ? 5000 : 20000;

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
