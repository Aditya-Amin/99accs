import { NextRequest, NextResponse } from 'next/server';
import dashboard from '@/mocks/account/dashboard.json';
import { hasAuth, isDemoUser } from '@/lib/auth/server';

export async function GET(req: NextRequest) {
  if (!(await hasAuth(req))) return NextResponse.json({ message: 'Unauthenticated' }, { status: 401 });
  const isDemo = await isDemoUser(req);
  const data = isDemo ? dashboard.recent_orders : [];
  return NextResponse.json({
    data,
    meta: { current_page: 1, last_page: 1, per_page: 10, total: data.length },
    links: { first: null, last: null, next: null, prev: null },
  });
}
