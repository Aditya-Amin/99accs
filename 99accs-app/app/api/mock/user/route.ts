import { NextRequest, NextResponse } from 'next/server';
import { getCurrentUser } from '@/lib/auth/server';

// Returns the authenticated user, or `{ data: null }` if no session.
//
// Note: we deliberately return 200 + null instead of 401 for the "no session"
// case so the browser doesn't log a console error on every guest page load
// (AuthHydrator polls this from the root layout). Real Laravel /api/auth/me
// uses 401 and the client will translate that to the same null-user state.
export async function GET(req: NextRequest) {
  const user = await getCurrentUser(req);
  return NextResponse.json({ data: user });
}
