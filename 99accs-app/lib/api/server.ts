// Server-side data layer for Server Components.
// Import from here — never from @/lib/api/endpoints — in any page.tsx / layout.tsx.
// When NEXT_PUBLIC_USE_MOCK=true  → reads mock JSON directly (no HTTP roundtrip).
// When NEXT_PUBLIC_USE_MOCK=false → calls the real Laravel API via endpoints.ts.
import type {
  ApiCollection,
  ApiResource,
  FooterData,
  HomeData,
  Product,
  WishlistItem,
  SupportArticle,
} from './types';
import type { ProductFilters } from './endpoints';

export type { ProductFilters };

const USE_MOCK = process.env.NEXT_PUBLIC_USE_MOCK !== 'false';

// ── Home ─────────────────────────────────────────────────────────────────────

export async function getHome(): Promise<ApiResource<HomeData>> {
  const { getMockHome } = await import('@/lib/mock/home');

  if (USE_MOCK) {
    return { data: getMockHome() };
  }

  try {
    const { getHome: _fn } = await import('./endpoints');
    const result = await _fn();
    const api = result.data;
    const mock = getMockHome();

    // Per-section fallback: if a section is absent from the API response, use mock data.
    return {
      data: {
        banner:       api.banner       ?? mock.banner,
        about:        api.about        ?? mock.about,
        work:         api.work         ?? mock.work,
        features:     api.features     ?? mock.features,
        testimonials: api.testimonials ?? mock.testimonials,
        cta:          api.cta          ?? mock.cta,
      },
    };
  } catch {
    // Full fallback when the API is unreachable or returns an error.
    return { data: getMockHome() };
  }
}

// ── Footer ────────────────────────────────────────────────────────────────────

export async function getFooter(): Promise<ApiResource<FooterData>> {
  const { getMockFooter } = await import('@/lib/mock/home');

  if (USE_MOCK) {
    return { data: getMockFooter() };
  }

  try {
    const { getFooter: _fn } = await import('./endpoints');
    return await _fn();
  } catch {
    return { data: getMockFooter() };
  }
}

// ── Products ──────────────────────────────────────────────────────────────────

export async function getProducts(filters: ProductFilters = {}): Promise<ApiCollection<Product>> {
  if (USE_MOCK) {
    const { getMockProducts } = await import('@/lib/mock/products');
    const result = getMockProducts(filters);
    return { ...result, links: { first: null, last: null, next: null, prev: null } } as ApiCollection<Product>;
  }
  const { getProducts: _fn } = await import('./endpoints');
  return _fn(filters);
}

export async function getProduct(slug: string): Promise<ApiResource<Product>> {
  if (USE_MOCK) {
    const { getMockProduct } = await import('@/lib/mock/products');
    const product = getMockProduct(slug);
    if (!product) throw new Error(`Product not found: ${slug}`);
    return { data: product as Product };
  }
  const { getProduct: _fn } = await import('./endpoints');
  return _fn(slug);
}

// ── Support articles ──────────────────────────────────────────────────────────

export async function getSupportArticles(category?: string): Promise<ApiCollection<SupportArticle>> {
  if (USE_MOCK) {
    const mod = await import('@/mocks/support/articles.json');
    const articles = mod.default as unknown as SupportArticle[];
    const results = category ? articles.filter((a) => a.category === category) : articles;
    return {
      data: results,
      meta: { current_page: 1, last_page: 1, per_page: results.length, total: results.length },
      links: { first: null, last: null, next: null, prev: null },
    };
  }
  const { getSupportArticles: _fn } = await import('./endpoints');
  return _fn(category);
}

export async function getSupportArticle(slug: string): Promise<ApiResource<SupportArticle>> {
  if (USE_MOCK) {
    const mod = await import('@/mocks/support/articles.json');
    const articles = mod.default as unknown as SupportArticle[];
    const article = articles.find((a) => a.slug === slug);
    if (!article) throw new Error(`Article not found: ${slug}`);
    return { data: article };
  }
  const { getSupportArticle: _fn } = await import('./endpoints');
  return _fn(slug);
}

// ── Wishlist ──────────────────────────────────────────────────────────────────

export async function getWishlist(_token: string, _page = 1): Promise<ApiCollection<WishlistItem>> {
  if (USE_MOCK) {
    const mod = await import('@/mocks/products/valorant.json');
    const data = (mod.default as unknown as Product[]).slice(0, 3).map((p, i) => ({
      id: i + 1,
      product: p,
      created_at: '2025-03-01T00:00:00Z',
    }));
    return {
      data,
      meta: { current_page: 1, last_page: 1, per_page: 10, total: data.length },
      links: { first: null, last: null, next: null, prev: null },
    };
  }
  const { getWishlist: _fn } = await import('./endpoints');
  return _fn(_token, _page);
}
