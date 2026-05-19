import { NextRequest, NextResponse } from 'next/server';
import type { SupportTicket, SupportTicketMessage, SupportTicketStatus } from '@/lib/api/types';
import { allocateMessageId, allocateTicketId, getAll, replaceAll, requireUserId } from './_store';

// GET /api/mock/support/tickets?status=new|open|closed&game=valorant&search=...
export async function GET(req: NextRequest) {
  const userId = await requireUserId();
  if (userId === null) return NextResponse.json({ message: 'Unauthenticated' }, { status: 401 });

  const { searchParams } = req.nextUrl;
  const status = searchParams.get('status') as SupportTicketStatus | null;
  const game = searchParams.get('game');
  const search = searchParams.get('search');
  const page = parseInt(searchParams.get('page') ?? '1');
  const perPage = parseInt(searchParams.get('per_page') ?? '20');

  let results = getAll().filter((t) => t.user_id === userId);
  if (status) results = results.filter((t) => t.status === status);
  if (game) results = results.filter((t) => t.game === game);
  if (search) {
    const q = search.toLowerCase();
    results = results.filter(
      (t) => t.subject.toLowerCase().includes(q) || t.preview.toLowerCase().includes(q),
    );
  }

  results = [...results].sort((a, b) => b.created_at.localeCompare(a.created_at));

  const total = results.length;
  const lastPage = Math.max(1, Math.ceil(total / perPage));
  const offset = (page - 1) * perPage;
  // Strip messages from the list response — they're only returned by the detail route.
  const data = results.slice(offset, offset + perPage).map(({ messages: _messages, ...rest }) => rest);

  return NextResponse.json({
    data,
    meta: { current_page: page, last_page: lastPage, per_page: perPage, total },
    links: {
      first: null,
      last: null,
      next: page < lastPage ? `?page=${page + 1}` : null,
      prev: page > 1 ? `?page=${page - 1}` : null,
    },
  });
}

// 4-letter + 2-digit suffix to match the seed format (`#AAAA12`). Cheap
// random — fine for a mock. Laravel will use a unique-checked generator
// (see API_GUIDE.md).
function generateOrderNumber(): string {
  const ALPHA = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
  let letters = '';
  for (let i = 0; i < 4; i++) letters += ALPHA[Math.floor(Math.random() * 26)];
  const digits = String(Math.floor(Math.random() * 90 + 10));
  return `#${letters}${digits}`;
}

// POST /api/mock/support/tickets  { subject, body, game }
// `order_number` is generated server-side — the client never supplies it.
export async function POST(req: NextRequest) {
  const userId = await requireUserId();
  if (userId === null) return NextResponse.json({ message: 'Unauthenticated' }, { status: 401 });

  const body = (await req.json().catch(() => null)) as {
    subject?: string;
    body?: string;
    game?: 'valorant' | 'fortnite' | 'legends';
  } | null;

  if (!body?.subject || !body?.body || !body?.game) {
    return NextResponse.json(
      { message: 'Validation failed', errors: { subject: ['required'], body: ['required'], game: ['required'] } },
      { status: 422 },
    );
  }

  const now = new Date().toISOString();
  const id = allocateTicketId();
  const openingMessage: SupportTicketMessage = {
    id: allocateMessageId(),
    ticket_id: id,
    is_owner: true,
    author_name: 'You',
    author_avatar: '/img/images/comment_avatar02.png',
    body: body.body,
    is_opening: true,
    created_at: now,
  };

  const ticket: SupportTicket = {
    id,
    ticket_number: `#${String(10000 + id).padStart(5, '0')}`,
    user_id: userId,
    game: body.game,
    order_number: generateOrderNumber(),
    subject: body.subject,
    preview: body.body.slice(0, 200),
    status: 'new',
    reply_count: 0,
    created_at: now,
    last_reply_at: null,
    messages: [openingMessage],
  };

  replaceAll([ticket, ...getAll()]);

  return NextResponse.json({ data: ticket }, { status: 201 });
}
