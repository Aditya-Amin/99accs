import { NextRequest, NextResponse } from 'next/server';
import { readToken } from '@/lib/auth/cookies';
import { laravelFetch } from '@/lib/auth/laravelClient';

interface Params { params: Promise<{ id: string }> }

// POST /api/support/tickets/:id/replies — append a reply { body, close_ticket? } → Laravel
export async function POST(req: NextRequest, { params }: Params) {
  const token = await readToken();
  if (!token) {
    return NextResponse.json({ message: 'Unauthenticated.' }, { status: 401 });
  }

  const { id } = await params;
  const body = await req.text();
  const res = await laravelFetch(`/support/tickets/${id}/replies`, { method: 'POST', token, body });

  return NextResponse.json(res.body, { status: res.status });
}
