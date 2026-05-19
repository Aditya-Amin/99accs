import { NextRequest, NextResponse } from 'next/server';
import { issueSession } from '@/lib/auth/setSession';

interface RegisterBody {
  name?: string;
  email?: string;
  password?: string;
  password_confirmation?: string;
}

// Mock register: validates the minimum required fields, then issues a session
// keyed on the supplied email. Real Laravel does this in AuthController@register
// with a FormRequest (RegisterRequest).
export async function POST(req: NextRequest) {
  const body = (await req.json().catch(() => ({}))) as RegisterBody;
  const email = body.email?.trim().toLowerCase();
  if (!email || !body.password) {
    return NextResponse.json({ message: 'Email and password are required.' }, { status: 422 });
  }
  if (body.password.length < 8) {
    return NextResponse.json({ message: 'Password must be at least 8 characters.' }, { status: 422 });
  }
  if (body.password_confirmation && body.password !== body.password_confirmation) {
    return NextResponse.json({ message: 'Passwords do not match.' }, { status: 422 });
  }

  const session = await issueSession(email);
  // Prefer the caller-supplied name over the email-derived one (unless it's
  // the demo seed, which has its own fixed identity).
  if (body.name && session.user.email !== 'demo@99accs.com') {
    session.user.name = body.name.trim();
  }

  return NextResponse.json(
    { data: { token: session.token, user: session.user } },
    { status: 201 },
  );
}
