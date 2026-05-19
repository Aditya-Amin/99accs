import type { FeaturesHeading as FeaturesHeadingType } from '@/lib/api/types';

// --- Left column heading for features section ---
export function FeaturesHeading({ heading }: { heading: FeaturesHeadingType }) {
  return (
    <div className="features__content-wrap">
      <div className="section__title">
        <h2 className="title">
          {heading.prefix}<span>{heading.user_count.toLocaleString()}</span>{heading.suffix}
        </h2>
      </div>
    </div>
  );
}
