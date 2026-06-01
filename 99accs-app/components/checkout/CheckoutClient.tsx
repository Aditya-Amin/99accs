'use client';
import { useState, useTransition, useCallback } from 'react';
import { useRouter } from 'next/navigation';
import type { SessionEnvelope, UpdateSessionInput } from '@/lib/mock/checkoutSessions';
import { PaymentMethodList } from './PaymentMethodList';
import { TrustpilotBadge } from './TrustpilotBadge';
import { OrderSummary } from './OrderSummary';
import { DiscountCodeInput } from './DiscountCodeInput';
import { WarrantyToggle } from './WarrantyToggle';
import { PriceBreakdown } from './PriceBreakdown';
import { WalletInputs } from './WalletInputs';
import { TotalRow } from './TotalRow';
import { PayNowButton } from './PayNowButton';
import { useCartStore } from '@/lib/store/cartStore';

interface Props {
  initial: SessionEnvelope;
  sessionId: string;
}

// Holds the full session envelope in client state. Every input dispatches a
// patch via POST /update; the response replaces local state. useTransition
// keeps the UI responsive while the patch is in flight.
export default function CheckoutClient({ initial, sessionId }: Props) {
  const router = useRouter();
  const clearCart = useCartStore((s) => s.clearCart);
  const [envelope, setEnvelope] = useState<SessionEnvelope>(initial);
  const [, startTransition] = useTransition();
  const [error, setError] = useState<string | null>(null);

  const patch = useCallback(
    (body: UpdateSessionInput) => {
      startTransition(() => void 0);
      (async () => {
        try {
          const res = await fetch(`/api/checkout/${sessionId}/update`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(body),
          });
          if (!res.ok) {
            const raw = (await res.json().catch(() => ({}))) as { message?: string };
            throw new Error(raw.message ?? `Update failed (${res.status})`);
          }
          const next = (await res.json()) as SessionEnvelope;
          setEnvelope(next);
          setError(null);
        } catch (e) {
          setError(e instanceof Error ? e.message : 'Update failed.');
        }
      })();
    },
    [sessionId]
  );

  const { session, wallet, payment_methods, coin_reward_preview } = envelope;

  const onPaySuccess = () => {
    clearCart();
    // Public, UUID-gated confirmation page — works for guests and authed users.
    router.push(`/order/${sessionId}/received`);
  };

  return (
    <div className="row">
      <div className="col-lg-7">
        <div className="checkout__pay-wrap">
          <span className="title">Pay with</span>
          <PaymentMethodList
            methods={payment_methods}
            selected={session.payment_method}
            onSelect={(id) => patch({ payment_method: id })}
          />
          <TrustpilotBadge />
        </div>
      </div>

      <div className="col-lg-5">
        <div className="order__info-wrap order__info-wrap--v2">
          <div className="order__info-inner">
            <h2 className="title">Your Order</h2>
            <OrderSummary items={session.items} currency={session.currency} />
            <DiscountCodeInput
              currentCode={session.discount_code}
              onApply={(code) => patch({ discount_code: code })}
              onClear={() => patch({ discount_code: null })}
            />
            <WarrantyToggle
              checked={session.lifetime_warranty}
              onChange={(checked) => patch({ lifetime_warranty: checked })}
            />
            <PriceBreakdown session={session} />
            <WalletInputs
              currency={session.currency}
              storeCreditCents={wallet.store_credit_cents}
              gbCoins={wallet.gb_coins}
            />
            <TotalRow
              currency={session.currency}
              totalCents={session.total_cents}
              coinReward={coin_reward_preview}
            />
            {error && <p className="checkout__error">{error}</p>}
            <PayNowButton
              sessionId={sessionId}
              paymentMethod={session.payment_method ?? undefined}
              disabled={!session.payment_method}
              onSuccess={onPaySuccess}
              onError={setError}
            />
            <p className="checkout__security">🔒 256-bit SSL Encrypted payment. You're safe.</p>
          </div>
        </div>
      </div>
    </div>
  );
}
