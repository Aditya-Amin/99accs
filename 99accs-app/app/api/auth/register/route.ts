import { NextRequest, NextResponse } from 'next/server';
import { laravelFetch } from '@/lib/auth/laravelClient';
import { persistSession, type SessionUser } from '@/lib/auth/cookies';

interface RegisterBody {
  name?: string;
  first_name?: string;
  last_name?: string;
  email?: string;
  password?: string;
  password_confirmation?: string;
  phone?: string;
}

interface LaravelSuccess {
  data: { token: string; user: SessionUser };
}

interface LaravelError {
  message?: string;
  errors?: Record<string, string[]>;
  code?: string;
}

export async function POST(req: NextRequest) {
  const body = (await req.json().catch(() => ({}))) as RegisterBody;

  const email = body.email?.trim().toLowerCase();
  if (!email || !body.password) {
    return NextResponse.json(
      { code: 'INVALID_INPUT', message: 'Email and password are required.' },
      { status: 400 },
    );
  }

  const res = await laravelFetch<LaravelSuccess | LaravelError>('/register', {
    method: 'POST',
    body: JSON.stringify({
      name: body.name,
      first_name: body.first_name,
      last_name: body.last_name,
      email,
      password: body.password,
      password_confirmation: body.password_confirmation ?? body.password,
      phone: body.phone,
    }),
  });

  if (!res.ok) {
    const err = res.body as LaravelError;
    return NextResponse.json(
      {
        code: err.code ?? 'REGISTER_FAILED',
        message: err.message ?? 'Registration failed.',
        errors: err.errors,
      },
      { status: res.status },
    );
  }

  const ok = res.body as LaravelSuccess;
  await persistSession(ok.data.token, ok.data.user);
  return NextResponse.json({ data: { user: ok.data.user } }, { status: 201 });
}
