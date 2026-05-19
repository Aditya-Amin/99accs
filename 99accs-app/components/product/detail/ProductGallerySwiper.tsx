'use client';
import { useEffect, useRef } from 'react';

declare global {
  interface Window {
    Swiper?: new (el: HTMLElement | string, opts?: unknown) => { destroy?: (a?: boolean, b?: boolean) => void };
  }
}

interface ProductGallerySwiperProps {
  images: string[];
}

export default function ProductGallerySwiper({ images }: ProductGallerySwiperProps) {
  const navRef = useRef<HTMLDivElement | null>(null);
  const mainRef = useRef<HTMLDivElement | null>(null);

  useEffect(() => {
    if (typeof window === 'undefined' || !window.Swiper) return;
    if (!navRef.current || !mainRef.current) return;

    const nav = new window.Swiper(navRef.current, {
      spaceBetween: 16,
      slidesPerView: 3,
      watchSlidesProgress: true,
      breakpoints: {
        320: { slidesPerView: 3 },
        768: { slidesPerView: 4 },
        1200: { slidesPerView: 5 },
      },
    });

    const main = new window.Swiper(mainRef.current, {
      spaceBetween: 10,
      effect: 'fade',
      navigation: {
        nextEl: '.thumb-button-next',
        prevEl: '.thumb-button-prev',
      },
      thumbs: { swiper: nav },
    });

    return () => {
      main.destroy?.(true, true);
      nav.destroy?.(true, true);
    };
  }, []);

  return (
    <div className="shop__details-thumb shop__details-thumb-three">
      <div className="shop__details-thumb-top">
        <div className="swiper thumbTab" ref={mainRef}>
          <div className="swiper-wrapper">
            {images.map((src) => (
              <div className="swiper-slide" key={src}>
                <div className="slider-item">
                  <img src={src} alt="" />
                </div>
              </div>
            ))}
          </div>
        </div>
        <div className="shop__details-thumb-nav">
          <button className="thumb-button-prev" aria-label="Previous image">
            <svg width="9" height="17" viewBox="0 0 9 17" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M0.220911 7.7198L7.72091 0.219797C7.86164 0.0790611 8.05251 6.59283e-08 8.25154 6.59283e-08C8.45056 6.59283e-08 8.64143 0.0790612 8.78216 0.219797C8.92289 0.360528 9.00195 0.551399 9.00195 0.750422C9.00195 0.949445 8.92289 1.14032 8.78216 1.28105L1.81185 8.25042L8.78216 15.2198C8.92289 15.3605 9.00195 15.5514 9.00195 15.7504C9.00195 15.9494 8.92289 16.1403 8.78216 16.281C8.64143 16.4218 8.45056 16.5008 8.25154 16.5008C8.05251 16.5008 7.86164 16.4218 7.72091 16.281L0.220911 8.78105C0.151178 8.71139 0.0958593 8.62868 0.0581157 8.53763C0.0203721 8.44658 0.000946758 8.34898 0.000946766 8.25042C0.000946775 8.15186 0.0203722 8.05427 0.0581158 7.96322C0.0958593 7.87217 0.151178 7.78945 0.220911 7.7198Z" fill="currentColor" />
            </svg>
          </button>
          <button className="thumb-button-next" aria-label="Next image">
            <svg width="9" height="17" viewBox="0 0 9 17" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M8.78104 7.7198L1.28104 0.219797C1.14031 0.0790611 0.94944 1.94143e-07 0.750417 1.94143e-07C0.551394 1.94143e-07 0.360522 0.0790612 0.219792 0.219797C0.0790611 0.360528 -8.44855e-08 0.551399 -6.56035e-08 0.750422C-4.67216e-08 0.949445 0.0790611 1.14032 0.219792 1.28105L7.1901 8.25042L0.219792 15.2198C0.0790611 15.3605 -8.44855e-08 15.5514 -6.56035e-08 15.7504C-4.67216e-08 15.9494 0.0790611 16.1403 0.219792 16.281C0.360522 16.4218 0.551394 16.5008 0.750417 16.5008C0.94944 16.5008 1.14031 16.4218 1.28104 16.281L8.78104 8.78105C8.85077 8.71139 8.90609 8.62868 8.94384 8.53763C8.98158 8.44658 9.00101 8.34898 9.00101 8.25042C9.00101 8.15186 8.98158 8.05427 8.94384 7.96322C8.90609 7.87217 8.85077 7.78945 8.78104 7.7198Z" fill="currentColor" />
            </svg>
          </button>
        </div>
      </div>
      <div className="shop__details-thumb-bottom">
        <div className="swiper navSwiper" ref={navRef}>
          <div className="swiper-wrapper">
            {images.map((src, i) => (
              <div className={`swiper-slide tab-nav${i === 0 ? ' active' : ''}`} key={src}>
                <img src={src} alt="img" />
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}
