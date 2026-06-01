import { NextRequest, NextResponse } from 'next/server';
import { laravelFetch } from '@/lib/auth/laravelClient';
import { readToken } from '@/lib/auth/cookies';

interface CheckoutItem {
  product_id: number;
  quantity: number;
}

interface CheckoutBody {
  items?: CheckoutItem[];
  note?: string;
  // Guest fields — required when the user isn't authed
  email?: string;
  phone?: string;
  first_name?: string;
  last_name?: string;
}

interface LaravelSuccess {
  data: {
    id: string;             // checkout_token (UUID)
    order_number: string;
    is_guest_checkout: boolean;
    customer_email: string;
    [k: string]: unknown;
  };
}

interface LaravelError {
  code?: string;
  message?: string;
  errors?: Record<string, string[]>;
}

/**
 * POST /api/checkout — proxies to Laravel /api/v1/checkout.
 *
 * Forwards the bearer token cookie when the caller is authed (so the order
 * is associated with the existing customer). For guests, the cookie is
 * absent and the body must include email/phone/first_name so Laravel can
 * auto-create the Customer + dispatch the password-setup email.
 */
export async function POST(req: NextRequest) {
  const token = await readToken();
  const body = (await req.json().catch(() => ({}))) as CheckoutBody;

  if (!Array.isArray(body.items) || body.items.length === 0) {
    return NextResponse.json(
      { code: 'EMPTY_CART', message: 'Your cart is empty.' },
      { status: 422 },
    );
  }

  const res = await laravelFetch<LaravelSuccess | LaravelError>('/checkout', {
    method: 'POST',
    body: JSON.stringify(body),
    token,
  });

  if (!res.ok) {
    const err = res.body as LaravelError;
    return NextResponse.json(
      {
        code: err.code ?? 'CHECKOUT_FAILED',
        message: err.message ?? 'Could not start checkout.',
        errors: err.errors,
      },
      { status: res.status },
    );
  }

  return NextResponse.json(res.body, { status: 201 });
}
