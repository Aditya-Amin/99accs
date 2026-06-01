'use client';
import { CATEGORY_ICONS, IconDiscount } from '@/components/icons';
import type { CartLineItem } from '@/lib/store/cartStore';

interface Props {
  items: CartLineItem[];
}

export function CartOrderSummary({ items }: Props) {
  const subtotal = items.reduce((sum, i) => sum + i.product.price * i.quantity, 0);
  const regularTotal = items.reduce(
    (sum, i) => sum + (i.product.regular_price ?? i.product.price) * i.quantity,
    0,
  );
  const totalDiscount = Math.max(0, regularTotal - subtotal);

  return (
    <div className="order__info-wrap order__info-wrap--v2">
      <div className="order__info-inner">
        <h2 className="title">Your Order</h2>

        <ul className="order-items list-wrap">
          {items.map((line) => {
            const { product, quantity } = line;
            const img = product.images?.[0];
            const countryCode = product.country?.code ?? '';
            const countryClass =
              product.country?.class_modifier ?? countryCode.toLowerCase();
            const cats = (product.categories ?? []).slice(0, 3);
            const hasSecondaryMeta =
              !!product.badge_icon || !!product.discount_percent || quantity > 1;

            return (
              <li key={product.id} className="cos__row">
                <div className="cos__thumb">
                  {img ? (
                    <img src={img} alt={product.title} />
                  ) : (
                    <div className="cos__thumb-fallback" aria-hidden="true" />
                  )}
                </div>

                <div className="cos__body">
                  {/* Title + flag + region badge on one line */}
                  <div className="cos__title-row">
                    <h3 className="cos__title">{product.title}</h3>
                    {product.country?.flag && (
                      <img className="cos__flag" src={product.country.flag} alt="" />
                    )}
                    {countryCode && (
                      <span className={`country__code ${countryClass}`}>{countryCode}</span>
                    )}
                  </div>

                  {/* Badge icon, discount, qty */}
                  {hasSecondaryMeta && (
                    <div className={`cos__meta${cats.length === 0 ? ' cos__meta-last' : ''}`}>
                      {product.badge_icon && (
                        <img className="cos__badge-icon" src={product.badge_icon} alt="" />
                      )}
                      {product.discount_percent ? (
                        <span className="discount discount-two">
                          <IconDiscount />
                          -{product.discount_percent}% OFF
                        </span>
                      ) : null}
                      {quantity > 1 && (
                        <span className="cos__qty">×{quantity}</span>
                      )}
                    </div>
                  )}

                  {/* Category chips */}
                  {cats.length > 0 && (
                    <div className="cos__chips">
                      {cats.map((cat) => {
                        const Icon = CATEGORY_ICONS[cat.icon];
                        return (
                          <span key={cat.id} className="cos__chip">
                            {Icon ? <Icon /> : null}
                            {cat.label}
                          </span>
                        );
                      })}
                    </div>
                  )}
                </div>

                <div className="cos__price">
                  ${(product.price * quantity).toFixed(2)}
                  {product.regular_price && product.regular_price > product.price && (
                    <del className="cos__price-original">
                      ${(product.regular_price * quantity).toFixed(2)}
                    </del>
                  )}
                </div>
              </li>
            );
          })}
        </ul>

        <ul className="cos__totals list-wrap">
          <li className="cos__totals-row">
            <span>Subtotal</span>
            <span>${regularTotal.toFixed(2)}</span>
          </li>
          {totalDiscount > 0 && (
            <li className="cos__totals-row cos__totals-discount">
              <span>Discount</span>
              <span>−${totalDiscount.toFixed(2)}</span>
            </li>
          )}
          <li className="cos__totals-total">
            <span>Total</span>
            <span>${subtotal.toFixed(2)}</span>
          </li>
        </ul>
      </div>
    </div>
  );
}
