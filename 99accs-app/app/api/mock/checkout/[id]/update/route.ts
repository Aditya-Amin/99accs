import { NextRequest, NextResponse } from 'next/server';
import { hasAuth, getAuthUserId } from '@/lib/auth/server';
import { envelope, getSession, updateSession, type UpdateSessionInput } from '@/lib/mock/checkoutSessions';

// POST /api/mock/checkout/{id}/update — Patch any subset of fields and
// receive the recomputed envelope. Body fields are all optional; absent
// fields are left untouched.
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  if (!(await hasAuth(req))) {
    return NextResponse.json({ message: 'Unauthenticated' }, { status: 401 });
  }
  const { id } = await params;
  const existing = getSession(id);
  if (!existing) return NextResponse.json({ message: 'Session not found.' }, { status: 404 });

  const userId = await getAuthUserId(req);
  if (userId !== null && existing.user_id !== userId) {
    return NextResponse.json({ message: 'Not authorized.' }, { status: 403 });
  }
  if (existing.status === 'expired') {
    return NextResponse.json({ message: 'Session expired.' }, { status: 410 });
  }
  if (existing.status !== 'pending') {
    return NextResponse.json({ message: 'Session already paid.' }, { status: 409 });
  }

  const body = (await req.json().catch(() => ({}))) as UpdateSessionInput;
  const updated = updateSession(id, body);
  if (!updated) return NextResponse.json({ message: 'Session not found.' }, { status: 404 });

  return NextResponse.json(envelope(updated));
}
