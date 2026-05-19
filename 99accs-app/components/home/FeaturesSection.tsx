import { FeaturesHeading } from './features/FeaturesHeading';
import { FeaturesGrid } from './features/FeaturesGrid';
import type { HomeFeatures } from '@/lib/api/types';

interface FeaturesSectionProps {
  data: HomeFeatures;
}
export default function FeaturesSection({ data }: FeaturesSectionProps) {
  return (
    <section
      className="features__area section__bg section-py-130"
      style={{ backgroundImage: `url(${data.background_image})` }}
    >
      <div className="container">
        <div className="row">
          <div className="col-lg-4">
            <FeaturesHeading heading={data.heading} />
          </div>
          <div className="col-lg-8">
            <FeaturesGrid items={data.items} />
          </div>
        </div>
      </div>
      <div className="bg__overlay"></div>
      <div className="bg__overlay-top"></div>
    </section>
  );
}
