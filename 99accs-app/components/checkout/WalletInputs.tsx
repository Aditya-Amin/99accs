import type { Currency } from '@/lib/mock/checkoutSessions';
import { formatMoney } from './money';

interface Props {
  currency: Currency;
  storeCreditCents: number;
  gbCoins: number;
}

// Store-credit + 99 Coins rows. Mock layer has no wallet balance, so both
// inputs are rendered disabled with a "0" placeholder per the user's pick.
// When wallets ship server-side, just remove the `disabled` and wire onChange
// to the parent's patch() (debounced 400ms).
export function WalletInputs({ currency, storeCreditCents, gbCoins }: Props) {
  return (
    <ul className="wallet-inputs list-wrap">
      <li>
        <span className="wallet-inputs__label">
          <span className="wallet-inputs__coin wallet-inputs__coin--green" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="9" cy="9" r="8" fill="var(--tg-theme-primary)" />
              <text x="9" y="13" textAnchor="middle" fontSize="10" fontWeight="700" fill="#000E06">$</text>
            </svg>
          </span>
          Store Credit ({formatMoney(storeCreditCents, currency)})
        </span>
        <input
          type="number"
          className="wallet-inputs__input"
          placeholder="0"
          min={0}
          disabled
          title="Wallet coming soon"
        />
      </li>
      <li>
        <span className="wallet-inputs__label">
          <span className="wallet-inputs__coin wallet-inputs__coin--gold" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="9" cy="9" r="8" fill="#F59E0B" />
              <text x="9" y="13" textAnchor="middle" fontSize="10" fontWeight="700" fill="#000E06">99</text>
            </svg>
          </span>
          99 Coins ({gbCoins})
        </span>
        <input
          type="number"
          className="wallet-inputs__input"
          placeholder="0"
          min={0}
          disabled
          title="Wallet coming soon"
        />
      </li>
    </ul>
  );
}
