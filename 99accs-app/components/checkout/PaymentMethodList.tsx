'use client';
import type { PaymentMethodEntry } from '@/lib/mock/checkoutSessions';
import { PaymentIcon } from './PaymentIcon';

interface Props {
  methods: PaymentMethodEntry[];
  selected: string | null;
  onSelect: (id: string) => void;
}

// Vertically stacked, radio-style payment cards. Re-uses the 99accs button
// pattern (border + theme-green active state) instead of GameBoost blue.
export function PaymentMethodList({ methods, selected, onSelect }: Props) {
  return (
    <ul className="payment-method-list list-wrap">
      {methods.map((m) => {
        const isSelected = selected === m.id;
        return (
          <li key={m.id}>
            <button
              type="button"
              className={`payment-method-card${isSelected ? ' is-selected' : ''}`}
              onClick={() => onSelect(m.id)}
              aria-pressed={isSelected}
            >
              <span className="payment-method-card__icon">
                <PaymentIcon id={m.id} />
              </span>
              <span className="payment-method-card__body">
                <span className="payment-method-card__label">{m.label}</span>
                {m.sublabel && (
                  <span className="payment-method-card__sublabel">{m.sublabel}</span>
                )}
              </span>
              <span className="payment-method-card__radio" aria-hidden="true">
                <span className="dot" />
              </span>
            </button>
          </li>
        );
      })}
    </ul>
  );
}
