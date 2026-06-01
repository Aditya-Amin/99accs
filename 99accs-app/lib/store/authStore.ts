'use client';
import { create } from 'zustand';
import type { AuthUser } from '@/lib/api/types';

// GET /api/auth/me returns the user directly under `data` (it passes Laravel's
// { data: <customerShape> } through). NOTE: this differs from /login, /register
// and /reset-password, which wrap it as { data: { user } }.
interface SessionResponse {
  data: AuthUser | null;
}

interface LegacyResetError {
  code: 'LEGACY_PASSWORD_RESET_REQUIRED';
  message: string;
  email: string;
}

interface GenericError {
  code?: string;
  message?: string;
  errors?: Record<string, string[]>;
}

export type LoginResult =
  | { ok: true; user: AuthUser }
  | { ok: false; legacy: true; email: string; message: string }
  | { ok: false; legacy: false; message: string };

interface AuthStore {
  user: AuthUser | null;
  // 'unknown' = haven't checked /me yet (initial mount), 'authed' = /me
  // returned a user, 'guest' = /me returned null. Consumers should treat
  // 'unknown' as a transient state and not redirect.
  status: 'unknown' | 'authed' | 'guest';
  isAuthenticated: () => boolean;
  hydrate: () => Promise<void>;
  login: (email: string, password: string) => Promise<LoginResult>;
  logout: () => Promise<void>;
  setUser: (user: AuthUser) => void;
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
      const res = await fetch('/api/auth/me', { credentials: 'include' });
      if (!res.ok) {
        set({ user: null, status: 'guest' });
        return;
      }
      const json = (await res.json()) as SessionResponse;
      if (!json.data) {
        set({ user: null, status: 'guest' });
        return;
      }
      // /me returns the user directly under `data` (not `data.user`).
      set({ user: json.data, status: 'authed' });
    } catch {
      set({ user: null, status: 'guest' });
    }
  },

  login: async (email, password) => {
    try {
      const res = await fetch('/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ email, password }),
      });

      // Legacy migration — Laravel triggered a reset email already, and the
      // BFF set the reset cookies so proxy.ts will funnel the user to
      // /reset-password. We return enough info for the form to display the
      // migration notice inline.
      if (res.status === 409) {
        const body = (await res.json()) as LegacyResetError;
        if (body.code === 'LEGACY_PASSWORD_RESET_REQUIRED') {
          return { ok: false, legacy: true, email: body.email, message: body.message };
        }
      }

      if (!res.ok) {
        const body = (await res.json().catch(() => ({}))) as GenericError;
        return {
          ok: false,
          legacy: false,
          message: body.message ?? 'Invalid credentials.',
        };
      }

      const json = (await res.json()) as { data: { user: AuthUser } };
      set({ user: json.data.user, status: 'authed' });
      hydratedOnce = true;
      return { ok: true, user: json.data.user };
    } catch {
      return { ok: false, legacy: false, message: 'Login failed. Please try again.' };
    }
  },

  logout: async () => {
    try {
      await fetch('/api/auth/logout', { method: 'POST', credentials: 'include' });
    } catch {
      // Local logout must always succeed even if the BFF call fails.
    }
    hydratedOnce = false;
    set({ user: null, status: 'guest' });
  },

  setUser: (user) => {
    hydratedOnce = true;
    set({ user, status: 'authed' });
  },

  clear: () => {
    hydratedOnce = false;
    set({ user: null, status: 'guest' });
  },
}));
