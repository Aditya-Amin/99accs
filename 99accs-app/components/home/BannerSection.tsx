import { BannerContent } from './banner/BannerContent';
import { BannerFeatureList } from './banner/BannerFeatureList';
import { BannerCatWrap } from './banner/BannerCategories';
import type { HomeBanner } from '@/lib/api/types';

interface BannerSectionProps {
  data: HomeBanner;
}
export default function BannerSection({ data }: BannerSectionProps) {
  return (
    <section className="banner__area section__bg banner__bg" style={{ backgroundImage: `url(${data.background_image})` }}>
      <div id="banner-particles"></div>
      <div className="container">
        <div className="row justify-content-center">
          <div className="col-lg-8">
            <BannerContent subtitle={data.subtitle} heading={data.heading} description={data.description} />
            <BannerFeatureList features={data.features} />
            <BannerCatWrap categories={data.categories} />
          </div>
        </div>
      </div>
      <div className="bg__overlay"></div>
    </section>
  );
}
