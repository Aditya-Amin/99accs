import { NextRequest, NextResponse } from 'next/server';
import { laravelFetch } from '@/lib/auth/laravelClient';
import { readToken } from '@/lib/auth/cookies';
import {
  toEnvelope,
  patchOverlay,
  type LaravelCheckoutData,
  type CheckoutOverlay,
} from '@/lib/checkout/laravelCheckout';
import type { UpdateSessionInput } from '@/lib/mock/checkoutSessions';

export async function POST(
  req: NextRequest,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;
  const token = await readToken();
  const body = (await req.json().catch(() => ({}))) as UpdateSessionInput;

  // Build the overlay patch from only the fields the client explicitly sent.
  const patch: CheckoutOverlay = {};
  if (body.payment_method !== undefined) patch.payment_method = body.payment_method;
  if (body.lifetime_warranty !== undefined) patch.lifetime_warranty = body.lifetime_warranty;
  if (body.discount_code !== undefined) patch.discount_code = body.discount_code;
  const overlay = patchOverlay(id, patch);

  // Fetch fresh order data from Laravel for prices.
  const res = await laravelFetch<{ data: LaravelCheckoutData }>(
    `/checkout/${encodeURIComponent(id)}`,
    { token: token ?? undefined },
  );

  if (!res.ok) {
    const err = res.body as { message?: string };
    return NextResponse.json(
      { message: err?.message ?? 'Update failed.' },
      { status: res.status || 500 },
    );
  }

  const data = (res.body as { data: LaravelCheckoutData }).data;
  return NextResponse.json(toEnvelope(data, overlay));
}
