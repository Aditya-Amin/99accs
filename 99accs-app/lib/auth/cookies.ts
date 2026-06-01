import { cookies } from 'next/headers';

export const TOKEN_COOKIE = '99accs_token';
export const USER_COOKIE = '99accs_user';
export const RESET_TOKEN_COOKIE = '99accs_reset_token';
export const RESET_EMAIL_COOKIE = '99accs_reset_email';

const COOKIE_BASE = {
  httpOnly: true as const,
  secure: process.env.NODE_ENV === 'production',
  sameSite: 'lax' as const,
  path: '/',
};

const DAY = 60 * 60 * 24;

export interface SessionUser {
  id: number;
  name: string;
  first_name?: string;
  last_name?: string;
  email: string;
  phone?: string | null;
  must_reset_password?: boolean;
  is_legacy?: boolean;
  created_at?: string;
}

/**
 * Persist a successful authentication outcome:
 *  - 99accs_token : opaque Sanctum personal access token (httpOnly)
 *  - 99accs_user  : JSON-encoded user snapshot so server components can read
 *                   identity without a Laravel roundtrip on every render.
 *
 * Also clears any in-flight reset cookies — a successful auth supersedes any
 * pending legacy-reset state.
 */
export async function persistSession(token: string, user: SessionUser): Promise<void> {
  const store = await cookies();
  const ttl = sanctumTokenTtl();
  store.set(TOKEN_COOKIE, token, { ...COOKIE_BASE, maxAge: ttl });
  store.set(USER_COOKIE, JSON.stringify(user), { ...COOKIE_BASE, maxAge: ttl });
  store.delete(RESET_TOKEN_COOKIE);
  store.delete(RESET_EMAIL_COOKIE);
}

/** Clears the session cookies. Idempotent. */
export async function clearSession(): Promise<void> {
  const store = await cookies();
  store.delete(TOKEN_COOKIE);
  store.delete(USER_COOKIE);
  store.delete(RESET_TOKEN_COOKIE);
  store.delete(RESET_EMAIL_COOKIE);
}

/**
 * Mark the user as mid-migration. proxy.ts hard-redirects every URL except
 * /reset-password while these cookies are set, so the user is funneled
 * straight to the reset page.
 */
export async function markPendingReset(email: string): Promise<void> {
  const store = await cookies();
  // 15-minute window — matches the typical "click the link in the email" gap.
  const FIFTEEN_MIN = 60 * 15;
  store.set(RESET_TOKEN_COOKIE, `pending_${Date.now()}`, { ...COOKIE_BASE, maxAge: FIFTEEN_MIN });
  store.set(RESET_EMAIL_COOKIE, email, { ...COOKIE_BASE, maxAge: FIFTEEN_MIN });
  store.delete(TOKEN_COOKIE);
  store.delete(USER_COOKIE);
}

export async function readToken(): Promise<string | undefined> {
  const store = await cookies();
  return store.get(TOKEN_COOKIE)?.value;
}

/**
 * Mirrors Sanctum's token-expiration setting so the cookie dies in lockstep
 * with the backing token. SANCTUM_TOKEN_EXPIRATION in Laravel is minutes;
 * we expose the same env name on the Next side so they can be kept in sync
 * with a single setting in a future deploy script.
 */
function sanctumTokenTtl(): number {
  const minutes = Number(process.env.SANCTUM_TOKEN_EXPIRATION ?? 60 * 24);
  if (!Number.isFinite(minutes) || minutes <= 0) return DAY;
  return Math.floor(minutes * 60);
}
