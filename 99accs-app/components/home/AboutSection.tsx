import { AboutImage } from './about/AboutImage';
import { AboutContent } from './about/AboutContent';
import type { HomeAbout } from '@/lib/api/types';

interface AboutSectionProps {
  data: HomeAbout;
}
export default function AboutSection({ data }: AboutSectionProps) {
  return (
    <section
      className="about__area section__bg about__bg"
      style={{ backgroundImage: `url(${data.background_image})` }}
    >
      <div className="container">
        <div className="row align-items-center">
          <div className="col-lg-6">
            <AboutImage src={data.image} />
          </div>
          <div className="col-lg-6">
            <AboutContent data={data} />
          </div>
        </div>
      </div>
      <div className="bg__overlay"></div>
      <div className="bg__overlay-top"></div>
    </section>
  );
}
