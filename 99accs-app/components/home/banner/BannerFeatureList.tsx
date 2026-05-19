import { BannerIconByName } from './BannerContent';
import type { BannerFeature } from '@/lib/api/types';

// --- Feature badge list (high-quality / instant delivery / warranty) ---
export function BannerFeatureList({ features }: { features: BannerFeature[] }) {
  return (
    <div className="banner__features wow fadeInUp" data-wow-delay=".6s">
      <ul className="list-wrap">
        {features.map((f) => (
          <li key={f.id}>
            <BannerIconByName name={f.icon} />
            {f.text}
          </li>
        ))}
      </ul>
    </div>
  );
}
