// Direct mock-store reads for server-side rendering. Mirrors the
// `lib/mock/products.ts` pattern — pages can call these without paying
// for an HTTP roundtrip back to /api/mock. Client-side mutations still
// go through the HTTP routes (see lib/api/endpoints.ts).
//
// In production this whole file will be deleted; pages will switch to
// the `lib/api/endpoints.ts` helpers which hit Laravel.

import type { SupportTicket, SupportTicketStatus } from '@/lib/api/types';
import seed from '@/mocks/support/tickets.json';

const ALL = seed as unknown as SupportTicket[];
const DEMO_USER_ID = 1;

export interface MockTicketFilters {
  status?: SupportTicketStatus;
  game?: string;
  search?: string;
}

export function getMockTickets(filters: MockTicketFilters = {}): SupportTicket[] {
  let results = ALL.filter((t) => t.user_id === DEMO_USER_ID);
  if (filters.status) results = results.filter((t) => t.status === filters.status);
  if (filters.game) results = results.filter((t) => t.game === filters.game);
  if (filters.search) {
    const q = filters.search.toLowerCase();
    results = results.filter(
      (t) => t.subject.toLowerCase().includes(q) || t.preview.toLowerCase().includes(q),
    );
  }
  return [...results].sort((a, b) => b.created_at.localeCompare(a.created_at));
}

export function getMockTicket(id: number): SupportTicket | null {
  return ALL.find((t) => t.id === id && t.user_id === DEMO_USER_ID) ?? null;
}
