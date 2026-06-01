import { notFound } from 'next/navigation';
import { getProduct } from '@/lib/api/server';
import { accountTypeToLayout } from '@/lib/api/layout';
import ProductDetailBreadcrumb from '@/components/product/detail/ProductDetailBreadcrumb';
import ValorantAgentsBody from '@/components/product/detail/ValorantAgentsBody';
import FortniteLockerBody from '@/components/product/detail/FortniteLockerBody';
import SimpleDescriptionBody from '@/components/product/detail/SimpleDescriptionBody';
import RelatedProductsSlider from '@/components/product/detail/RelatedProductsSlider';
import type { Metadata } from 'next';

export const dynamic = 'force-dynamic';

interface Props {
  params: Promise<{ slug: string }>;
}

export async function generateMetadata({ params }: Props): Promise<Metadata> {
  const { slug } = await params;

  try {
    const res = await getProduct(slug);
    const product = res.data;

    const title =
      product.meta_title ||
      `${product.title} | 99accs`;

    const description =
      product.meta_description ||
      (product.description ? product.description.slice(0, 160) : `Buy ${product.title} at 99accs.`);

    return {
      title,
      description,
      keywords: product.meta_keywords ?? undefined,
      alternates: {
        canonical: product.canonical_url || `/product/${product.slug}`,
      },
      openGraph: {
        title,
        description,
        url: `/product/${product.slug}`,
        siteName: '99accs',
        images: product.images[0] ? [{ url: product.images[0] }] : undefined,
        type: 'website',
      },
      twitter: {
        card: 'summary_large_image',
        title,
        description,
        images: product.images[0] ? [product.images[0]] : undefined,
      },
    };
  } catch {
    return { title: 'Product not found | 99accs' };
  }
}

export default async function ProductDetailPage({ params }: Props) {
  const { slug } = await params;

  let product;
  try {
    const res = await getProduct(slug);
    product = res.data;
  } catch {
    notFound();
  }

  const layout = accountTypeToLayout(product.account_type);
  const related = product.related ?? [];

  // Related slider only on the two simple layouts — the HTML source pages for
  // `rich` (shop-details.html) and `fortnite_four` (shop-details-4.html) don't
  // include a related-products section.
  const showRelated = layout === 'simple_two' || layout === 'simple_three';

  return (
    <main className="main-area fix">
      <ProductDetailBreadcrumb product={product} />

      {layout === 'rich'           && <ValorantAgentsBody    product={product} />}
      {layout === 'fortnite_four'  && <FortniteLockerBody    product={product} />}
      {(layout === 'simple_two' || layout === 'simple_three') && (
        <SimpleDescriptionBody product={product} layout={layout} />
      )}

      {showRelated && <RelatedProductsSlider products={related} />}
    </main>
  );
}
