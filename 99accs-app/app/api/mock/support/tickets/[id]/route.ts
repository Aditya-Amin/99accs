import { NextRequest, NextResponse } from 'next/server';
import type { SupportTicketStatus } from '@/lib/api/types';
import { getById, requireUserId } from '../_store';

interface Params { params: Promise<{ id: string }> }

// GET /api/mock/support/tickets/:id — full ticket with messages
export async function GET(_req: NextRequest, { params }: Params) {
  const userId = await requireUserId();
  if (userId === null) return NextResponse.json({ message: 'Unauthenticated' }, { status: 401 });

  const { id } = await params;
  const ticket = getById(parseInt(id, 10));
  if (!ticket || ticket.user_id !== userId) {
    return NextResponse.json({ message: 'Not found' }, { status: 404 });
  }
  return NextResponse.json({ data: ticket });
}

// PATCH /api/mock/support/tickets/:id  { status?: 'closed' | 'open' }
export async function PATCH(req: NextRequest, { params }: Params) {
  const userId = await requireUserId();
  if (userId === null) return NextResponse.json({ message: 'Unauthenticated' }, { status: 401 });

  const { id } = await params;
  const ticket = getById(parseInt(id, 10));
  if (!ticket || ticket.user_id !== userId) {
    return NextResponse.json({ message: 'Not found' }, { status: 404 });
  }

  const body = (await req.json().catch(() => null)) as { status?: SupportTicketStatus } | null;
  if (body?.status && (body.status === 'open' || body.status === 'closed' || body.status === 'new')) {
    ticket.status = body.status;
  }
  return NextResponse.json({ data: ticket });
}
