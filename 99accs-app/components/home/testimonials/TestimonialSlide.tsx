import { StarRating } from './StarRating';
import type { Testimonial } from '@/lib/api/types';

// --- Single swiper slide ---
interface TestimonialSlideProps {
  item: Testimonial;
}
export function TestimonialSlide({ item }: TestimonialSlideProps) {
  return (
    <div className="swiper-slide">
      <div className="testimonial__item">
        <StarRating count={item.rating} />
        <h2 className="title">{item.title}</h2>
        <p>{item.text}</p>
        <span>{item.author}</span>
      </div>
    </div>
  );
}
