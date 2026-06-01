'use client';
import { useEffect, useRef, useState } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { useCartStore } from '@/lib/store/cartStore';
import { useAuthStore } from '@/lib/store/authStore';
import { GuestContactForm, type GuestContact } from './GuestContactForm';
import { CartOrderSummary } from './CartOrderSummary';

interface CheckoutSuccess {
  data: {
    id: string;
    order_number: string;
    is_guest_checkout: boolean;
    customer_email: string;
  };
}

interface CheckoutError {
  code?: string;
  message?: string;
  email?: string;
}

export function CheckoutStarter() {
  const router = useRouter();
  const items = useCartStore((s) => s.items);
  const clearCart = useCartStore((s) => s.clearCart);
  const authStatus = useAuthStore((s) => s.status);

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [emailRequiresLogin, setEmailRequiresLogin] = useState<string | null>(null);

  const authedStartedRef = useRef(false);

  const startCheckout = async (contact?: GuestContact) => {
    setLoading(true);
    setError(null);
    setEmailRequiresLogin(null);

    try {
      const res = await fetch('/api/checkout', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
          items: items.map((i) => ({
            product_id: i.product.id,
            quantity: i.quantity,
          })),
          ...(contact ?? {}),
        }),
      });

      const json = (await res.json().catch(() => ({}))) as CheckoutSuccess | CheckoutError;

      if (!res.ok) {
        const err = json as CheckoutError;
        if (err.code === 'EMAIL_REQUIRES_LOGIN' && err.email) {
          setEmailRequiresLogin(err.email);
        } else {
          setError(err.message ?? 'Could not start checkout.');
        }
        setLoading(false);
        return;
      }

      const success = (json as CheckoutSuccess).data;
      clearCart();

      // Everyone — guest or authed — goes to the payment page to pick a gateway
      // and pay. /checkout/[id] is gated by the checkout_token UUID (not auth),
      // so guests can reach it too; the order confirmation (/order/[id]/received)
      // is reached only AFTER payment succeeds.
      router.replace(`/checkout/${success.id}`);
    } catch {
      setError('Could not reach the checkout service. Please try again.');
      setLoading(false);
    }
  };

  // Authed-user fast path: post once on first render.
  useEffect(() => {
    if (authStatus !== 'authed') return;
    if (items.length === 0) return;
    if (authedStartedRef.current) return;
    authedStartedRef.current = true;
    void startCheckout();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [authStatus, items.length]);

  // ─── Render branches ─────────────────────────────────────────────────────

  if (items.length === 0) {
    return (
      <div className="text-center" style={{ padding: '60px 0' }}>
        <p style={{ marginBottom: 16, opacity: 0.7 }}>Your cart is empty.</p>
        <Link href="/product-category/valorant" className="tg-btn">Browse shop</Link>
      </div>
    );
  }

  // Authed bootstrap is in-flight, or we're waiting for /api/auth/me to resolve.
  if (authStatus === 'authed' || authStatus === 'unknown') {
    return (
      <div className="text-center" style={{ padding: '60px 0' }}>
        {error ? (
          <>
            <p style={{ color: '#f87171', marginBottom: 16 }}>{error}</p>
            <Link href="/cart" className="tg-btn">Back to cart</Link>
          </>
        ) : (
          <p style={{ opacity: 0.7 }}>Preparing your checkout…</p>
        )}
      </div>
    );
  }

  // Guest path — show the contact form alongside the order summary.
  return (
    <div className="row">
      <div className="col-lg-7">
        <GuestContactForm
          loading={loading}
          error={emailRequiresLogin
            ? `An account already exists for ${emailRequiresLogin}.`
            : error
          }
          onSubmit={(contact) => void startCheckout(contact)}
        />
        {emailRequiresLogin && (
          <p style={{ marginTop: 16, fontSize: '0.95em' }}>
            <Link
              href={`/login?redirect=${encodeURIComponent('/checkout')}`}
              className="tg-btn"
              style={{ display: 'inline-block' }}
            >
              Sign in to continue
            </Link>
          </p>
        )}
      </div>

      <div className="col-lg-5">
        <CartOrderSummary items={items} />
      </div>
    </div>
  );
}
