'use client';
import { useState } from 'react';
import { useCartStore } from '@/lib/store/cartStore';
import type { Product } from '@/lib/api/types';

interface CartQtyAddToCartProps {
  product: Product;
  minQuantity?: number;
}

export default function CartQtyAddToCart({ product, minQuantity = 1 }: CartQtyAddToCartProps) {
  const [qty, setQty] = useState(minQuantity);
  const addItem = useCartStore((s) => s.addItem);

  const onMinus = () => setQty((q) => Math.max(minQuantity, q - 1));
  const onPlus = () => setQty((q) => q + 1);

  return (
    <div className="shop__details-qty">
      <div className="cart-plus-minus">
        <button type="button" className="dec qtybutton" onClick={onMinus} aria-label="Decrease quantity">-</button>
        <input
          type="text"
          value={qty}
          onChange={(e) => {
            const n = parseInt(e.target.value, 10);
            if (!Number.isNaN(n)) setQty(Math.max(minQuantity, n));
          }}
        />
        <button type="button" className="inc qtybutton" onClick={onPlus} aria-label="Increase quantity">+</button>
      </div>
      <button type="button" className="tg-btn" onClick={() => addItem(product, qty)}>
        Add to cart
      </button>
    </div>
  );
}
