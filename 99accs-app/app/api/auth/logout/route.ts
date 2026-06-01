import { NextResponse } from 'next/server';
import { laravelFetch } from '@/lib/auth/laravelClient';
import { clearSession, readToken } from '@/lib/auth/cookies';

export async function POST() {
  const token = await readToken();
  if (token) {
    // Best-effort token revocation upstream. We still clear cookies even if
    // the backend call fails — local logout must always succeed.
    await laravelFetch('/logout', { method: 'POST', token });
  }
  await clearSession();
  return NextResponse.json({ message: 'Logged out successfully.' });
}
