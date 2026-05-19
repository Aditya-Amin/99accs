import Link from 'next/link';
import { CATEGORY_ICONS, IconDiscount, IconShieldCheck } from '@/components/icons';
import AddToCartButton from '@/components/product/AddToCartButton';
import CartQtyAddToCart from './CartQtyAddToCart';
import ShopDetailsList from './ShopDetailsList';
import ProductGuaranteeBox from './ProductGuaranteeBox';
import type { Product } from '@/lib/api/types';

interface ProductDetailContentProps {
  product: Product;
  variant: 'rich' | 'simple_two' | 'simple_three' | 'fortnite_four';
  showQty?: boolean;
  showCountry?: boolean;
  showGuarantee?: boolean;
}

const VARIANT_CLASS: Record<ProductDetailContentProps['variant'], string> = {
  rich: 'shop__details-content',
  simple_two: 'shop__details-content shop__details-content-two',
  simple_three: 'shop__details-content shop__details-content-three',
  fortnite_four: 'shop__details-content shop__details-content-four',
};

export default function ProductDetailContent({
  product,
  variant,
  showQty = false,
  showCountry = true,
  showGuarantee = false,
}: ProductDetailContentProps) {
  // showCountry only renders the country chip when the product actually has
  // a country code — Fortnite NFA cards intentionally ship `country.code === ''`
  // so the chip is suppressed.
  const renderCountry = showCountry && !!product.country.code;
  const countryClass = product.country.class_modifier ?? product.country.code.toLowerCase();
  const hasTopRow = renderCountry || !!product.discount_percent || !!product.badge_icon;

  return (
    <div className={VARIANT_CLASS[variant]}>
      {hasTopRow && (
        <div className="shop__details-content-top">
          {renderCountry && (
            <div className="shop__content-top-right">
              {product.badge_icon && <img src={product.badge_icon} alt="icon" />}
              <span className={`country__code ${countryClass}`}>{product.country.code}</span>
            </div>
          )}
          {product.discount_percent ? (
            <span className="discount discount-two">
              <IconDiscount />
              -{product.discount_percent}% OFF
            </span>
          ) : null}
        </div>
      )}

      <h2 className="title">
        {product.title}
        {product.country.flag && <img src={product.country.flag} alt="" />}
      </h2>

      {(product.categories.length > 0 || product.last_match_label) && (
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
          {product.last_match_label && (
            <li>
              <Link href={`/shop/${product.game}`}>
                <IconShieldCheck />
                {product.last_match_label}
              </Link>
            </li>
          )}
        </ul>
      )}

      {product.min_quantity && product.min_quantity > 1 ? (
        <span className="quantity__wrap">🔻 Min. Quantity: {product.min_quantity}</span>
      ) : null}

      <ShopDetailsList shortDescription={product.short_description} />

      <div className="shop__details-content-bottom">
        <h2 className="price">
          ${product.price.toFixed(2)}
          {product.price_max ? ` – $${product.price_max.toFixed(2)}` : ''}
          {product.old_price ? <del>${product.old_price.toFixed(2)}</del> : null}
        </h2>
        {showQty ? (
          <CartQtyAddToCart product={product} minQuantity={product.min_quantity ?? 1} />
        ) : (
          <AddToCartButton product={product} />
        )}
      </div>

      {showGuarantee && <ProductGuaranteeBox guarantee={product.guarantee} />}
    </div>
  );
}
