import type { Product } from '@/lib/api/types';
import ProductCard from '@/components/product/ProductCard';

interface Props {
  title: string;
  items: Product[];
  /** Pass-through CSS class on the outer `<section>` — picked per game by the
   *  catalog page so the HTML's spacing alternation (`shop__area`,
   *  `shop__area-two`, plus `section-py-60` / `section-pb-130` utilities) is
   *  preserved 1:1 with shop.html / shop-2.html / shop-3.html. */
  sectionClassName: string;
}

export default function ShopSection({ title, items, sectionClassName }: Props) {
  return (
    <section className={sectionClassName}>
      <div className="container">
        <div className="row">
          <div className="col-lg-12">
            <div className="section__title section__title-two text-center mb-25">
              <h2 className="title">{title}</h2>
              <img src="/img/images/title_shape.svg" alt="shape" />
            </div>
          </div>
        </div>
        <div className="row gutter-y-24">
          {items.map((product) => (
            <div key={product.id} className="col-xl-3 col-lg-4 col-sm-6">
              <ProductCard product={product} />
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
