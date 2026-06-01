import Link from 'next/link';
import { notFound } from 'next/navigation';
import { getProducts } from '@/lib/api/server';
import type { Game, Product, ProductSection } from '@/lib/api/types';
import ShopFilters from '@/components/shop/ShopFilters';
import ShopSection from '@/components/shop/ShopSection';

export const dynamic = 'force-dynamic';

const GAME_META: Record<string, { title: string; icon: string }> = {
  valorant: { title: 'Valorant',           icon: '/img/icons/valorant.svg' },
  fortnite: { title: 'Fortnite',           icon: '/img/icons/header_cat02.svg' },
  legends:  { title: 'League Of Legends',  icon: '/img/icons/header_cat03.svg' },
};

// Per-game CSS class for each section's outer `<section>`, in section.order.
// Mirrors the wrapper class HTML uses on shop.html / shop-2.html / shop-3.html
// so vertical rhythm and shop__area vs shop__area-two styling match exactly.
const SECTION_WRAPPER_CLASSES: Record<Game, string[]> = {
  valorant: [
    'shop__area',
    'shop__area-two section-pt-60 section-pb-130',
  ],
  fortnite: [
    'shop__area',
    'shop__area section-py-60',
    'shop__area-two section-pb-130',
  ],
  legends: [
    'shop__area-two',
    'shop__area section-py-60',
    'shop__area section-pb-130',
  ],
};

interface Props {
  params: Promise<{ slug: string }>;
  searchParams: Promise<Record<string, string>>;
}

interface SectionGroup {
  section: ProductSection;
  items: Product[];
}

function groupBySection(products: Product[]): SectionGroup[] {
  const buckets = new Map<string, SectionGroup>();
  for (const p of products) {
    if (!p.section) continue;
    let g = buckets.get(p.section.slug);
    if (!g) {
      g = { section: p.section, items: [] };
      buckets.set(p.section.slug, g);
    }
    g.items.push(p);
  }
  return Array.from(buckets.values()).sort((a, b) => a.section.order - b.section.order);
}

export default async function ProductCategoryPage({ params, searchParams }: Props) {
  const { slug } = await params;
  const sp = await searchParams;

  if (!GAME_META[slug]) notFound();

  const meta = GAME_META[slug];
  const page = parseInt(sp.page ?? '1');

  const { data: products, meta: pagination } = await getProducts({
    game: slug as Game,
    account_type: sp.account_type as never,
    section: sp.section,
    min_price: sp.min_price ? parseFloat(sp.min_price) : undefined,
    max_price: sp.max_price ? parseFloat(sp.max_price) : undefined,
    rank: sp.rank,
    region: sp.region,
    sort: sp.sort as never,
    search: sp.search,
    page,
    per_page: 48,
  });

  const groups = groupBySection(products);
  const wrappers = SECTION_WRAPPER_CLASSES[slug as Game] ?? ['shop__area'];

  return (
    <main className="main-area fix">
      <div className="area__bg">
        <div className="shop__filter-bg section__bg" style={{ backgroundImage: 'url(/img/bg/shop_filter_bg.jpg)' }}>
          <div className="bg__overlay"></div>
        </div>
      </div>

      <div className="shop__filter-area">
        <div className="container">
          <div className="shop__filter-title">
            <h2 className="title">{meta.title}</h2>
            <img src={meta.icon} alt={meta.title} />
          </div>
          <ShopFilters game={slug} currentParams={sp} />
        </div>
      </div>

      {products.length === 0 ? (
        <section className="shop__area section-py-120">
          <div className="container">
            <div className="text-center" style={{ padding: '60px 0' }}>
              <p>No products found. Try adjusting your filters.</p>
            </div>
          </div>
        </section>
      ) : (
        <>
          {groups.map((group, idx) => (
            <ShopSection
              key={group.section.slug}
              title={group.section.label}
              items={group.items}
              sectionClassName={wrappers[idx] ?? wrappers[wrappers.length - 1] ?? 'shop__area'}
            />
          ))}

          {pagination.last_page > 1 && (
            <div className="container">
              <div className="pagination__wrap text-center mt-50 mb-130">
                <ul className="list-wrap">
                  {Array.from({ length: pagination.last_page }, (_, i) => i + 1).map((p) => {
                    const urlParams = new URLSearchParams({ ...sp, page: String(p) });
                    return (
                      <li key={p} className={p === pagination.current_page ? 'active' : ''}>
                        <Link href={`/product-category/${slug}?${urlParams.toString()}`}>{p}</Link>
                      </li>
                    );
                  })}
                </ul>
              </div>
            </div>
          )}
        </>
      )}
    </main>
  );
}
