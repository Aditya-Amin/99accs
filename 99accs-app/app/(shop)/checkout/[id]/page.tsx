import { notFound, redirect } from 'next/navigation';
import { cookies, headers } from 'next/headers';
import Link from 'next/link';
import { PageBreadcrumb } from '@/components/ui/PageBreadcrumb';
import CheckoutClient from '@/components/checkout/CheckoutClient';
import type { SessionEnvelope } from '@/lib/mock/checkoutSessions';

// Public payment page — gated by the unguessable checkout_token UUID, not auth,
// so guests can complete payment too. Server-fetches the initial session
// envelope from /api/checkout/[id] (which proxies to Laravel).
async function fetchInitial(id: string): Promise<{
  envelope?: SessionEnvelope;
  status: 'ok' | 'not_found' | 'expired' | 'paid' | 'forbidden';
}> {
  const cookieStore = await cookies();
  const hdrs = await headers();
  const proto = hdrs.get('x-forwarded-proto') ?? 'http';
  const host = hdrs.get('host') ?? 'localhost:3000';
  const url = `${proto}://${host}/api/checkout/${encodeURIComponent(id)}`;

  const res = await fetch(url, {
    headers: { cookie: cookieStore.toString() },
    cache: 'no-store',
  });
  if (res.status === 404) return { status: 'not_found' };
  if (res.status === 403) return { status: 'forbidden' };
  if (res.status === 410) return { status: 'expired' };
  if (res.status === 409) return { status: 'paid' };
  if (!res.ok) return { status: 'not_found' };
  const envelope = (await res.json()) as SessionEnvelope;
  return { envelope, status: 'ok' };
}

interface PageProps {
  params: Promise<{ id: string }>;
}

export default async function CheckoutSessionPage({ params }: PageProps) {
  const { id } = await params;
  const result = await fetchInitial(id);

  if (result.status === 'not_found') notFound();
  if (result.status === 'paid') redirect(`/order/${id}/received`);
  // GET now returns 200 for paid/processing too — redirect based on the
  // envelope's own status so users can't edit a session they've already paid.
  if (result.envelope && result.envelope.session.status !== 'pending') {
    redirect(`/order/${id}/received`);
  }
  if (result.status === 'forbidden') {
    return (
      <main className="main-area fix">
        <PageBreadcrumb title="Checkout" />
        <section className="checkout__area section-pb-130">
          <div className="container text-center" style={{ padding: '60px 0' }}>
            <h3 style={{ marginBottom: 16 }}>Not authorized</h3>
            <p style={{ opacity: 0.7, marginBottom: 24 }}>
              This checkout session belongs to a different account.
            </p>
            <Link href="/cart" className="tg-btn">Back to cart</Link>
          </div>
        </section>
      </main>
    );
  }
  if (result.status === 'expired') {
    return (
      <main className="main-area fix">
        <PageBreadcrumb title="Checkout" />
        <section className="checkout__area section-pb-130">
          <div className="container text-center" style={{ padding: '60px 0' }}>
            <h3 style={{ marginBottom: 16 }}>This session has expired</h3>
            <p style={{ opacity: 0.7, marginBottom: 24 }}>
              Start over from your cart to lock in current pricing.
            </p>
            <Link href="/cart" className="tg-btn">Back to cart</Link>
          </div>
        </section>
      </main>
    );
  }

  return (
    <main className="main-area fix">
      <PageBreadcrumb title="Checkout" />
      <section className="checkout__area section-pb-130">
        <div className="container">
          <CheckoutClient initial={result.envelope!} sessionId={id} />
        </div>
      </section>
    </main>
  );
}
