import { NextRequest, NextResponse } from 'next/server';
import { hasAuth } from '@/lib/auth/server';

export async function GET(req: NextRequest) {
  if (!(await hasAuth(req))) return NextResponse.json({ message: 'Unauthenticated' }, { status: 401 });
  return NextResponse.json({
    data: [],
    meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 },
    links: { first: null, last: null, next: null, prev: null },
  });
}

export async function POST(req: NextRequest) {
  if (!(await hasAuth(req))) return NextResponse.json({ message: 'Unauthenticated' }, { status: 401 });
  return NextResponse.json({ message: 'Item added to cart.' });
}

export async function DELETE(req: NextRequest) {
  if (!(await hasAuth(req))) return NextResponse.json({ message: 'Unauthenticated' }, { status: 401 });
  return NextResponse.json({ message: 'Cart cleared.' });
}
