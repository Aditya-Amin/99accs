// In-memory checkout-session store for the mock layer.
//
// Real backend = a Laravel `checkout_sessions` table keyed by uuid. Here we
// keep a Map<id, session>. State is per-Node-process and not persistent —
// fine for the dev mock; restart wipes it. Sessions expire after 30 minutes
// of wall-clock idle to mirror the Laravel TTL.

import { randomUUID } from 'crypto';

export type Currency = 'USD' | 'EUR';

export interface CheckoutItemSnapshot {
  title: string;
  images: string[];
  category: string;
  delivery_type: 'instant' | 'manual';
  warranty_days: number;
  attributes?: Record<string, string | number>;
}

export interface CheckoutItem {
  id: string;
  unit_price_cents: number;
  quantity: number;
  snapshot: CheckoutItemSnapshot;
}

export type CheckoutStatus = 'pending' | 'processing' | 'paid' | 'expired';

export interface CheckoutSession {
  id: string;
  user_id: number;
  status: CheckoutStatus;
  currency: Currency;
  subtotal_cents: number;
  marketplace_fee_cents: number;
  processor_fee_cents: number;
  warranty_fee_cents: number;
  discount_code_cents: number;
  store_credit_applied_cents: number;
  coins_applied: number;
  total_cents: number;
  lifetime_warranty: boolean;
  discount_code: string | null;
  payment_method: string | null;
  items: CheckoutItem[];
  created_at: number;
  expires_at: number;
}

export interface PaymentMethodEntry {
  id: string;
  label: string;
  sublabel?: string;
}

export interface SessionEnvelope {
  session: CheckoutSession;
  wallet: { store_credit_cents: number; gb_coins: number };
  payment_methods: PaymentMethodEntry[];
  coin_reward_preview: number;
}

const TTL_MS = 30 * 60 * 1000;
const FEE_MARKETPLACE_BPS = 30;   // 0.30%
const FEE_PROCESSOR_BPS = 390;    // 3.90%
const WARRANTY_BPS = 1500;        // 15%
const COIN_REWARD_BPS = 90;       // 0.90% of subtotal rounded to int (display only)

const sessions = new Map<string, CheckoutSession>();

export const PAYMENT_METHODS: PaymentMethodEntry[] = [
  { id: 'card', label: 'Debit/Credit cards', sublabel: 'We accept all major debit and credit cards.' },
  { id: 'crypto', label: 'Crypto', sublabel: 'BTC · ETH · LTC · USDT · USDC and more!' },
];

function recompute(s: CheckoutSession): void {
  s.subtotal_cents = s.items.reduce((sum, it) => sum + it.unit_price_cents * it.quantity, 0);
  s.marketplace_fee_cents = Math.round((s.subtotal_cents * FEE_MARKETPLACE_BPS) / 10000);
  s.warranty_fee_cents = s.lifetime_warranty
    ? Math.round((s.subtotal_cents * WARRANTY_BPS) / 10000)
    : 0;
  // Processor fee applies to subtotal + marketplace + warranty (excluding discounts/wallet).
  const taxable = s.subtotal_cents + s.marketplace_fee_cents + s.warranty_fee_cents;
  s.processor_fee_cents = Math.round((taxable * FEE_PROCESSOR_BPS) / 10000);
  const gross =
    s.subtotal_cents +
    s.marketplace_fee_cents +
    s.warranty_fee_cents +
    s.processor_fee_cents -
    s.discount_code_cents -
    s.store_credit_applied_cents -
    s.coins_applied;
  s.total_cents = Math.max(0, gross);
}

export interface CreateSessionInput {
  user_id: number;
  currency?: Currency;
  items: CheckoutItem[];
}

export function createSession({ user_id, currency = 'USD', items }: CreateSessionInput): CheckoutSession {
  if (items.length === 0) {
    throw new Error('Cart is empty.');
  }
  const now = Date.now();
  const s: CheckoutSession = {
    id: randomUUID(),
    user_id,
    status: 'pending',
    currency,
    subtotal_cents: 0,
    marketplace_fee_cents: 0,
    processor_fee_cents: 0,
    warranty_fee_cents: 0,
    discount_code_cents: 0,
    store_credit_applied_cents: 0,
    coins_applied: 0,
    total_cents: 0,
    lifetime_warranty: false,
    discount_code: null,
    // Default selection: Debit/Credit cards. Users can still switch in the UI.
    payment_method: 'card',
    items,
    created_at: now,
    expires_at: now + TTL_MS,
  };
  recompute(s);
  sessions.set(s.id, s);
  return s;
}

export function getSession(id: string): CheckoutSession | null {
  const s = sessions.get(id);
  if (!s) return null;
  if (Date.now() > s.expires_at && s.status !== 'paid') {
    s.status = 'expired';
  }
  return s;
}

export interface UpdateSessionInput {
  lifetime_warranty?: boolean;
  discount_code?: string | null;
  store_credit_cents?: number;
  coins?: number;
  payment_method?: string | null;
}

const VALID_DISCOUNTS: Record<string, number> = {
  // Code → cents off (mock only).
  SAVE10: 1000,
  WELCOME: 500,
};

export function updateSession(id: string, patch: UpdateSessionInput): CheckoutSession | null {
  const s = getSession(id);
  if (!s) return null;
  if (s.status !== 'pending') return s;

  if (patch.lifetime_warranty !== undefined) s.lifetime_warranty = !!patch.lifetime_warranty;
  if (patch.payment_method !== undefined) s.payment_method = patch.payment_method;

  if (patch.discount_code !== undefined) {
    if (patch.discount_code === null || patch.discount_code === '') {
      s.discount_code = null;
      s.discount_code_cents = 0;
    } else {
      const cents = VALID_DISCOUNTS[patch.discount_code.toUpperCase()];
      if (cents !== undefined) {
        s.discount_code = patch.discount_code.toUpperCase();
        s.discount_code_cents = cents;
      } // unknown codes are silently ignored — UI can show its own "invalid code" hint
    }
  }

  // Wallet inputs are mock-disabled (no balance) — clamp to 0 server-side.
  if (patch.store_credit_cents !== undefined) s.store_credit_applied_cents = 0;
  if (patch.coins !== undefined) s.coins_applied = 0;

  recompute(s);
  return s;
}

export function markProcessing(id: string): CheckoutSession | null {
  const s = getSession(id);
  if (!s) return null;
  if (s.status === 'pending') s.status = 'processing';
  return s;
}

export function envelope(s: CheckoutSession): SessionEnvelope {
  return {
    session: s,
    wallet: { store_credit_cents: 0, gb_coins: 0 },
    payment_methods: PAYMENT_METHODS,
    coin_reward_preview: Math.round((s.subtotal_cents * COIN_REWARD_BPS) / 10000),
  };
}
