import type { Currency } from '@/lib/mock/checkoutSessions';
import { formatMoney } from './money';

interface Props {
  currency: Currency;
  totalCents: number;
  coinReward: number;
}

// Large total + tiny coin-reward preview underneath. The reward number comes
// straight from the envelope — no client math.
export function TotalRow({ currency, totalCents, coinReward }: Props) {
  return (
    <div className="checkout__total-row">
      <div className="checkout__total-line">
        <span>Total</span>
        <span className="checkout__total-amount">{formatMoney(totalCents, currency)}</span>
      </div>
      {coinReward > 0 && (
        <p className="checkout__reward">
          +{coinReward.toLocaleString()} <span className="checkout__reward-coin" aria-hidden="true">🟡</span> reward
        </p>
      )}
    </div>
  );
}
