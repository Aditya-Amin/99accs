'use client';
import { useEffect, useRef } from 'react';
import ProductCard from '@/components/product/ProductCard';
import type { Product } from '@/lib/api/types';

interface RelatedProductsSliderProps {
  products: Product[];
}

export default function RelatedProductsSlider({ products }: RelatedProductsSliderProps) {
  const ref = useRef<HTMLDivElement | null>(null);

  useEffect(() => {
    if (typeof window === 'undefined' || !window.Swiper || !ref.current) return;
    const sw = new window.Swiper(ref.current, {
      slidesPerView: 4,
      spaceBetween: 24,
      breakpoints: {
        320: { slidesPerView: 1 },
        576: { slidesPerView: 2 },
        992: { slidesPerView: 3 },
        1200: { slidesPerView: 4 },
      },
    });
    return () => sw.destroy?.(true, true);
  }, [products.length]);

  if (!products.length) return null;

  return (
    <section className="shop__area section-py-130">
      <div className="container">
        <div className="row">
          <div className="col-lg-12">
            <div className="section__title section__title-two text-center mb-25">
              <h2 className="title">Related products</h2>
              <img src="/img/images/title_shape.svg" alt="shape" />
            </div>
          </div>
        </div>
        <div className="swiper-container related-post-active fix" ref={ref}>
          <div className="swiper-wrapper">
            {products.map((p) => (
              <div className="swiper-slide" key={p.id}>
                <ProductCard product={p} />
              </div>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}
