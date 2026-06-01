import Link from 'next/link';
import { notFound } from 'next/navigation';
import { cookies, headers } from 'next/headers';
import { PageBreadcrumb } from '@/components/ui/PageBreadcrumb';
import { formatMoney } from '@/components/checkout/money';
import type { SessionEnvelope } from '@/lib/mock/checkoutSessions';

interface Props {
  params: Promise<{ token: string }>;
}

// Server-fetches the (now paid/processing) checkout session that backs this
// order number. In production this would call /api/orders/{id} instead, but
// the mock store reuses the session object as the order record.
async function fetchOrder(id: string): Promise<SessionEnvelope | null> {
  const cookieStore = await cookies();
  const hdrs = await headers();
  const proto = hdrs.get('x-forwarded-proto') ?? 'http';
  const host = hdrs.get('host') ?? 'localhost:3000';
  const res = await fetch(`${proto}://${host}/api/mock/checkout/${encodeURIComponent(id)}`, {
    headers: { cookie: cookieStore.toString() },
    cache: 'no-store',
  });
  if (!res.ok) return null;
  return (await res.json()) as SessionEnvelope;
}

const PAYMENT_LABELS: Record<string, string> = {
  card: 'Debit/Credit cards',
  crypto: 'Crypto',
  apple_pay: 'Apple Pay',
  google_pay: 'Google Pay',
  paysafe: 'Paysafe Card',
  skrill: 'Skrill',
};

export default async function OrderConfirmationPage({ params }: Props) {
  const { token } = await params;
  const envelope = await fetchOrder(token);
  if (!envelope) notFound();
  const { session } = envelope;
  const currency = session.currency;
  const orderShort = token.split('-')[0].toUpperCase();

  return (
    <main className="main-area fix">
      <PageBreadcrumb title="Order Confirmed" />
      <section className="order-confirm__area section-pb-130">
        <div className="container">
          <div className="row justify-content-center">
            <div className="col-lg-9">
              <div className="order-confirm__card">
                <div className="order-confirm__icon" aria-hidden="true">
                  <svg width="56" height="56" viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="28" cy="28" r="26" fill="rgba(0,252,112,0.12)" stroke="var(--tg-theme-primary)" strokeWidth="2" />
                    <path d="M18 28.5l7 7 13-14" stroke="var(--tg-theme-primary)" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" fill="none" />
                  </svg>
                </div>
                <h1 className="order-confirm__title">Thank you for your order!</h1>
                <p className="order-confirm__sub">
                  Order <strong>#{orderShort}</strong> has been placed successfully. A receipt is on its way to your email.
                </p>

                <div className="order-confirm__meta">
                  <div>
                    <span className="order-confirm__meta-label">Order number</span>
                    <span className="order-confirm__meta-value">#{orderShort}</span>
                  </div>
                  <div>
                    <span className="order-confirm__meta-label">Date</span>
                    <span className="order-confirm__meta-value">
                      {new Date(session.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}
                    </span>
                  </div>
                  <div>
                    <span className="order-confirm__meta-label">Payment</span>
                    <span className="order-confirm__meta-value">
                      {session.payment_method ? PAYMENT_LABELS[session.payment_method] ?? session.payment_method : '—'}
                    </span>
                  </div>
                  <div>
                    <span className="order-confirm__meta-label">Status</span>
                    <span className="order-confirm__meta-value order-confirm__status">
                      {session.status === 'paid' ? 'Paid' : 'Processing'}
                    </span>
                  </div>
                </div>

                <div className="order-confirm__items">
                  <h2 className="order-confirm__items-title">Order summary</h2>
                  <ul className="order-items list-wrap">
                    {session.items.map((item) => {
                      const img = item.snapshot.images?.[0];
                      return (
                        <li key={item.id} className="order-items__row">
                          <div className="order-items__thumb">
                            {img ? (
                              <img src={img} alt={item.snapshot.title} />
                            ) : (
                              <div className="order-items__thumb-fallback" aria-hidden="true" />
                            )}
                          </div>
                          <div className="order-items__body">
                            <h3 className="order-items__title">{item.snapshot.title}</h3>
                            <ul className="order-items__meta list-wrap">
                              <li>
                                {item.snapshot.delivery_type === 'instant'
                                  ? '⚡ Instant delivery'
                                  : '📨 Manual delivery'}
                              </li>
                              {item.snapshot.warranty_days > 0 && (
                                <li>🛡 {item.snapshot.warranty_days}-days warranty</li>
                              )}
                            </ul>
                          </div>
                          <div className="order-items__price">
                            {formatMoney(item.unit_price_cents * item.quantity, currency)}
                          </div>
                        </li>
                      );
                    })}
                  </ul>

                  <ul className="order-confirm__totals list-wrap">
                    <li>
                      <span>Subtotal</span>
                      <span>{formatMoney(session.subtotal_cents, currency)}</span>
                    </li>
                    <li>
                      <span>Marketplace Fee</span>
                      <span>{formatMoney(session.marketplace_fee_cents, currency)}</span>
                    </li>
                    <li>
                      <span>Processor Fee</span>
                      <span>{formatMoney(session.processor_fee_cents, currency)}</span>
                    </li>
                    {session.warranty_fee_cents > 0 && (
                      <li>
                        <span>Lifetime Warranty</span>
                        <span>{formatMoney(session.warranty_fee_cents, currency)}</span>
                      </li>
                    )}
                    {session.discount_code_cents > 0 && (
                      <li className="order-confirm__totals-discount">
                        <span>Discount ({session.discount_code})</span>
                        <span>−{formatMoney(session.discount_code_cents, currency)}</span>
                      </li>
                    )}
                    <li className="order-confirm__totals-grand">
                      <span>Total paid</span>
                      <span>{formatMoney(session.total_cents, currency)}</span>
                    </li>
                  </ul>
                </div>

                <div className="order-confirm__next">
                  <h3 className="order-confirm__next-title">What happens next?</h3>
                  <ul className="order-confirm__next-list list-wrap">
                    <li>
                      <span className="order-confirm__next-dot" aria-hidden="true">1</span>
                      <div>
                        <strong>Check your email.</strong> We've sent your receipt and account credentials.
                      </div>
                    </li>
                    <li>
                      <span className="order-confirm__next-dot" aria-hidden="true">2</span>
                      <div>
                        <strong>Account access is instant.</strong> Log in using the credentials we just emailed.
                      </div>
                    </li>
                    <li>
                      <span className="order-confirm__next-dot" aria-hidden="true">3</span>
                      <div>
                        <strong>Need help?</strong> Our 24/7 support team is one click away.
                      </div>
                    </li>
                  </ul>
                </div>

                <div className="order-confirm__actions">
                  <Link href="/account/orders" className="tg-btn">View my orders</Link>
                  <Link href="/product-category/valorant" className="border-btn order-confirm__shop-btn">Continue shopping</Link>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>
  );
}
