import { notFound } from 'next/navigation';
import { headers } from 'next/headers';
import Link from 'next/link';
import { PageBreadcrumb } from '@/components/ui/PageBreadcrumb';

interface OrderItem {
  id: number;
  title: string;
  image: string | null;
  price: number;
  quantity: number;
  subtotal: number;
}

interface OrderData {
  id: string;
  order_number: string;
  status: string;
  payment_status: string;
  subtotal: number;
  total: number;
  customer_email: string | null;
  is_guest_checkout: boolean;
  items: OrderItem[];
}

async function fetchOrder(token: string): Promise<OrderData | null> {
  const hdrs = await headers();
  const proto = hdrs.get('x-forwarded-proto') ?? 'http';
  const host  = hdrs.get('host') ?? 'localhost:3000';
  const url   = `${proto}://${host}/api/order/${encodeURIComponent(token)}`;

  const res = await fetch(url, { cache: 'no-store' });
  if (!res.ok) return null;

  const json = (await res.json()) as { data: OrderData };
  return json.data ?? null;
}

interface Props {
  params: Promise<{ token: string }>;
}

export default async function OrderReceivedPage({ params }: Props) {
  const { token } = await params;
  const order = await fetchOrder(token);
  if (!order) notFound();

  const fmt = (amount: number) =>
    '$' + amount.toFixed(2);

  return (
    <main className="main-area fix">
      <PageBreadcrumb
        title="Order Confirmed"
        crumbs={[{ label: 'Home', href: '/' }, { label: 'Shop', href: '/product-category/valorant' }]}
      />

      <section className="orv__area section-pb-130">
        <div className="container">
          <div className="row g-4">

            {/* ── Left column: success + steps ──────────────────────── */}
            <div className="col-lg-7">

              {/* Hero */}
              <div className="orv__hero">
                <div className="orv__check-wrap" aria-hidden="true">
                  <div className="orv__check-ring" />
                  <svg width="72" height="72" viewBox="0 0 72 72" fill="none">
                    <circle
                      cx="36" cy="36" r="34"
                      fill="rgba(0,252,112,0.1)"
                      stroke="var(--tg-theme-primary)"
                      strokeWidth="2"
                    />
                    <path
                      d="M22 37l11 11 17-20"
                      stroke="var(--tg-theme-primary)"
                      strokeWidth="3.5"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    />
                  </svg>
                </div>

                <h1 className="orv__heading">Order Received!</h1>
                <p className="orv__sub">Your order has been confirmed and is being processed.</p>

                <div className="orv__order-pill">
                  <span className="orv__pill-label">Order ID</span>
                  <span className="orv__pill-value">{order.order_number}</span>
                </div>
              </div>

              {/* Email notice — guest only */}
              {order.is_guest_checkout && order.customer_email && (
                <div className="orv__email-card">
                  <img
                    className="orv__email-img"
                    src="/img/icons/features_icon01.png"
                    alt=""
                    aria-hidden="true"
                  />
                  <div className="orv__email-text">
                    <p>
                      We&apos;ve sent a secure setup link to{' '}
                      <strong>{order.customer_email}</strong>.
                      Open it to set your password and access your account.
                    </p>
                    <p className="orv__resend">
                      Didn&apos;t get it? Check spam, or{' '}
                      <Link href="/forgot-password">request another link</Link>.
                    </p>
                  </div>
                </div>
              )}

              {/* What happens next */}
              <div className="orv__steps">
                <div className="orv__step">
                  <img className="orv__step-img" src="/img/icons/features_icon01.png" alt="" />
                  <span className="orv__step-num">01</span>
                  <h3 className="orv__step-title">Check your inbox</h3>
                  <p className="orv__step-desc">Your receipt and account details are on the way.</p>
                </div>
                <div className="orv__step">
                  <img className="orv__step-img" src="/img/icons/features_icon02.png" alt="" />
                  <span className="orv__step-num">02</span>
                  <h3 className="orv__step-title">Set your password</h3>
                  <p className="orv__step-desc">Use the link to secure your 99Accs account.</p>
                </div>
                <div className="orv__step">
                  <img className="orv__step-img" src="/img/icons/features_icon03.png" alt="" />
                  <span className="orv__step-num">03</span>
                  <h3 className="orv__step-title">Get your credentials</h3>
                  <p className="orv__step-desc">Track your order and receive your game account.</p>
                </div>
              </div>

              {/* Actions */}
              <div className="orv__actions">
                <Link href="/product-category/valorant" className="tg-btn">Continue Shopping</Link>
                <Link
                  href={`/login?redirect=${encodeURIComponent('/account/orders')}`}
                  className="orv__signin-btn"
                >
                  Sign In to My Account
                </Link>
              </div>
            </div>

            {/* ── Right column: order summary ─────────────────────────── */}
            <div className="col-lg-5">
              <div className="orv__summary">
                <h2 className="orv__summary-title">Order Summary</h2>

                <ul className="orv__items list-wrap">
                  {order.items.map((item) => (
                    <li key={item.id} className="orv__item">
                      <div className="orv__item-thumb">
                        {item.image ? (
                          <img src={item.image} alt={item.title} />
                        ) : (
                          <div className="orv__item-thumb-fallback" aria-hidden="true" />
                        )}
                      </div>
                      <div className="orv__item-body">
                        <p className="orv__item-title">{item.title}</p>
                        {item.quantity > 1 && (
                          <span className="orv__item-qty">×{item.quantity}</span>
                        )}
                      </div>
                      <span className="orv__item-price">{fmt(item.subtotal)}</span>
                    </li>
                  ))}
                </ul>

                <div className="orv__totals">
                  <div className="orv__totals-row">
                    <span>Subtotal</span>
                    <span>{fmt(order.subtotal)}</span>
                  </div>
                  <div className="orv__totals-grand">
                    <span>Total</span>
                    <span>{fmt(order.total)}</span>
                  </div>
                </div>

                <div className="orv__summary-status">
                  <span className="orv__status-dot" />
                  <span>
                    {order.status === 'paid' ? 'Payment confirmed' : 'Awaiting payment'}
                  </span>
                </div>
              </div>
            </div>

          </div>
        </div>
      </section>
    </main>
  );
}
