import { NextRequest, NextResponse } from 'next/server';
import { hasAuth, getAuthUserId } from '@/lib/auth/server';
import { getSession, markProcessing } from '@/lib/mock/checkoutSessions';

// POST /api/mock/checkout/{id}/pay — Returns a fake Stripe client secret.
// In production the Laravel backend creates a PaymentIntent on Stripe and
// returns its client_secret + the publishable key. The frontend then calls
// stripe.confirmPayment({clientSecret, ...}). The mock here just returns a
// plausible-shaped payload so the client wiring can be tested.
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
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
  if (session.status !== 'pending') {
    return NextResponse.json({ message: 'Session already paid.' }, { status: 409 });
  }
  if (!session.payment_method) {
    return NextResponse.json({ message: 'Choose a payment method first.' }, { status: 422 });
  }

  markProcessing(id);

  // Mock client_secret. Real Stripe shape: pi_<id>_secret_<rand>.
  const stub = `pi_${id.replace(/-/g, '').slice(0, 16)}_secret_mock${Date.now().toString(36)}`;
  return NextResponse.json({
    client_secret: stub,
    publishable_key: 'pk_test_mock_99accs',
    session_id: id,
  });
}
