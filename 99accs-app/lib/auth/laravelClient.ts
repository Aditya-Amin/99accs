// Server-side Laravel API client used by the BFF route handlers under
// app/api/auth/*. The frontend never talks to Laravel directly for auth —
// every credential exchange goes Browser → Next.js BFF → Laravel, so the
// Sanctum token can be set into an httpOnly cookie that JS cannot read.

const LARAVEL_BASE =
  process.env.LARAVEL_API_BASE_URL ??
  process.env.NEXT_PUBLIC_API_BASE_URL ??
  'http://localhost:8000/api/v1';

export interface LaravelResponse<T = unknown> {
  status: number;
  ok: boolean;
  body: T;
}

export async function laravelFetch<T = unknown>(
  path: string,
  init: RequestInit & { token?: string } = {},
): Promise<LaravelResponse<T>> {
  const { token, headers, ...rest } = init;
  const url = `${LARAVEL_BASE}${path}`;

  let res: Response;
  try {
    res = await fetch(url, {
      ...rest,
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
        ...(headers ?? {}),
      },
      cache: 'no-store',
    });
  } catch (err) {
    return {
      status: 503,
      ok: false,
      body: {
        code: 'UPSTREAM_UNREACHABLE',
        message:
          err instanceof Error
            ? `Auth backend unreachable: ${err.message}`
            : 'Auth backend unreachable.',
      } as unknown as T,
    };
  }

  // Some endpoints (e.g. logout) return empty body — handle that gracefully.
  const text = await res.text();
  let body: unknown;
  try {
    body = text ? JSON.parse(text) : {};
  } catch {
    body = { message: text || 'Invalid JSON response from auth backend.' };
  }

  return { status: res.status, ok: res.ok, body: body as T };
}
