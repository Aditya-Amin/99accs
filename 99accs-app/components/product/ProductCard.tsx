'use client';
import Link from 'next/link';
import type { Product } from '@/lib/api/types';
import { useCartStore } from '@/lib/store/cartStore';
import { accountTypeToLayout } from '@/lib/api/layout';
import { CATEGORY_ICONS, IconGallery, IconDiscount } from '@/components/icons';

interface Props {
  product: Product;
}

export default function ProductCard({ product }: Props) {
  const addItem = useCartStore((s) => s.addItem);

  // The four shop-details layouts split visually into two card shapes:
  //  - simple_two cards use `shop__thumb` (no gallery, no badge icon, no discount badge in HTML)
  //  - everything else uses `shop__thumb shop__thumb-two` (gallery-capable, badge-capable)
  const layout = accountTypeToLayout(product.account_type);
  const useThumbTwo = layout !== 'simple_two';

  const detailHref = `/shop/${product.game}/${product.slug}`;
  const showGallery = !!product.has_gallery && product.images.length > 1;
  const showCountry = !!product.country.code;
  const countryClass = product.country.class_modifier ?? product.country.code.toLowerCase();

  return (
    <div className="shop__item">
      <div className={useThumbTwo ? 'shop__thumb shop__thumb-two' : 'shop__thumb'}>
        <Link href={detailHref}>
          <img src={product.images[0] ?? '/img/valorant/skin_img_01.png'} alt={product.title} />
        </Link>

        {showGallery && (
          <div className="shop__thumb-gallery">
            <div className="shop__thumb-popup">
              <IconGallery />
              {product.images.length}+
            </div>
            <div className="hidden-gallery">
              {product.images.map((img, i) => (
                <a key={i} href={img}></a>
              ))}
            </div>
          </div>
        )}

        {product.discount_percent ? (
          <span className="discount">
            <IconDiscount />
            -{product.discount_percent}% OFF
          </span>
        ) : null}
      </div>

      <div className="shop__content">
        <div className="shop__content-top">
          <h2 className="title">
            <Link href={detailHref}>
              {product.title}
              {product.country.flag && <img src={product.country.flag} alt="icon" />}
            </Link>
          </h2>

          {showCountry && (
            product.badge_icon ? (
              <div className="shop__content-top-right">
                <img src={product.badge_icon} alt="icon" />
                <span className={`country__code ${countryClass}`}>{product.country.code}</span>
              </div>
            ) : (
              <span className={`country__code ${countryClass}`}>{product.country.code}</span>
            )
          )}
        </div>

        {product.categories.length > 0 && (
          <ul className="shop__tag-wrap list-wrap">
            {product.categories.map((cat) => {
              const Icon = CATEGORY_ICONS[cat.icon];
              return (
                <li key={cat.id}>
                  <Link href={`/shop/${product.game}`}>
                    {Icon ? <Icon /> : null}
                    {cat.label}
                  </Link>
                </li>
              );
            })}
          </ul>
        )}

        <h2 className="price">
          ${product.price.toFixed(2)}
          {product.price_max ? ` – $${product.price_max.toFixed(2)}` : ''}
          {product.old_price ? <del>${product.old_price.toFixed(2)}</del> : null}
        </h2>

        <button onClick={() => addItem(product)} className="tg-btn">
          Add to cart
        </button>
      </div>
    </div>
  );
}
