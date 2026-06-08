import { NextRequest, NextResponse } from 'next/server';
import { readToken } from '@/lib/auth/cookies';
import { laravelFetch } from '@/lib/auth/laravelClient';

// BFF proxy: the browser holds only the httpOnly Sanctum cookie (JS can't read
// it), so client-side ticket mutations come here. We read the token server-side
// and forward to Laravel, relaying its status + body verbatim.

// POST /api/support/tickets — create a ticket { subject, body, game }
export async function POST(req: NextRequest) {
  const token = await readToken();
  if (!token) {
    return NextResponse.json({ message: 'Unauthenticated.' }, { status: 401 });
  }

  const body = await req.text();
  const res = await laravelFetch('/support/tickets', { method: 'POST', token, body });

  return NextResponse.json(res.body, { status: res.status });
}
