import { NextRequest, NextResponse } from 'next/server';
import { readToken } from '@/lib/auth/cookies';
import { laravelFetch } from '@/lib/auth/laravelClient';

interface Params { params: Promise<{ id: string }> }

// GET /api/support/tickets/:id — full ticket + thread → Laravel.
// Used by the thread's 5s poll to pull new messages client-side.
export async function GET(_req: NextRequest, { params }: Params) {
  const token = await readToken();
  if (!token) {
    return NextResponse.json({ message: 'Unauthenticated.' }, { status: 401 });
  }

  const { id } = await params;
  const res = await laravelFetch(`/support/tickets/${id}`, { token });

  return NextResponse.json(res.body, { status: res.status });
}

// PATCH /api/support/tickets/:id — status change (e.g. close) → Laravel
export async function PATCH(req: NextRequest, { params }: Params) {
  const token = await readToken();
  if (!token) {
    return NextResponse.json({ message: 'Unauthenticated.' }, { status: 401 });
  }

  const { id } = await params;
  const body = await req.text();
  const res = await laravelFetch(`/support/tickets/${id}`, { method: 'PATCH', token, body });

  return NextResponse.json(res.body, { status: res.status });
}
