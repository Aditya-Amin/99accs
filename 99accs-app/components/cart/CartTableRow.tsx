'use client';
import Link from 'next/link';
import { IconClose } from '@/components/icons';
import { QuantityStepper } from '@/components/ui/QuantityStepper';
import type { CartLineItem } from '@/lib/store/cartStore';

interface Props {
  line: CartLineItem;
  onUpdateQty: (productId: number, qty: number) => void;
  onRemove: (productId: number) => void;
}

export function CartTableRow({ line, onUpdateQty, onRemove }: Props) {
  const { product, quantity } = line;
  const detailHref = `/shop/${product.game}/${product.slug}`;

  return (
    <tr>
      <td className="product__remove">
        <a
          href="#"
          onClick={(e) => { e.preventDefault(); onRemove(product.id); }}
          aria-label={`Remove ${product.title}`}
        >
          <IconClose width={15} height={15} />
        </a>
      </td>
      <td className="product__thumb">
        <Link href={detailHref}>
          <img src={product.images[0] ?? '/img/valorant/skin_img_01.png'} alt="" />
        </Link>
      </td>
      <td className="product__name">
        <Link href={detailHref}>{product.title}</Link>
      </td>
      <td className="product__price">${product.price.toFixed(2)}</td>
      <td className="product__quantity">
        <QuantityStepper value={quantity} onChange={(q) => onUpdateQty(product.id, q)} />
      </td>
      <td className="product__subtotal">${(product.price * quantity).toFixed(2)}</td>
    </tr>
  );
}
