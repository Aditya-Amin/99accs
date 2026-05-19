import type { Product } from '@/lib/api/types';
import valorant from '@/mocks/products/valorant.json';
import fortnite from '@/mocks/products/fortnite.json';
import legends from '@/mocks/products/legends.json';
import { applyDetailTemplate } from './templates';

const all = [...valorant, ...fortnite, ...legends] as unknown as Product[];

export interface MockProductFilters {
  game?: string;
  account_type?: string;
  section?: string;       // catalog section slug — e.g. `verified`, `nfa_inactive`, `las`
  country?: string;
  min_price?: number;
  max_price?: number;
  rank?: string;
  region?: string;
  sort?: string;
  search?: string;
  page?: number;
  per_page?: number;
}

export function getMockProducts(filters: MockProductFilters = {}) {
  const { game, account_type, section, country, min_price, max_price, rank, region, sort, search, page = 1, per_page = 12 } = filters;

  let results = game ? all.filter((p) => p.game === game) : all;
  if (account_type) results = results.filter((p) => p.account_type === account_type);
  if (section) results = results.filter((p) => p.section?.slug === section);
  if (country) results = results.filter((p) => p.country.code === country.toUpperCase());
  if (min_price !== undefined) results = results.filter((p) => p.price >= min_price);
  if (max_price !== undefined) results = results.filter((p) => p.price <= max_price);
  if (rank) results = results.filter((p) => p.rank?.toLowerCase() === rank.toLowerCase());
  if (region) results = results.filter((p) => p.region === region);
  if (search) {
    const q = search.toLowerCase();
    results = results.filter((p) => p.title.toLowerCase().includes(q) || p.description.toLowerCase().includes(q));
  }

  results = [...results];
  if (sort === 'price_asc')  results.sort((a, b) => a.price - b.price);
  if (sort === 'price_desc') results.sort((a, b) => b.price - a.price);
  if (sort === 'newest')     results.sort((a, b) => b.created_at.localeCompare(a.created_at));
  if (sort === 'oldest')     results.sort((a, b) => a.created_at.localeCompare(b.created_at));

  const total = results.length;
  const last_page = Math.max(1, Math.ceil(total / per_page));
  const offset = (page - 1) * per_page;
  const data = results.slice(offset, offset + per_page);

  return {
    data,
    meta: { current_page: page, last_page, per_page, total },
  };
}

export function getMockProduct(slug: string) {
  const p = all.find((p) => p.slug === slug);
  return p ? applyDetailTemplate(p) : null;
}
