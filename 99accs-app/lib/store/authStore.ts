'use client';
import { create } from 'zustand';
import type { AuthUser } from '@/lib/api/types';

interface LoginSuccess {
  data: { token: string; user: AuthUser };
}
interface ForcedReset {
  must_reset_password: true;
  reset_token: string;
  email: string;
}
type LoginResult =
  | { ok: true; user: AuthUser }
  | { ok: false; mustReset: true }
  | { ok: false; mustReset: false; message: string };

interface AuthStore {
  user: AuthUser | null;
  // 'unknown' = haven't checked the cookie yet (initial mount), 'authed' =
  // /user returned a user, 'guest' = /user returned 401. Consumers should
  // treat 'unknown' as a transient state and not redirect.
  status: 'unknown' | 'authed' | 'guest';
  // Derived selector — usable inline as `useAuthStore((s) => s.isAuthenticated())`.
  isAuthenticated: () => boolean;
  hydrate: () => Promise<void>;
  // Posts to /api/mock/login, sets state on success, returns a discriminated
  // result so callers can branch on must_reset_password without writing
  // /login fetch boilerplate themselves.
  login: (email: string, password: string) => Promise<LoginResult>;
  // Posts to /api/mock/logout (clears cookie server-side), clears local state.
  logout: () => Promise<void>;
  // Called by the modal/page panes after they handle login themselves.
  setUser: (user: AuthUser) => void;
  // Called by tests / forced-cleanup paths.
  clear: () => void;
}

let hydratedOnce = false;

export const useAuthStore = create<AuthStore>()((set, get) => ({
  user: null,
  status: 'unknown',
  isAuthenticated: () => get().status === 'authed',
  hydrate: async () => {
    if (hydratedOnce) return;
    hydratedOnce = true;
    try {
      const res = await fetch('/api/mock/user', { credentials: 'include' });
      // Mock contract: 200 with `data: null` for guests (avoids console noise
      // from a 401 on every page load). Real Laravel responds 401 — we treat
      // both signals identically.
      if (!res.ok) {
        set({ user: null, status: 'guest' });
        return;
      }
      const json = (await res.json()) as { data: AuthUser | null };
      if (!json.data) {
        set({ user: null, status: 'guest' });
        return;
      }
      set({ user: json.data, status: 'authed' });
    } catch {
      set({ user: null, status: 'guest' });
    }
  },
  login: async (email, password) => {
    try {
      const res = await fetch('/api/mock/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ email, password }),
      });
      if (!res.ok) {
        return { ok: false, mustReset: false, message: 'Invalid credentials.' };
      }
      const json = (await res.json()) as LoginSuccess | ForcedReset;
      if ('must_reset_password' in json) {
        return { ok: false, mustReset: true };
      }
      set({ user: json.data.user, status: 'authed' });
      return { ok: true, user: json.data.user };
    } catch {
      return { ok: false, mustReset: false, message: 'Login failed.' };
    }
  },
  logout: async () => {
    try {
      await fetch('/api/mock/logout', { method: 'POST', credentials: 'include' });
    } catch {
      // Swallow — the cookie will be cleared on next hydrate even if the request fails.
    }
    hydratedOnce = false;
    set({ user: null, status: 'guest' });
  },
  setUser: (user) => set({ user, status: 'authed' }),
  clear: () => {
    hydratedOnce = false;
    set({ user: null, status: 'guest' });
  },
}));
