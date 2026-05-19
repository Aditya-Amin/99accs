import { NextRequest, NextResponse } from 'next/server';
import dashboard from '@/mocks/account/dashboard.json';
import { hasAuth, isDemoUser } from '@/lib/auth/server';

const EMPTY_DASHBOARD = {
  order_count: 0,
  wishlist_count: 0,
  total_spent: 0,
  recent_orders: [],
};

// Demo seed account sees the full mock dashboard; every other account starts
// with zeros + empty arrays so the UI shows a fresh-user state.
export async function GET(req: NextRequest) {
  if (!(await hasAuth(req))) return NextResponse.json({ message: 'Unauthenticated' }, { status: 401 });
  const isDemo = await isDemoUser(req);
  return NextResponse.json({ data: isDemo ? dashboard : EMPTY_DASHBOARD });
}
