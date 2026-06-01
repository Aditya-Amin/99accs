import { NextRequest, NextResponse } from 'next/server';
import { laravelFetch } from '@/lib/auth/laravelClient';

interface ForgotBody {
  email?: string;
}

export async function POST(req: NextRequest) {
  const body = (await req.json().catch(() => ({}))) as ForgotBody;
  const email = body.email?.trim().toLowerCase();
  if (!email) {
    return NextResponse.json(
      { code: 'INVALID_INPUT', message: 'Email is required.' },
      { status: 400 },
    );
  }

  const res = await laravelFetch<{ message: string }>('/forgot-password', {
    method: 'POST',
    body: JSON.stringify({ email }),
  });

  // Pass the upstream status through (200 success, 429 rate-limited).
  // No cookies set here — reset flow happens via the email link.
  return NextResponse.json(res.body, { status: res.status });
}
