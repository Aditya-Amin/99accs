'use client';
import Link from 'next/link';

interface Props {
  subtotal: number;
  total: number;
}

// Right sidebar from cart.html — .cart__collaterals-wrap.
// CSS in globals.css styles the heading, list, and button placement.
export function CartTotals({ subtotal, total }: Props) {
  return (
    <div className="cart__collaterals-wrap">
      <h2 className="title">Cart totals</h2>
      <ul className="list-wrap">
        <li>
          Subtotal <span>${subtotal.toFixed(2)}</span>
        </li>
        <li>
          Total <span className="amount">${total.toFixed(2)}</span>
        </li>
      </ul>
      <Link href="/checkout" className="tg-btn">Proceed to checkout</Link>
    </div>
  );
}
