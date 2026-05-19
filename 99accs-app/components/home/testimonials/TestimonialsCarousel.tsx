import { TestimonialSlide } from './TestimonialSlide';
import type { Testimonial } from '@/lib/api/types';

// --- Swiper pagination dots ---
export function TestimonialsPagination() {
  return <div className="testimonial-pagination"></div>;
}

// --- Full Swiper carousel wrapper ---
interface TestimonialsCarouselProps {
  testimonials: Testimonial[];
}
export function TestimonialsCarousel({ testimonials }: TestimonialsCarouselProps) {
  return (
    <div className="testimonial__item-wrap">
      <div className="swiper-container testimonial-active fix">
        <div className="swiper-wrapper">
          {testimonials.map((item) => (
            <TestimonialSlide key={item.id} item={item} />
          ))}
        </div>
      </div>
      <TestimonialsPagination />
    </div>
  );
}
