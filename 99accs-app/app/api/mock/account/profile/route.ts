import { NextRequest, NextResponse } from 'next/server';
import { cookies } from 'next/headers';
import { getCurrentUser } from '@/lib/auth/server';

export async function GET(req: NextRequest) {
  const user = await getCurrentUser(req);
  if (!user) return NextResponse.json({ message: 'Unauthenticated' }, { status: 401 });
  return NextResponse.json({ data: user });
}

// PATCH rewrites the user cookie so the page reflects the change on next load.
// Real Laravel persists to the users table; the mock cookie is the closest
// equivalent in dev.
export async function PATCH(req: NextRequest) {
  const user = await getCurrentUser(req);
  if (!user) return NextResponse.json({ message: 'Unauthenticated' }, { status: 401 });

  const body = (await req.json().catch(() => ({}))) as { name?: string; email?: string };
  const updated = {
    ...user,
    name: body.name?.trim() || user.name,
    email: body.email?.trim().toLowerCase() || user.email,
  };

  const store = await cookies();
  store.set('99accs_user', JSON.stringify(updated), {
    httpOnly: true,
    secure: process.env.NODE_ENV === 'production',
    sameSite: 'lax',
    path: '/',
    maxAge: 60 * 60 * 24 * 30,
  });

  return NextResponse.json({ data: updated });
}
