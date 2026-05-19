import type { Currency } from '@/lib/mock/checkoutSessions';

// Server returns integer cents. Display = divide by 100, fixed to 2.
// USD prefixes "$", EUR prefixes "€". Never compute fees client-side; just
// format what the envelope provides.
export function formatMoney(cents: number, currency: Currency): string {
  const value = (cents / 100).toFixed(2);
  return currency === 'EUR' ? `€${value.replace('.', ',')}` : `$${value}`;
}

export function symbolFor(currency: Currency): string {
  return currency === 'EUR' ? '€' : '$';
}
