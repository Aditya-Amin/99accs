import { api } from './client';
import type {
  ApiCollection,
  ApiResource,
  FooterData,
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

// Footer + home are global, near-static config rendered into every layout. With
// the client's default `no-store` they'd be re-fetched on every render AND every
// route prefetch, saturating the single-threaded dev server. `force-cache` lets
// one fetch be reused across the whole render tree. (Flushes on server restart
// or an explicit revalidateTag/Path; fine for near-static config.)
export const getHome   = () => api<ApiResource<HomeData>>('/home',   { cache: 'force-cache' });
export const getFooter = () => api<ApiResource<FooterData>>('/footer', { cache: 'force-cache' });

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

// Auth helpers route through the Next.js BFF (app/api/auth/*) so the Sanctum
// token can be stored in an httpOnly cookie that JS cannot read. The BFF
// strips the token from the response body before forwarding to the client.
type AuthBffResponse = { data: { user: AuthUser } };

const bff = async <T>(path: string, init: RequestInit = {}): Promise<T> => {
  const res = await fetch(path, {
    credentials: 'include',
    ...init,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...(init.headers ?? {}),
    },
  });
  const json = await res.json().catch(() => ({}));
  if (!res.ok) {
    const err = json as { code?: string; message?: string; errors?: Record<string, string[]> };
    throw Object.assign(new Error(err.message ?? `Request failed (${res.status})`), {
      status: res.status,
      code: err.code,
      errors: err.errors,
    });
  }
  return json as T;
};

export const login = (email: string, password: string) =>
  bff<AuthBffResponse>('/api/auth/login', {
    method: 'POST',
    body: JSON.stringify({ email, password }),
  });

export const register = (name: string, email: string, password: string, password_confirmation: string) =>
  bff<AuthBffResponse>('/api/auth/register', {
    method: 'POST',
    body: JSON.stringify({ name, email, password, password_confirmation }),
  });

export const forgotPassword = (email: string) =>
  bff<{ message: string }>('/api/auth/forgot-password', {
    method: 'POST',
    body: JSON.stringify({ email }),
  });

export const resetPassword = (token: string, email: string, password: string, password_confirmation: string) =>
  bff<AuthBffResponse>('/api/auth/reset-password', {
    method: 'POST',
    body: JSON.stringify({ token, email, password, password_confirmation }),
  });

export const changePassword = (current_password: string, password: string, password_confirmation: string) =>
  bff<{ message: string }>('/api/auth/change-password', {
    method: 'POST',
    body: JSON.stringify({ current_password, password, password_confirmation }),
  });

export const logout = () =>
  bff<{ message: string }>('/api/auth/logout', { method: 'POST' });

export const getMe = () =>
  bff<{ data: AuthUser | null }>('/api/auth/me');

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

// BFF variant — reads the token from the httpOnly cookie server-side.
// Use this from client components (the token is never exposed to the browser).
export const updateProfileBff = (body: Partial<{
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
  current_password: string;
  password: string;
  password_confirmation: string;
}>) =>
  bff<{ data: AuthUser }>('/api/account/profile', {
    method: 'PATCH',
    body: JSON.stringify(body),
  });

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
