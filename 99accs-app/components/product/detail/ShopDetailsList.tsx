import type { ProductHighlight } from '@/lib/api/types';

interface ShopDetailsListProps {
  highlights?: ProductHighlight[] | null;
}

export default function ShopDetailsList({ highlights }: ShopDetailsListProps) {
  if (!highlights || highlights.length === 0) return null;

  return (
    <ul className="shop__details-list list-wrap">
      {highlights.map((h, i) => (
        <li key={i}>
          {h.icon && <img src={h.icon} alt="icon" />}
          {h.label}
        </li>
      ))}
    </ul>
  );
}
