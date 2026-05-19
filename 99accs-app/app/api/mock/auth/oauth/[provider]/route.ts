import { NextRequest, NextResponse } from 'next/server';
import { issueSession } from '@/lib/auth/setSession';

interface OAuthBody {
  email?: string;
  name?: string;
}

const ALLOWED_PROVIDERS = new Set(['google', 'facebook']);

// Mock OAuth callback. Replaces the real Sign in with Google / Facebook flow
// for local dev: the client collects an email in a simulated consent dialog
// and POSTs it here. Real Laravel will exchange an OAuth `code` from Google's
// redirect for an id token, verify it, then call issueSession with the
// verified email — the rest of the app is identical.
export async function POST(req: NextRequest, { params }: { params: Promise<{ provider: string }> }) {
  const { provider } = await params;
  if (!ALLOWED_PROVIDERS.has(provider)) {
    return NextResponse.json({ message: 'Unknown OAuth provider.' }, { status: 404 });
  }

  const body = (await req.json().catch(() => ({}))) as OAuthBody;
  const email = body.email?.trim().toLowerCase();
  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    return NextResponse.json({ message: 'A valid email is required.' }, { status: 422 });
  }

  const session = await issueSession(email);
  if (body.name && session.user.email !== 'demo@99accs.com') {
    session.user.name = body.name.trim();
  }

  return NextResponse.json({
    data: { token: session.token, user: session.user, provider },
  });
}
