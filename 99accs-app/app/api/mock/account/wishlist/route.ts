import { NextRequest, NextResponse } from 'next/server';
import valorant from '@/mocks/products/valorant.json';
import { hasAuth, isDemoUser } from '@/lib/auth/server';

const mockWishlist = valorant.slice(0, 3).map((p, i) => ({
  id: i + 1,
  product: p,
  created_at: '2025-03-01T00:00:00Z',
}));

export async function GET(req: NextRequest) {
  if (!(await hasAuth(req))) return NextResponse.json({ message: 'Unauthenticated' }, { status: 401 });
  const isDemo = await isDemoUser(req);
  const data = isDemo ? mockWishlist : [];
  return NextResponse.json({
    data,
    meta: { current_page: 1, last_page: 1, per_page: 10, total: data.length },
    links: { first: null, last: null, next: null, prev: null },
  });
}

export async function POST(req: NextRequest) {
  if (!(await hasAuth(req))) return NextResponse.json({ message: 'Unauthenticated' }, { status: 401 });
  const body = await req.json();
  const product = [...valorant].find((p) => p.id === body.product_id);
  if (!product) return NextResponse.json({ message: 'Product not found' }, { status: 404 });
  return NextResponse.json({ data: { id: Date.now(), product, created_at: new Date().toISOString() } });
}
