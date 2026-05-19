import { NextResponse } from 'next/server';

export async function POST() {
  // Always return the same shape regardless of whether the email exists,
  // so the response can't be used to enumerate accounts.
  return NextResponse.json({
    message: 'If an account exists for that email, a reset link is on its way.',
  });
}
