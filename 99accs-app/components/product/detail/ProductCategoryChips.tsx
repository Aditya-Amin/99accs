import Link from 'next/link';
import { CATEGORY_ICONS } from '@/components/icons';
import type { ProductCategory } from '@/lib/api/types';

interface ProductCategoryChipsProps {
  categories: ProductCategory[];
  game: string;
  extraChip?: { label: string; icon?: React.ReactNode };
}

export default function ProductCategoryChips({ categories, game, extraChip }: ProductCategoryChipsProps) {
  const cats = categories ?? [];
  if (!cats.length && !extraChip) return null;

  return (
    <ul className="shop__tag-wrap list-wrap">
      {cats.map((cat) => {
        const Icon = CATEGORY_ICONS[cat.icon];
        return (
          <li key={cat.id}>
            <Link href={`/product-category/${game}`}>
              {Icon ? <Icon /> : null}
              {cat.label}
            </Link>
          </li>
        );
      })}
      {extraChip && (
        <li>
          <Link href={`/product-category/${game}`}>
            {extraChip.icon}
            {extraChip.label}
          </Link>
        </li>
      )}
    </ul>
  );
}
