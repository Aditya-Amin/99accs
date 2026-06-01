import { NextRequest, NextResponse } from 'next/server';
import { laravelFetch } from '@/lib/auth/laravelClient';
import { readToken } from '@/lib/auth/cookies';

interface ChangeBody {
  current_password?: string;
  password?: string;
  password_confirmation?: string;
}

export async function POST(req: NextRequest) {
  const token = await readToken();
  if (!token) {
    return NextResponse.json(
      { code: 'UNAUTHENTICATED', message: 'You must be signed in to change your password.' },
      { status: 401 },
    );
  }

  const body = (await req.json().catch(() => ({}))) as ChangeBody;
  if (!body.current_password || !body.password) {
    return NextResponse.json(
      { code: 'INVALID_INPUT', message: 'Current and new passwords are required.' },
      { status: 400 },
    );
  }

  const res = await laravelFetch<{ message: string }>('/auth/password/change', {
    method: 'POST',
    body: JSON.stringify({
      current_password: body.current_password,
      password: body.password,
      password_confirmation: body.password_confirmation ?? body.password,
    }),
    token,
  });

  return NextResponse.json(res.body, { status: res.status });
}
