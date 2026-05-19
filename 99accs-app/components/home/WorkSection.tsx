import { WorkContentWrap } from './work/WorkSteps';
import { WorkImages } from './work/WorkImages';
import type { HomeWork } from '@/lib/api/types';

interface WorkSectionProps {
  data: HomeWork;
}
export default function WorkSection({ data }: WorkSectionProps) {
  return (
    <section
      className="work__area section__bg section-py-130"
      style={{ backgroundImage: `url(${data.background_image})` }}
    >
      <div className="container">
        <div className="row align-items-center">
          <div className="col-lg-5">
            <WorkContentWrap title={data.title} steps={data.steps} />
          </div>
          <div className="col-lg-7">
            <WorkImages images={data.images} />
          </div>
        </div>
      </div>
      <div className="bg__overlay"></div>
      <div className="bg__overlay-top"></div>
    </section>
  );
}
