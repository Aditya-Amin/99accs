import type { SessionEnvelope, CheckoutItem, PaymentMethodEntry } from '@/lib/mock/checkoutSessions';

export interface LaravelCheckoutItem {
  id: number | string;
  title: string;
  image: string | null;
  price: number;
  quantity: number;
  subtotal: number;
}

export interface LaravelCheckoutData {
  id: string;
  order_number: string;
  status: string;
  payment_status: string;
  payment_method: string | null;
  subtotal: number;
  total: number;
  expires_at: string | null;
  payment_methods_available: string[];
  items: LaravelCheckoutItem[];
  customer_email?: string | null;
  is_guest_checkout?: boolean;
}

export interface CheckoutOverlay {
  payment_method?: string | null;
  lifetime_warranty?: boolean;
  discount_code?: string | null;
}

export const CHECKOUT_PAYMENT_METHODS: PaymentMethodEntry[] = [
  { id: 'stripe', label: 'Debit/Credit cards', sublabel: 'We accept all major debit and credit cards.' },
  { id: 'crypto', label: 'Crypto', sublabel: 'BTC · ETH · LTC · USDT · USDC and more!' },
];

// Per-process session state overlay. The real backend doesn't persist
// payment_method/warranty choices until pay — so we hold them here
// across update calls within the same server process lifetime.
const overlays = new Map<string, CheckoutOverlay>();

export function getOverlay(id: string): CheckoutOverlay {
  return overlays.get(id) ?? {};
}

export function patchOverlay(id: string, patch: CheckoutOverlay): CheckoutOverlay {
  const updated = { ...getOverlay(id), ...patch };
  overlays.set(id, updated);
  return updated;
}

export function toEnvelope(data: LaravelCheckoutData, overlay: CheckoutOverlay = {}): SessionEnvelope {
  const subtotalCents = Math.round((data.subtotal ?? 0) * 100);
  const totalCents = Math.round((data.total ?? 0) * 100);

  const items: CheckoutItem[] = (data.items ?? []).map((it) => ({
    id: String(it.id),
    unit_price_cents: Math.round((it.price ?? 0) * 100),
    quantity: it.quantity,
    snapshot: {
      title: it.title ?? 'Item',
      images: it.image ? [it.image] : [],
      category: 'account',
      delivery_type: 'instant' as const,
      warranty_days: 14,
    },
  }));

  const paymentMethod =
    'payment_method' in overlay ? (overlay.payment_method ?? null) : (data.payment_method ?? null);

  // Only surface gateways the admin has actually enabled (and configured) in the
  // dashboard. `payment_methods_available` comes from PaymentGateway::active().
  // Previously this list was hardcoded, so the checkout offered gateways that
  // the backend would then reject at /pay.
  const activeSlugs = new Set(data.payment_methods_available ?? []);
  const paymentMethods = CHECKOUT_PAYMENT_METHODS.filter((m) => activeSlugs.has(m.id));

  return {
    session: {
      id: data.id,
      user_id: 0,
      // Use payment_status as the canonical paid signal; fall back to order status.
      status: (data.payment_status === 'paid' ? 'paid' : data.status) as any,
      currency: 'USD',
      subtotal_cents: subtotalCents,
      marketplace_fee_cents: 0,
      processor_fee_cents: 0,
      warranty_fee_cents: 0,
      discount_code_cents: 0,
      store_credit_applied_cents: 0,
      coins_applied: 0,
      total_cents: totalCents,
      lifetime_warranty: overlay.lifetime_warranty ?? false,
      discount_code: overlay.discount_code !== undefined ? (overlay.discount_code ?? null) : null,
      payment_method: paymentMethod,
      items,
      created_at: Date.now(),
      expires_at: data.expires_at
        ? new Date(data.expires_at).getTime()
        : Date.now() + 3_600_000,
    },
    wallet: { store_credit_cents: 0, gb_coins: 0 },
    payment_methods: paymentMethods,
    coin_reward_preview: 0,
  };
}
