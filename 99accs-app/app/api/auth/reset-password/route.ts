import { NextRequest, NextResponse } from 'next/server';
import { laravelFetch } from '@/lib/auth/laravelClient';
import { persistSession, type SessionUser } from '@/lib/auth/cookies';

interface ResetBody {
  token?: string;
  email?: string;
  password?: string;
  password_confirmation?: string;
}

interface LaravelSuccess {
  data: { token: string; user: SessionUser };
}

interface LaravelError {
  code?: string;
  message?: string;
  errors?: Record<string, string[]>;
}

export async function POST(req: NextRequest) {
  const body = (await req.json().catch(() => ({}))) as ResetBody;
  if (!body.token || !body.email || !body.password) {
    return NextResponse.json(
      { code: 'INVALID_INPUT', message: 'Token, email, and password are required.' },
      { status: 400 },
    );
  }

  const res = await laravelFetch<LaravelSuccess | LaravelError>('/reset-password', {
    method: 'POST',
    body: JSON.stringify({
      token: body.token,
      email: body.email.trim().toLowerCase(),
      password: body.password,
      password_confirmation: body.password_confirmation ?? body.password,
    }),
  });

  if (!res.ok) {
    const err = res.body as LaravelError;
    return NextResponse.json(
      {
        code: err.code ?? 'RESET_FAILED',
        message: err.message ?? 'Could not reset password.',
        errors: err.errors,
      },
      { status: res.status },
    );
  }

  const ok = res.body as LaravelSuccess;
  // Laravel returns a fresh Sanctum token after successful reset so the user
  // is logged in immediately — persist it and clear any reset-flow cookies.
  await persistSession(ok.data.token, ok.data.user);
  return NextResponse.json({ data: { user: ok.data.user } });
}
