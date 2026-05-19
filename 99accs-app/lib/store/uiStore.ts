'use client';
import { create } from 'zustand';

type AuthModalMode = 'login' | 'register' | 'forgot-password';

interface UiStore {
  authModalOpen: boolean;
  authModalMode: AuthModalMode;
  authPostLoginRedirect: string | null;
  mobileMenuOpen: boolean;
  openAuthModal: (mode?: AuthModalMode, postLoginRedirect?: string) => void;
  closeAuthModal: () => void;
  clearAuthPostLoginRedirect: () => void;
  toggleMobileMenu: () => void;
  closeMobileMenu: () => void;
}

export const useUiStore = create<UiStore>()((set) => ({
  authModalOpen: false,
  authModalMode: 'login',
  authPostLoginRedirect: null,
  mobileMenuOpen: false,
  openAuthModal: (mode = 'login', postLoginRedirect) =>
    set({
      authModalOpen: true,
      authModalMode: mode,
      authPostLoginRedirect: postLoginRedirect ?? null,
    }),
  closeAuthModal: () =>
    set({
      authModalOpen: false,
      authPostLoginRedirect: null,
    }),
  clearAuthPostLoginRedirect: () => set({ authPostLoginRedirect: null }),
  toggleMobileMenu: () => set((s) => ({ mobileMenuOpen: !s.mobileMenuOpen })),
  closeMobileMenu: () => set({ mobileMenuOpen: false }),
}));
