import { CtaParticles, CtaContent } from './cta/CtaContent';
import type { HomeCta } from '@/lib/api/types';

interface CtaSectionProps {
  data: HomeCta;
}
export default function CtaSection({ data }: CtaSectionProps) {
  return (
    <section
      className="cta__area cta__bg section__bg tg-jarallax"
      style={{ backgroundImage: `url(${data.background_image})` }}
    >
      <CtaParticles />
      <div className="container">
        <div className="row justify-content-center">
          <div className="col-lg-6">
            <CtaContent data={data} />
          </div>
        </div>
      </div>
      <div className="bg__overlay-top-two"></div>
    </section>
  );
}
