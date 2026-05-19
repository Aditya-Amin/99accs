import { FeatureItemCard } from './FeatureItem';
import type { FeatureItem } from '@/lib/api/types';

// --- 2x2 grid of feature cards (right column) ---
export function FeaturesGrid({ items }: { items: FeatureItem[] }) {
  return (
    <div className="features__item-wrap">
      <div className="row">
        {items.map((feature) => (
          <div key={feature.id} className="col-md-6">
            <FeatureItemCard title={feature.title} icon={feature.icon} text={feature.text} />
          </div>
        ))}
      </div>
    </div>
  );
}
