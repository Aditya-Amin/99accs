import { NextRequest, NextResponse } from 'next/server';
import type { SupportTicketMessage } from '@/lib/api/types';
import { allocateMessageId, getById, requireUserId } from '../../_store';

interface Params { params: Promise<{ id: string }> }

// POST /api/mock/support/tickets/:id/replies  { body, close_ticket? }
export async function POST(req: NextRequest, { params }: Params) {
  const userId = await requireUserId();
  if (userId === null) return NextResponse.json({ message: 'Unauthenticated' }, { status: 401 });

  const { id } = await params;
  const ticket = getById(parseInt(id, 10));
  if (!ticket || ticket.user_id !== userId) {
    return NextResponse.json({ message: 'Not found' }, { status: 404 });
  }

  const body = (await req.json().catch(() => null)) as { body?: string; close_ticket?: boolean } | null;
  if (!body?.body) {
    return NextResponse.json({ message: 'Validation failed', errors: { body: ['required'] } }, { status: 422 });
  }

  const now = new Date().toISOString();
  const message: SupportTicketMessage = {
    id: allocateMessageId(),
    ticket_id: ticket.id,
    is_owner: true,
    author_name: 'You',
    author_avatar: '/img/images/comment_avatar02.png',
    body: body.body,
    created_at: now,
  };

  ticket.messages = [...(ticket.messages ?? []), message];
  ticket.reply_count += 1;
  ticket.last_reply_at = now;
  if (body.close_ticket) ticket.status = 'closed';
  else if (ticket.status === 'new') ticket.status = 'open';

  return NextResponse.json({ data: message }, { status: 201 });
}
