'use client';
import { useEffect, useRef, useState } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { useCartStore } from '@/lib/store/cartStore';

// One-shot bootstrap: posts the cart to /api/mock/checkout, then redirects to
// /checkout/{id}. Rendered by /checkout (the no-id route) so cart users can
// keep linking to "/checkout" without knowing about session ids.
export function CheckoutStarter() {
  const router = useRouter();
  const items = useCartStore((s) => s.items);
  const [error, setError] = useState<string | null>(null);
  const startedRef = useRef(false);

  useEffect(() => {
    if (startedRef.current) return;
    if (items.length === 0) return; // empty-cart branch renders below
    startedRef.current = true;

    (async () => {
      try {
        const res = await fetch('/api/mock/checkout', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'include',
          body: JSON.stringify({
            currency: 'USD',
            items: items.map((i) => ({
              id: i.product.id,
              title: i.product.title,
              image: i.product.images?.[0] ?? null,
              unit_price_cents: Math.round(i.product.price * 100),
              quantity: i.quantity,
              category: i.product.account_type,
              delivery_type: 'instant',
              warranty_days: 14,
              attributes: {
                region: i.product.country?.code ?? '',
              },
            })),
          }),
        });

        if (res.status === 401) {
          router.replace('/login');
          return;
        }
        if (!res.ok) {
          const body = (await res.json().catch(() => ({}))) as { message?: string };
          throw new Error(body.message ?? `Failed (${res.status})`);
        }
        const { session } = (await res.json()) as { session: { id: string } };
        router.replace(`/checkout/${session.id}`);
      } catch (e) {
        setError(e instanceof Error ? e.message : 'Could not start checkout.');
        startedRef.current = false;
      }
    })();
  }, [items, router]);

  if (items.length === 0) {
    return (
      <div className="text-center" style={{ padding: '60px 0' }}>
        <p style={{ marginBottom: 16, opacity: 0.7 }}>Your cart is empty.</p>
        <Link href="/shop/valorant" className="tg-btn">Browse shop</Link>
      </div>
    );
  }

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
