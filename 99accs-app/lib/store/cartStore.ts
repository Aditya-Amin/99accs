'use client';
import { create } from 'zustand';
import { persist, createJSONStorage } from 'zustand/middleware';
import type { Product } from '@/lib/api/types';

export interface CartLineItem {
  product: Product;
  quantity: number;
}

interface CartStore {
  items: CartLineItem[];
  addItem: (product: Product, qty?: number) => void;
  removeItem: (productId: number) => void;
  updateQty: (productId: number, qty: number) => void;
  clearCart: () => void;
  total: () => number;
  count: () => number;
}

export const useCartStore = create<CartStore>()(
  persist(
    (set, get) => ({
      items: [],
      addItem: (product, qty = 1) =>
        set((state) => {
          if (qty <= 0 || !product?.id) return state;
          const existing = state.items.find((i) => i.product.id === product.id);
          if (existing) {
            return {
              items: state.items.map((i) =>
                i.product.id === product.id ? { ...i, quantity: i.quantity + qty } : i
              ),
            };
          }
          return { items: [...state.items, { product, quantity: qty }] };
        }),
      removeItem: (productId) =>
        set((state) => ({ items: state.items.filter((i) => i.product.id !== productId) })),
      updateQty: (productId, qty) =>
        set((state) => ({
          items:
            qty <= 0
              ? state.items.filter((i) => i.product.id !== productId)
              : state.items.map((i) => (i.product.id === productId ? { ...i, quantity: qty } : i)),
        })),
      clearCart: () => set({ items: [] }),
      total: () => get().items.reduce((sum, i) => sum + i.product.price * i.quantity, 0),
      count: () => get().items.reduce((sum, i) => sum + i.quantity, 0),
    }),
    {
      name: '99accs-cart',
      version: 1,
      storage: createJSONStorage(() => localStorage),
      // Only persist items — never persist functions or computed state.
      partialize: (state) => ({ items: state.items }),
      // If the persisted shape ever drifts (e.g. Product schema change), drop the
      // cart rather than crashing on the next page load.
      migrate: (persisted, version) => {
        if (!persisted || typeof persisted !== 'object') return { items: [] };
        if (version === 0) return { items: [] };
        const items = (persisted as { items?: unknown }).items;
        return { items: Array.isArray(items) ? items : [] };
      },
    }
  )
);
