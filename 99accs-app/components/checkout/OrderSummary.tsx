import type { CheckoutItem, Currency } from '@/lib/mock/checkoutSessions';
import { formatMoney } from './money';

interface Props {
  items: CheckoutItem[];
  currency: Currency;
}

// Line items: 80px thumbnail, title with service metadata lines, right-aligned
// price. Falls back to a neutral placeholder when no image is provided.
export function OrderSummary({ items, currency }: Props) {
  return (
    <ul className="order-items list-wrap">
      {items.map((item) => {
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
                <li>🎮 {labelForCategory(item.snapshot.category)}</li>
                <li>
                  {item.snapshot.delivery_type === 'instant'
                    ? '⚡ Instant delivery after payment'
                    : '📨 Manual delivery'}
                </li>
                {item.snapshot.warranty_days > 0 && (
                  <li>🛡 {item.snapshot.warranty_days}-days Warranty Included</li>
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
  );
}

function labelForCategory(c: string): string {
  switch (c) {
    case 'verified':
    case 'inactive_exclusive':
      return 'Valorant Account';
    case 'nfa_random':
    case 'nfa_guaranteed':
    case 'nfa_inactive':
      return 'Fortnite Account';
    case 'standard':
      return 'League of Legends Account';
    default:
      return 'Account';
  }
}
