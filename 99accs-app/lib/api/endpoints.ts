import { api } from './client';
import type {
  ApiCollection,
  ApiResource,
  AuthLoginResponse,
  HomeData,
  Product,
  Order,
  WishlistItem,
  SupportArticle,
  SupportTicket,
  SupportTicketMessage,
  SupportTicketStatus,
  AccountDashboard,
  AuthUser,
  CartItem,
} from './types';

export interface ProductFilters {
  game?: 'valorant' | 'fortnite' | 'legends';
  account_type?:
    | 'verified'           // valorant VERIFIED                 → simple_two
    | 'inactive_exclusive' // valorant INACTIVE EXCLUSIVE        → rich
    | 'nfa'               // fortnite NFA (skin type via taxonomy) → simple_two
    | 'nfa_inactive'       // fortnite NFA Inactive Accounts     → fortnite_four
    | 'standard';          // legends (all regional sections)    → simple_three
  // Catalog section slug (independent of account_type — Legends uses
  // 'euw' / 'tr' / 'las' which all share account_type='standard').
  section?: string;
  skin?: string;         // skin taxonomy slug — filtered via JSON_CONTAINS
  country?: string;
  min_price?: number;
  max_price?: number;
  rank?: string;
  region?: string;
  sort?: 'price_asc' | 'price_desc' | 'newest' | 'oldest';
  search?: string;
  page?: number;
  per_page?: number;
}

export const getHome = () => api<ApiResource<HomeData>>('/home');

export const getProducts = (filters: ProductFilters = {}) => {
  const params = new URLSearchParams();
  Object.entries(filters).forEach(([k, v]) => {
    if (v !== undefined && v !== '') params.set(k, String(v));
  });
  const qs = params.toString();
  return api<ApiCollection<Product>>(`/products${qs ? `?${qs}` : ''}`);
};

export const getProduct = (slug: string) =>
  api<ApiResource<Product>>(`/products/${slug}`);

export const getSupportArticles = (category?: string) => {
  const qs = category ? `?category=${category}` : '';
  return api<ApiCollection<SupportArticle>>(`/support/articles${qs}`);
};

export const getSupportArticle = (slug: string) =>
  api<ApiResource<SupportArticle>>(`/support/articles/${slug}`);

// ── Support tickets — all auth-gated ──────────────────────────────────────

export interface SupportTicketFilters {
  status?: SupportTicketStatus;
  game?: 'valorant' | 'fortnite' | 'legends';
  search?: string;
  page?: number;
  per_page?: number;
}

export const getSupportTickets = (token: string | undefined, filters: SupportTicketFilters = {}) => {
  const params = new URLSearchParams();
  Object.entries(filters).forEach(([k, v]) => {
    if (v !== undefined && v !== '') params.set(k, String(v));
  });
  const qs = params.toString();
  return api<ApiCollection<SupportTicket>>(`/support/tickets${qs ? `?${qs}` : ''}`, { token });
};

export const getSupportTicket = (token: string | undefined, id: number) =>
  api<ApiResource<SupportTicket>>(`/support/tickets/${id}`, { token });

// `order_number` is server-generated — never sent by the client.
export const createSupportTicket = (
  token: string | undefined,
  body: { subject: string; body: string; game: 'valorant' | 'fortnite' | 'legends' },
) =>
  api<ApiResource<SupportTicket>>('/support/tickets', {
    method: 'POST',
    body: JSON.stringify(body),
    token,
  });

export const replySupportTicket = (
  token: string | undefined,
  id: number,
  body: { body: string; close_ticket?: boolean },
) =>
  api<ApiResource<SupportTicketMessage>>(`/support/tickets/${id}/replies`, {
    method: 'POST',
    body: JSON.stringify(body),
    token,
  });

export const updateSupportTicket = (
  token: string | undefined,
  id: number,
  body: { status?: SupportTicketStatus },
) =>
  api<ApiResource<SupportTicket>>(`/support/tickets/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(body),
    token,
  });

export const submitContact = (body: {
  name: string;
  email: string;
  subject: string;
  message: string;
}) => api<{ message: string }>('/support/contact', { method: 'POST', body: JSON.stringify(body) });

export const login = (email: string, password: string) =>
  api<AuthLoginResponse>('/login', {
    method: 'POST',
    body: JSON.stringify({ email, password }),
  });

export const register = (name: string, email: string, password: string, password_confirmation: string) =>
  api<AuthLoginResponse>('/register', {
    method: 'POST',
    body: JSON.stringify({ name, email, password, password_confirmation }),
  });

export const forgotPassword = (email: string) =>
  api<{ message: string }>('/forgot-password', {
    method: 'POST',
    body: JSON.stringify({ email }),
  });

export const logout = (token: string) =>
  api<{ message: string }>('/logout', { method: 'POST', token });

export const getMe = (token: string) =>
  api<ApiResource<AuthUser>>('/user', { token });

export const getDashboard = (token: string) =>
  api<ApiResource<AccountDashboard>>('/account/dashboard', { token });

export const getOrders = (token: string, page = 1) =>
  api<ApiCollection<Order>>(`/account/orders?page=${page}`, { token });

export const getOrder = (token: string, id: number) =>
  api<ApiResource<Order>>(`/account/orders/${id}`, { token });

export const getProfile = (token: string) =>
  api<ApiResource<AuthUser>>('/account/profile', { token });

export const updateProfile = (token: string, body: Partial<AuthUser & { password: string; password_confirmation: string }>) =>
  api<ApiResource<AuthUser>>('/account/profile', { method: 'PATCH', body: JSON.stringify(body), token });

export const getWishlist = (token: string, page = 1) =>
  api<ApiCollection<WishlistItem>>(`/account/wishlist?page=${page}`, { token });

export const addWishlist = (token: string, product_id: number) =>
  api<ApiResource<WishlistItem>>('/account/wishlist', {
    method: 'POST',
    body: JSON.stringify({ product_id }),
    token,
  });

export const removeWishlist = (token: string, id: number) =>
  api<{ message: string }>(`/account/wishlist/${id}`, { method: 'DELETE', token });

export const getCart = (token: string) =>
  api<ApiCollection<CartItem>>('/cart', { token });

export const addToCart = (token: string, product_id: number, quantity = 1) =>
  api<ApiResource<CartItem>>('/cart', {
    method: 'POST',
    body: JSON.stringify({ product_id, quantity }),
    token,
  });

export const updateCartItem = (token: string, item_id: number, quantity: number) =>
  api<ApiResource<CartItem>>(`/cart/${item_id}`, {
    method: 'PATCH',
    body: JSON.stringify({ quantity }),
    token,
  });

export const removeCartItem = (token: string, item_id: number) =>
  api<{ message: string }>(`/cart/${item_id}`, { method: 'DELETE', token });

export const clearCart = (token: string) =>
  api<{ message: string }>('/cart', { method: 'DELETE', token });

export const checkout = (token: string, body: { note?: string }) =>
  api<ApiResource<Order>>('/checkout', { method: 'POST', body: JSON.stringify(body), token });
