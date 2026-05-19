import { NextRequest, NextResponse } from 'next/server';
import { cookies } from 'next/headers';
import { issueSession } from '@/lib/auth/setSession';

interface LoginBody {
  email?: string;
  password?: string;
}

// Demo: any email containing "migrated" simulates a legacy-migrated user who must reset.
// Real Laravel: `if ($user->must_reset_password) { return $forcedResetResponse; }` in AuthService.
function isMigratedUser(email: string): boolean {
  return /migrated/i.test(email);
}

const COOKIE_BASE = {
  httpOnly: true as const,
  secure: process.env.NODE_ENV === 'production',
  sameSite: 'lax' as const,
  path: '/',
};

export async function POST(req: NextRequest) {
  const body = (await req.json().catch(() => ({}))) as LoginBody;
  const email = body.email?.trim().toLowerCase();
  if (!email || !body.password) {
    return NextResponse.json({ message: 'Invalid credentials.' }, { status: 401 });
  }

  if (isMigratedUser(email)) {
    const store = await cookies();
    const resetToken = 'mock_reset_' + Math.random().toString(36).slice(2) + Math.random().toString(36).slice(2);
    const FIFTEEN_MIN = 60 * 15;
    store.set('99accs_reset_token', resetToken, { ...COOKIE_BASE, maxAge: FIFTEEN_MIN });
    store.set('99accs_reset_email', email, { ...COOKIE_BASE, maxAge: FIFTEEN_MIN });
    // Defensive: a migrated user must NOT hold an auth token until reset.
    store.delete('99accs_token');
    store.delete('99accs_user');
    return NextResponse.json({
      must_reset_password: true,
      reset_token: resetToken,
      email,
    });
  }

  // Normal path — issue a session keyed on the supplied email. Demo email
  // gets the seeded user (id 1 + Demo User + existing mock data); any other
  // email gets a fresh user id with no data attached anywhere.
  const session = await issueSession(email);
  return NextResponse.json({
    data: { token: session.token, user: session.user },
  });
}
