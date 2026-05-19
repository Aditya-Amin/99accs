import ProductGallerySingle from './ProductGallerySingle';
import ProductGallerySwiper from './ProductGallerySwiper';
import ProductDetailContent from './ProductDetailContent';
import type { Product } from '@/lib/api/types';

interface SimpleDescriptionBodyProps {
  product: Product;
  // 'simple_two'   → shop-details-2.html (single static image)
  // 'simple_three' → shop-details-3.html (Swiper carousel)
  layout: 'simple_two' | 'simple_three';
}

export default function SimpleDescriptionBody({ product, layout }: SimpleDescriptionBodyProps) {
  const useCarousel = layout === 'simple_three';
  const wrapClass = useCarousel ? 'shop__details-wrap' : 'shop__details-wrap shop__details-wrap-two';

  return (
    <section className="shop__details-area">
      <div className="container">
        <div className={wrapClass}>
          {useCarousel ? (
            <ProductGallerySwiper images={product.images} />
          ) : (
            <ProductGallerySingle
              image={product.images[0] ?? '/img/images/shop_details_img01.png'}
              alt={product.title}
              variant="two"
            />
          )}
          <ProductDetailContent
            product={product}
            variant={layout}
            showQty
            showCountry
            showGuarantee
          />
        </div>

        {product.description && (
          <div className="shop__description-wrap">
            <div dangerouslySetInnerHTML={{ __html: product.description }} />
          </div>
        )}
      </div>
    </section>
  );
}
