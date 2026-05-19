'use client';
import { useCartStore } from '@/lib/store/cartStore';
import type { Product } from '@/lib/api/types';

export default function AddToCartButton({ product }: { product: Product }) {
  const addItem = useCartStore((s) => s.addItem);
  return (
    <button className="tg-btn" onClick={() => addItem(product)}>
      Add to cart
    </button>
  );
}
