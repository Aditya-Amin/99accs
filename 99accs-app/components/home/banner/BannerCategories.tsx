import Link from 'next/link';
import type { BannerCategory } from '@/lib/api/types';

// --- Single game category image link ---
export function BannerCatItem({ href, image, alt }: { href: string; image: string; alt: string }) {
  return (
    <Link href={href}>
      <img src={image} alt={alt} />
    </Link>
  );
}

// --- Row of all game category images ---
export function BannerCatWrap({ categories }: { categories: BannerCategory[] }) {
  return (
    <div className="banner__cat-wrap wow fadeInUp" data-wow-delay=".8s">
      {categories.map((c) => (
        <BannerCatItem key={c.id} href={c.href} image={c.image} alt={c.alt} />
      ))}
    </div>
  );
}
