import { TestimonialsSectionTitle } from './testimonials/TestimonialsSectionTitle';
import { TestimonialsCarousel } from './testimonials/TestimonialsCarousel';
import type { HomeTestimonials } from '@/lib/api/types';

interface TestimonialsSectionProps {
  data: HomeTestimonials;
}
export default function TestimonialsSection({ data }: TestimonialsSectionProps) {
  return (
    <section
      className="testimonial__area section__bg"
      style={{ backgroundImage: `url(${data.background_image})` }}
    >
      <div className="container">
        <div className="row">
          <TestimonialsSectionTitle title={data.title} />
        </div>
        <TestimonialsCarousel testimonials={data.items} />
      </div>
      <div className="bg__overlay"></div>
      <div className="bg__overlay-top"></div>
    </section>
  );
}
