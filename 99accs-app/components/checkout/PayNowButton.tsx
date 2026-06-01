'use client';
import { useState } from 'react';

interface Props {
  sessionId: string;
  paymentMethod?: string;
  disabled: boolean;
  onSuccess: () => void;
  onError: (msg: string) => void;
}

// Triggers POST /pay. The mock returns a stub client_secret + publishable_key
// shape; in production this calls stripe.confirmPayment with return_url and
// the user is redirected by Stripe. Until Stripe is wired, we just call
// onSuccess so the order page can be reached.
export function PayNowButton({ sessionId, paymentMethod, disabled, onSuccess, onError }: Props) {
  const [submitting, setSubmitting] = useState(false);

  const handlePay = async () => {
    if (disabled || submitting) return;
    setSubmitting(true);
    try {
      const res = await fetch(`/api/checkout/${sessionId}/pay`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ payment_method: paymentMethod }),
      });
      if (!res.ok) {
        const raw = (await res.json().catch(() => ({}))) as { message?: string };
        throw new Error(raw.message ?? `Payment failed (${res.status})`);
      }
      // Real flow: stripe.confirmPayment({ clientSecret: data.client_secret, ... })
      // Mock flow: skip Stripe and surface success straight to the parent.
      onSuccess();
    } catch (e) {
      onError(e instanceof Error ? e.message : 'Payment failed.');
      setSubmitting(false);
    }
  };

  return (
    <button
      type="button"
      className="tg-btn checkout__pay-btn"
      onClick={handlePay}
      disabled={disabled || submitting}
      aria-disabled={disabled || submitting}
    >
      {submitting ? 'Processing…' : (
        <>
          Pay Now
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" strokeWidth="1.6" fill="none" />
          </svg>
        </>
      )}
    </button>
  );
}
