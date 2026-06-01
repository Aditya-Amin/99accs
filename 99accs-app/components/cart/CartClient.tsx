'use client';
import Link from 'next/link';
import { useCartStore } from '@/lib/store/cartStore';
import { CartTable } from './CartTable';
import { CartTotals } from './CartTotals';

export default function CartClient() {
  const items = useCartStore((s) => s.items);
  const removeItem = useCartStore((s) => s.removeItem);
  const updateQty = useCartStore((s) => s.updateQty);
  const total = useCartStore((s) => s.total());

  if (items.length === 0) {
    return (
      <div className="text-center" style={{ padding: '80px 0' }}>
        <p style={{ marginBottom: 24, opacity: 0.7 }}>Your cart is empty.</p>
        <Link href="/product-category/valorant" className="tg-btn">Continue shopping</Link>
      </div>
    );
  }

  return (
    <div className="row">
      <div className="col-lg-8">
        <CartTable
          items={items}
          onUpdateQty={updateQty}
          onRemove={removeItem}
          // Cart is localStorage-backed — qty changes already persist
          // immediately. "Update cart" is a no-op kept for visual parity.
        />
      </div>
      <div className="col-lg-4">
        <CartTotals subtotal={total} total={total} />
      </div>
    </div>
  );
}
