import { NextRequest, NextResponse } from 'next/server';
import { laravelFetch } from '@/lib/auth/laravelClient';
import { readToken } from '@/lib/auth/cookies';
import { toEnvelope, getOverlay, type LaravelCheckoutData } from '@/lib/checkout/laravelCheckout';

export async function GET(
  _req: NextRequest,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;
  const token = await readToken();

  const res = await laravelFetch<{ data: LaravelCheckoutData } | { message: string }>(
    `/checkout/${encodeURIComponent(id)}`,
    { token: token ?? undefined },
  );

  if (res.status === 404) return NextResponse.json({ message: 'Not found.' }, { status: 404 });
  if (!res.ok) return NextResponse.json({ message: 'Upstream error.' }, { status: res.status });

  const data = (res.body as { data: LaravelCheckoutData }).data;

  if (data.status === 'cancelled') return NextResponse.json({ message: 'Cancelled.' }, { status: 410 });
  if (data.payment_status === 'paid') return NextResponse.json({ message: 'Already paid.' }, { status: 409 });

  return NextResponse.json(toEnvelope(data, getOverlay(id)));
}
