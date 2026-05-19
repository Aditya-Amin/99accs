import { NextRequest, NextResponse } from 'next/server';
import { cookies } from 'next/headers';

interface ForcedResetBody {
  email?: string;
  reset_token?: string;
  password?: string;
  password_confirmation?: string;
}

const COOKIE_BASE = {
  httpOnly: true as const,
  secure: process.env.NODE_ENV === 'production',
  sameSite: 'lax' as const,
  path: '/',
};

export async function POST(req: NextRequest) {
  const body = (await req.json().catch(() => ({}))) as ForcedResetBody;
  const store = await cookies();

  // Prefer server-side cookie state over client-supplied values for verification.
  // The client only supplies password + confirmation; everything else (which
  // user, which token) comes from the httpOnly cookie set at login. This
  // prevents a different tab/user from triggering reset with stolen values.
  const cookieEmail = store.get('99accs_reset_email')?.value;
  const cookieToken = store.get('99accs_reset_token')?.value;

  if (!cookieEmail || !cookieToken) {
    return NextResponse.json({ message: 'Reset session expired. Please log in again.' }, { status: 401 });
  }
  // Client must echo back the token they were given (defense in depth).
  if (body.reset_token && body.reset_token !== cookieToken) {
    return NextResponse.json({ message: 'Invalid reset token.' }, { status: 422 });
  }

  const { password, password_confirmation } = body;
  if (!password || !password_confirmation) {
    return NextResponse.json({ message: 'Password is required.' }, { status: 422 });
  }
  if (password.length < 10) {
    return NextResponse.json({ message: 'Password must be at least 10 characters.' }, { status: 422 });
  }
  if (password !== password_confirmation) {
    return NextResponse.json({ message: 'Passwords do not match.' }, { status: 422 });
  }
  // Mock: real backend would hash_equals(stored_hash, sha256(token)) + check
  // expires_at > now(), then delete the row (single-use).
  if (!cookieToken.startsWith('mock_reset_')) {
    return NextResponse.json({ message: 'Invalid or expired reset token.' }, { status: 422 });
  }

  // Reset successful — clear reset cookies, issue real auth cookie.
  store.delete('99accs_reset_token');
  store.delete('99accs_reset_email');
  const token = 'mock_token_' + Math.random().toString(36).slice(2);
  store.set('99accs_token', token, { ...COOKIE_BASE, maxAge: 60 * 60 * 24 * 30 });

  return NextResponse.json({
    data: {
      token,
      user: { id: 1, name: 'Migrated User', email: cookieEmail, created_at: '2025-01-01T00:00:00Z' },
    },
  });
}
