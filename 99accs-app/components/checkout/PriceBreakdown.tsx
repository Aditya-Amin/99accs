import type { CheckoutSession } from '@/lib/mock/checkoutSessions';
import { formatMoney } from './money';

interface Props {
  session: CheckoutSession;
}

// Subtotal / fees / discount / warranty rows. Discount + warranty rows are
// hidden when their cents value is 0 (per the guide).
export function PriceBreakdown({ session }: Props) {
  const c = session.currency;
  return (
    <ul className="price-breakdown list-wrap">
      <li>
        <span>Subtotal</span>
        <span className="price-breakdown__value">{formatMoney(session.subtotal_cents, c)}</span>
      </li>
      <li>
        <span>
          Marketplace Fee
          <Tooltip text="Platform service fee — covers escrow and dispute handling." />
        </span>
        <span className="price-breakdown__value">{formatMoney(session.marketplace_fee_cents, c)}</span>
      </li>
      <li>
        <span>
          Processor Fee
          <Tooltip text="Payment-processor fee, varies by method." />
        </span>
        <span className="price-breakdown__value">{formatMoney(session.processor_fee_cents, c)}</span>
      </li>
      {session.warranty_fee_cents > 0 && (
        <li>
          <span>Lifetime Warranty</span>
          <span className="price-breakdown__value">{formatMoney(session.warranty_fee_cents, c)}</span>
        </li>
      )}
      {session.discount_code_cents > 0 && (
        <li className="price-breakdown__discount">
          <span>Discount ({session.discount_code})</span>
          <span className="price-breakdown__value">−{formatMoney(session.discount_code_cents, c)}</span>
        </li>
      )}
    </ul>
  );
}

function Tooltip({ text }: { text: string }) {
  return (
    <span className="price-breakdown__tip" title={text} aria-label={text}>
      (?)
    </span>
  );
}
