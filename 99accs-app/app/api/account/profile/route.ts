import { NextRequest, NextResponse } from 'next/server';
import { laravelFetch } from '@/lib/auth/laravelClient';
import { readToken } from '@/lib/auth/cookies';

export async function PATCH(req: NextRequest) {
  const token = await readToken();
  if (!token) {
    return NextResponse.json(
      { code: 'UNAUTHENTICATED', message: 'You must be signed in.' },
      { status: 401 },
    );
  }

  const body = await req.json().catch(() => ({}));
  const res = await laravelFetch('/account/profile', {
    method: 'PATCH',
    body: JSON.stringify(body),
    token,
  });

  return NextResponse.json(res.body, { status: res.status });
}
