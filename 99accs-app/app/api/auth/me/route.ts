import { cookies } from 'next/headers';
import { NextResponse } from 'next/server';
import { laravelFetch } from '@/lib/auth/laravelClient';
import { USER_COOKIE, clearSession, readToken, type SessionUser } from '@/lib/auth/cookies';

interface LaravelMe {
  data: SessionUser;
}

/**
 * Returns the authenticated customer, or `{ data: null }` if no session.
 * We deliberately respond 200+null (instead of 401) so AuthHydrator can poll
 * this on every page load without producing console errors for guests — same
 * behaviour the original mock /user endpoint had.
 */
export async function GET() {
  const token = await readToken();
  if (!token) return NextResponse.json({ data: null });

  const res = await laravelFetch<LaravelMe>('/user', { token });

  if (res.status === 401) {
    // Token revoked or expired upstream — clean up local cookies.
    await clearSession();
    return NextResponse.json({ data: null });
  }

  if (!res.ok) {
    // Upstream blip — don't blow the session away; let the caller retry.
    return NextResponse.json({ data: null }, { status: 503 });
  }

  // Refresh the user-snapshot cookie so server components stay in sync.
  const user = res.body.data;
  const store = await cookies();
  store.set(USER_COOKIE, JSON.stringify(user), {
    httpOnly: true,
    secure: process.env.NODE_ENV === 'production',
    sameSite: 'lax',
    path: '/',
    maxAge: 60 * 60 * 24,
  });

  return NextResponse.json({ data: user });
}
