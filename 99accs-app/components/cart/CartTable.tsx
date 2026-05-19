'use client';
import { CartTableRow } from './CartTableRow';
import { CartActionsRow } from './CartActionsRow';
import type { CartLineItem } from '@/lib/store/cartStore';

interface Props {
  items: CartLineItem[];
  onUpdateQty: (productId: number, qty: number) => void;
  onRemove: (productId: number) => void;
  onUpdateCart?: () => void;
  onApplyCoupon?: (code: string) => void;
}

export function CartTable({ items, onUpdateQty, onRemove, onUpdateCart, onApplyCoupon }: Props) {
  return (
    <table className="table cart__table">
      <thead>
        <tr>
          <th className="product__remove">&nbsp;</th>
          <th className="product__thumb">&nbsp;</th>
          <th className="product__name">Product</th>
          <th className="product__price">Price</th>
          <th className="product__quantity">Quantity</th>
          <th className="product__subtotal">Subtotal</th>
        </tr>
      </thead>
      <tbody>
        {items.map((line) => (
          <CartTableRow
            key={line.product.id}
            line={line}
            onUpdateQty={onUpdateQty}
            onRemove={onRemove}
          />
        ))}
        <CartActionsRow onUpdateCart={onUpdateCart} onApplyCoupon={onApplyCoupon} />
      </tbody>
    </table>
  );
}
