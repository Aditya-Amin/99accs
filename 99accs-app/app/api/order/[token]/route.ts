import { NextRequest, NextResponse } from 'next/server';
import { laravelFetch } from '@/lib/auth/laravelClient';
import { readToken } from '@/lib/auth/cookies';

export async function GET(
  _req: NextRequest,
  { params }: { params: Promise<{ token: string }> },
) {
  const { token } = await params;
  const authToken = await readToken();

  const res = await laravelFetch(`/checkout/${encodeURIComponent(token)}`, {
    token: authToken ?? undefined,
  });

  return NextResponse.json(res.body, { status: res.status });
}
