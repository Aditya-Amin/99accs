import { cookies } from 'next/headers';
import { buildMockUser, type MockUser } from './server';

const TOKEN_COOKIE = '99accs_token';
const USER_COOKIE = '99accs_user';

const COOKIE_BASE = {
  httpOnly: true as const,
  secure: process.env.NODE_ENV === 'production',
  sameSite: 'lax' as const,
  path: '/',
};
const THIRTY_DAYS = 60 * 60 * 24 * 30;

export interface IssuedSession {
  token: string;
  user: MockUser;
}

// Issues a mock session for the given email. Sets two cookies:
//   1. 99accs_token  — opaque random string; presence = "logged in".
//   2. 99accs_user   — JSON-encoded user object so subsequent endpoints can
//                       branch on identity (demo vs new).
//
// In production this is replaced by Sanctum's createToken() and the server
// resolves user identity from the token, not a separate cookie.
export async function issueSession(email: string): Promise<IssuedSession> {
  const user = buildMockUser(email);
  const token = 'mock_token_' + Math.random().toString(36).slice(2);

  const store = await cookies();
  store.set(TOKEN_COOKIE, token, { ...COOKIE_BASE, maxAge: THIRTY_DAYS });
  store.set(USER_COOKIE, JSON.stringify(user), { ...COOKIE_BASE, maxAge: THIRTY_DAYS });

  // Clear any in-flight reset cookies — a successful login supersedes any
  // pending forced-reset state.
  store.delete('99accs_reset_token');
  store.delete('99accs_reset_email');

  return { token, user };
}
