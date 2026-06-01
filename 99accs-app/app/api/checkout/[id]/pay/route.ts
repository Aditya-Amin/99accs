import { NextRequest, NextResponse } from 'next/server';
import { laravelFetch } from '@/lib/auth/laravelClient';
import { readToken } from '@/lib/auth/cookies';

export async function POST(
  req: NextRequest,
  { params }: { params: Promise<{ id: string }> },
) {
  const { id } = await params;
  const token = await readToken();
  const body = (await req.json().catch(() => ({}))) as { payment_method?: string };

  if (!body.payment_method) {
    return NextResponse.json({ message: 'Payment method is required.' }, { status: 422 });
  }

  const res = await laravelFetch(
    `/checkout/${encodeURIComponent(id)}/pay`,
    {
      method: 'POST',
      token: token ?? undefined,
      body: JSON.stringify({ payment_method: body.payment_method }),
    },
  );

  if (!res.ok) {
    const err = res.body as { code?: string; message?: string };
    return NextResponse.json(
      { code: err?.code ?? 'PAY_FAILED', message: err?.message ?? 'Payment failed.' },
      { status: res.status },
    );
  }

  return NextResponse.json(res.body);
}
