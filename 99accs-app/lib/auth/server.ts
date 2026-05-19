import { cookies } from 'next/headers';
import type { NextRequest } from 'next/server';

const TOKEN_COOKIE = '99accs_token';
const USER_COOKIE = '99accs_user';

// Demo seed account. Any other email = "new user" → empty data everywhere.
// The id of 1 is intentional — all seed JSON in mocks/ is owner=1, so the
// ticket store / order route filters cleanly produce empty arrays for
// id !== 1 without any data hacking.
export const DEMO_USER = {
  id: 1,
  email: 'demo@99accs.com',
  name: 'Demo User',
  created_at: '2025-01-01T00:00:00Z',
};

export interface MockUser {
  id: number;
  email: string;
  name: string;
  created_at: string;
}

// Allocates a deterministic id for non-demo users from their email so the
// same email always resolves to the same id across cookie roundtrips. Cheap
// FNV-1a — collisions don't matter for mock dev; Laravel will use a real
// auto-increment id.
function deterministicIdForEmail(email: string): number {
  let hash = 0x811c9dc5;
  for (let i = 0; i < email.length; i++) {
    hash ^= email.charCodeAt(i);
    hash = (hash * 0x01000193) >>> 0;
  }
  // Reserve id 1 for the demo user; everything else lives in [1000, 999_999].
  return 1000 + (hash % 999_000);
}

function nameFromEmail(email: string): string {
  const local = email.split('@')[0] ?? 'user';
  // "john.smith" → "John Smith"
  return local
    .replace(/[._-]+/g, ' ')
    .replace(/\b\w/g, (c) => c.toUpperCase())
    .trim() || 'User';
}

export function buildMockUser(email: string): MockUser {
  const normalized = email.trim().toLowerCase();
  if (normalized === DEMO_USER.email) return { ...DEMO_USER };
  return {
    id: deterministicIdForEmail(normalized),
    email: normalized,
    name: nameFromEmail(normalized),
    created_at: new Date().toISOString(),
  };
}

// Single source of truth for "is this request authenticated".
//
// Two channels:
//   1. The 99accs_token httpOnly cookie set by the login route.
//   2. Bearer Authorization (curl / server-to-server).
export async function hasAuth(req?: Pick<NextRequest, 'headers'>): Promise<boolean> {
  const store = await cookies();
  if (store.has(TOKEN_COOKIE)) return true;
  if (req?.headers.get('authorization')) return true;
  return false;
}

// Reads the JSON-encoded user from the `99accs_user` cookie. Returns the demo
// user if the cookie is missing or invalid but a token is present — handles
// "logged-in via Bearer with no cookie" and the migration from the older
// single-cookie auth (where /user defaulted to demo).
export async function getCurrentUser(req?: Pick<NextRequest, 'headers'>): Promise<MockUser | null> {
  if (!(await hasAuth(req))) return null;
  const store = await cookies();
  const raw = store.get(USER_COOKIE)?.value;
  if (!raw) return { ...DEMO_USER };
  try {
    const parsed = JSON.parse(raw) as Partial<MockUser>;
    if (
      typeof parsed.id === 'number' &&
      typeof parsed.email === 'string' &&
      typeof parsed.name === 'string'
    ) {
      return {
        id: parsed.id,
        email: parsed.email,
        name: parsed.name,
        created_at: parsed.created_at ?? new Date().toISOString(),
      };
    }
  } catch {
    /* fall through */
  }
  return { ...DEMO_USER };
}

// Returns the authenticated user id (or null). Used by tickets store filter.
export async function getAuthUserId(req?: Pick<NextRequest, 'headers'>): Promise<number | null> {
  const user = await getCurrentUser(req);
  return user ? user.id : null;
}

export async function isDemoUser(req?: Pick<NextRequest, 'headers'>): Promise<boolean> {
  const user = await getCurrentUser(req);
  return !!user && user.id === DEMO_USER.id;
}
