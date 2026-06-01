import { NextRequest, NextResponse } from 'next/server';
import { laravelFetch } from '@/lib/auth/laravelClient';
import { persistSession, type SessionUser } from '@/lib/auth/cookies';

interface LoginBody {
  email?: string;
  password?: string;
}

interface LaravelSuccess {
  data: { token: string; user: SessionUser };
}

interface LaravelLegacyError {
  code: 'LEGACY_PASSWORD_RESET_REQUIRED';
  message: string;
  email: string;
}

interface LaravelGenericError {
  code?: string;
  message?: string;
  errors?: Record<string, string[]>;
}

export async function POST(req: NextRequest) {
  const body = (await req.json().catch(() => ({}))) as LoginBody;
  const email = body.email?.trim().toLowerCase();
  if (!email || !body.password) {
    return NextResponse.json(
      { code: 'INVALID_CREDENTIALS', message: 'Email and password are required.' },
      { status: 400 },
    );
  }

  const res = await laravelFetch<LaravelSuccess | LaravelLegacyError | LaravelGenericError>('/login', {
    method: 'POST',
    body: JSON.stringify({ email, password: body.password }),
  });

  // Legacy migration path — Laravel already emailed a reset link. We do NOT
  // set any blocking cookie: the user stays a guest, sees the reset notice
  // inline, and can keep browsing the site until they reset + log in.
  if (res.status === 409 && (res.body as LaravelLegacyError).code === 'LEGACY_PASSWORD_RESET_REQUIRED') {
    const legacy = res.body as LaravelLegacyError;
    return NextResponse.json(
      {
        code: legacy.code,
        message: legacy.message,
        email: legacy.email,
      },
      { status: 409 },
    );
  }

  if (!res.ok) {
    const err = res.body as LaravelGenericError;
    return NextResponse.json(
      {
        code: err.code ?? 'LOGIN_FAILED',
        message: err.message ?? 'Login failed. Please try again.',
        errors: err.errors,
      },
      { status: res.status },
    );
  }

  const ok = res.body as LaravelSuccess;
  await persistSession(ok.data.token, ok.data.user);
  // Token never leaves the BFF — return only the user shape.
  return NextResponse.json({ data: { user: ok.data.user } });
}
