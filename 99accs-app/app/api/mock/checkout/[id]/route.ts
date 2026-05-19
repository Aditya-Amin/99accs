import { NextRequest, NextResponse } from 'next/server';
import { hasAuth, getAuthUserId } from '@/lib/auth/server';
import { envelope, getSession } from '@/lib/mock/checkoutSessions';

// GET /api/mock/checkout/{id} — Return the full session envelope.
//
// 401 unauthenticated · 403 not owner · 404 not found · 410 expired · 409 already paid
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  if (!(await hasAuth(req))) {
    return NextResponse.json({ message: 'Unauthenticated' }, { status: 401 });
  }
  const { id } = await params;
  const session = getSession(id);
  if (!session) return NextResponse.json({ message: 'Session not found.' }, { status: 404 });

  const userId = await getAuthUserId(req);
  if (userId !== null && session.user_id !== userId) {
    return NextResponse.json({ message: 'Not authorized.' }, { status: 403 });
  }
  if (session.status === 'expired') {
    return NextResponse.json({ message: 'Session expired.' }, { status: 410 });
  }
  // Note: paid/processing sessions are still returned via GET so the order
  // confirmation page can read them. `update` and `pay` still reject them.
  return NextResponse.json(envelope(session));
}
