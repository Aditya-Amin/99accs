// In-memory mutable copy of mocks/support/tickets.json shared by the mock
// API routes so creating a ticket via POST and listing it via GET in the
// same dev session shows up. The seed is reloaded on cold start — fine for
// a dev mock; Laravel will persist for real.

import seed from '@/mocks/support/tickets.json';
import type { SupportTicket } from '@/lib/api/types';
import { getAuthUserId } from '@/lib/auth/server';

let tickets: SupportTicket[] = JSON.parse(JSON.stringify(seed));
let nextTicketId = Math.max(...tickets.map((t) => t.id)) + 1;
let nextMessageId =
  Math.max(0, ...tickets.flatMap((t) => (t.messages ?? []).map((m) => m.id))) + 1;

export function getAll(): SupportTicket[] {
  return tickets;
}

export function getById(id: number): SupportTicket | undefined {
  return tickets.find((t) => t.id === id);
}

export function replaceAll(next: SupportTicket[]): void {
  tickets = next;
}

export function allocateTicketId(): number {
  return nextTicketId++;
}

export function allocateMessageId(): number {
  return nextMessageId++;
}

// Delegates to the shared helper so all mock routes share one auth rule.
// Real Laravel will use `auth:sanctum` middleware on the same paths.
export async function requireUserId(): Promise<number | null> {
  return getAuthUserId();
}
