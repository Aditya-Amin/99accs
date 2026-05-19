import { NextRequest, NextResponse } from 'next/server';
import { hasAuth, getAuthUserId } from '@/lib/auth/server';
import { createSession, envelope, type CheckoutItem } from '@/lib/mock/checkoutSessions';

interface CreateBody {
  currency?: 'USD' | 'EUR';
  items?: Array<{
    id?: string | number;
    title?: string;
    image?: string;
    unit_price_cents?: number;
    quantity?: number;
    category?: string;
    delivery_type?: 'instant' | 'manual';
    warranty_days?: number;
    attributes?: Record<string, string | number>;
  }>;
}

// POST /api/mock/checkout — Create a checkout session from the (client-held)
// cart items. The client passes the line items so the server can take a
// snapshot. Real Laravel would lock prices server-side from the Product
// model — here we trust the input but compute fees + totals ourselves.
export async function POST(req: NextRequest) {
  if (!(await hasAuth(req))) {
    return NextResponse.json({ message: 'Unauthenticated' }, { status: 401 });
  }
  const userId = (await getAuthUserId(req)) ?? 1;

  const body = (await req.json().catch(() => ({}))) as CreateBody;
  if (!Array.isArray(body.items) || body.items.length === 0) {
    return NextResponse.json({ message: 'Cart is empty.' }, { status: 422 });
  }

  const items: CheckoutItem[] = body.items.map((it, idx) => ({
    id: String(it.id ?? idx),
    unit_price_cents: Math.max(0, Math.round(Number(it.unit_price_cents) || 0)),
    quantity: Math.max(1, Math.round(Number(it.quantity) || 1)),
    snapshot: {
      title: String(it.title ?? 'Item'),
      images: it.image ? [it.image] : [],
      category: String(it.category ?? 'account'),
      delivery_type: it.delivery_type === 'manual' ? 'manual' : 'instant',
      warranty_days: Math.max(0, Math.round(Number(it.warranty_days) || 14)),
      attributes: it.attributes,
    },
  }));

  try {
    const session = createSession({
      user_id: userId,
      currency: body.currency === 'EUR' ? 'EUR' : 'USD',
      items,
    });
    return NextResponse.json(envelope(session), { status: 201 });
  } catch (e) {
    return NextResponse.json(
      { message: e instanceof Error ? e.message : 'Failed to create session.' },
      { status: 422 }
    );
  }
}
