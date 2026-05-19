import { notFound } from 'next/navigation';
import { getProduct } from '@/lib/api/endpoints';
import { accountTypeToLayout } from '@/lib/api/layout';
import ProductDetailBreadcrumb from '@/components/product/detail/ProductDetailBreadcrumb';
import ValorantAgentsBody from '@/components/product/detail/ValorantAgentsBody';
import FortniteLockerBody from '@/components/product/detail/FortniteLockerBody';
import SimpleDescriptionBody from '@/components/product/detail/SimpleDescriptionBody';
import RelatedProductsSlider from '@/components/product/detail/RelatedProductsSlider';

export const dynamic = 'force-dynamic';

interface Props {
  params: Promise<{ game: string; slug: string }>;
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
