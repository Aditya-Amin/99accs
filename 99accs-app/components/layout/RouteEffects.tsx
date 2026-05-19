'use client';
import { useEffect, useRef } from 'react';
import { usePathname } from 'next/navigation';

declare global {
  interface Window {
    jQuery?: unknown;
    $?: unknown;
    particlesJS?: (id: string, config: unknown) => void;
    pJSDom?: Array<{ pJS?: { fn?: { vendors?: { destroypJS?: () => void } } } }>;
    WOW?: new (opts: unknown) => { init: () => void };
  }
}

const PARTICLE_CONFIG = {
  particles: {
    number: { value: 80, density: { enable: true, value_area: 800 } },
    color: { value: '#00FC70' },
    shape: { type: 'circle', stroke: { width: 0, color: '#000000' }, polygon: { nb_sides: 3 } },
    opacity: { value: 0.5, random: false, anim: { enable: false, speed: 1, opacity_min: 0.1, sync: false } },
    size: { value: 3, random: true, anim: { enable: false, speed: 40, size_min: 0.1, sync: false } },
    line_linked: { enable: false, distance: 150, color: '#00FC70', opacity: 0.4, width: 1 },
    move: {
      enable: true, speed: 3, direction: 'none', random: false, straight: false,
      out_mode: 'out', bounce: false, attract: { enable: false, rotateX: 600, rotateY: 1200 },
    },
  },
  interactivity: {
    detect_on: 'canvas',
    events: {
      onhover: { enable: false, mode: 'repulse' },
      onclick: { enable: true, mode: 'push' },
      resize: true,
    },
    modes: {
      grab: { distance: 400, line_linked: { opacity: 1 } },
      bubble: { distance: 400, size: 40, duration: 2, opacity: 8, speed: 3 },
      repulse: { distance: 200, duration: 0.4 },
      push: { particles_nb: 4 },
      remove: { particles_nb: 2 },
    },
  },
  retina_detect: true,
};

const TESTIMONIAL_SWIPER_CONFIG = {
  slidesPerView: 4,
  spaceBetween: 0,
  loop: true,
  breakpoints: {
    1500: { slidesPerView: 4 },
    1200: { slidesPerView: 4 },
    992: { slidesPerView: 3 },
    768: { slidesPerView: 2 },
    576: { slidesPerView: 2 },
    0: { slidesPerView: 1 },
  },
  pagination: { el: '.testimonial-pagination', clickable: true },
};

function formatNumber(v: number, decimals: number, withCommas: boolean): string {
  const fixed = v.toFixed(decimals);
  if (!withCommas) return fixed;
  const [intPart, decPart] = fixed.split('.');
  const grouped = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  return decPart !== undefined ? grouped + '.' + decPart : grouped;
}

function cleanupStaleParticles(divId: string) {
  if (!window.pJSDom || !Array.isArray(window.pJSDom)) {
    window.pJSDom = [];
    return;
  }
  // Snapshot first — particles.js's destroypJS() mutates window.pJSDom as a side
  // effect (sets it to null/empty), so iterating + splicing the live array crashes.
  const snapshot = window.pJSDom.slice();
  const survivors: typeof snapshot = [];
  for (const inst of snapshot) {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const canvas: HTMLCanvasElement | undefined = (inst as any)?.pJS?.canvas?.el;
    const detached = !canvas || !canvas.isConnected;
    const sameDiv = canvas?.parentElement?.id === divId;
    if (detached || sameDiv) {
      try {
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        (inst as any)?.pJS?.fn?.vendors?.destroypJS?.();
      } catch { /* noop */ }
    } else {
      survivors.push(inst);
    }
  }
  // Reassign the registry — overwrites whatever destroypJS left behind
  window.pJSDom = survivors;
}

export default function RouteEffects() {
  const pathname = usePathname();
  const workIntervalRef = useRef<number | null>(null);

  useEffect(() => {
    if (typeof window === 'undefined') return;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const $: any = (window as any).jQuery ?? (window as any).$;

    const raf = requestAnimationFrame(() => {
      // [data-background] → background-image
      if ($) {
        $('[data-background]').each(function (this: HTMLElement) {
          const el = $(this);
          const bg = el.attr('data-background');
          if (bg) el.css('background-image', 'url(' + bg + ')');
        });
        $('[data-bg-color]').each(function (this: HTMLElement) {
          const el = $(this);
          const c = el.attr('data-bg-color');
          if (c) el.css('background-color', c);
        });
      }

      // particles — ensure pJSDom exists, drop stale/detached instances, then init when needed
      if (typeof window.particlesJS === 'function') {
        ['banner-particles', 'cta-particles'].forEach((pid) => {
          const div = document.getElementById(pid);
          if (!div) return;
          if (div.querySelector('canvas')) return;
          cleanupStaleParticles(pid);
          try {
            window.particlesJS!(pid, PARTICLE_CONFIG);
          } catch {
            // particles.js can throw if pJSDom got into a weird state; reset and retry once
            window.pJSDom = [];
            try { window.particlesJS!(pid, PARTICLE_CONFIG); } catch { /* give up silently */ }
          }
        });
      }

      // counter animation — IntersectionObserver based (the bundled jquery.counterup
      // depends on jquery-waypoints which isn't loaded, so we drive the count ourselves)
      document.querySelectorAll<HTMLElement>('.counter-number').forEach((el) => {
        if (el.dataset.counterupDone === 'true' || el.dataset.counterupBound === 'true') return;
        const raw = (el.dataset.num ?? el.textContent ?? '').trim();
        const target = parseFloat(raw.replace(/,/g, ''));
        if (!Number.isFinite(target)) return;

        const hasCommas = /[0-9]+,[0-9]+/.test(raw);
        const decimals = (raw.replace(/,/g, '').split('.')[1] ?? '').length;
        el.dataset.counterupBound = 'true';
        el.dataset.num = raw;
        el.textContent = formatNumber(0, decimals, hasCommas);

        const start = (): void => {
          if (el.dataset.counterupDone === 'true') return;
          el.dataset.counterupDone = 'true';
          const duration = 2000;
          const t0 = performance.now();
          const tick = (now: number) => {
            const p = Math.min(1, (now - t0) / duration);
            const eased = 1 - Math.pow(1 - p, 3);
            const v = target * eased;
            el.textContent = formatNumber(v, decimals, hasCommas);
            if (p < 1) requestAnimationFrame(tick);
            else el.textContent = formatNumber(target, decimals, hasCommas);
          };
          requestAnimationFrame(tick);
        };

        if ('IntersectionObserver' in window) {
          const io = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
              if (entry.isIntersecting) {
                start();
                io.disconnect();
              }
            });
          }, { threshold: 0.2 });
          io.observe(el);
        } else {
          start();
        }
      });

      // WOW — fresh instance picks up unanimated nodes
      if (typeof window.WOW === 'function') {
        try {
          new window.WOW({
            boxClass: 'wow',
            animateClass: 'animated',
            offset: 0,
            mobile: false,
            live: true,
          }).init();
        } catch { /* noop */ }
      }

      // jarallax — has its own guard for already-initialized elements
      if ($ && $.fn?.jarallax) {
        $('.tg-jarallax').jarallax({ speed: 0.2 });
      }

      // magnific popup — re-bind triggers
      if ($ && $.fn?.magnificPopup) {
        $('.popup-image').magnificPopup({ type: 'image', gallery: { enabled: true } });
        $('.popup-video').magnificPopup({ type: 'iframe' });
      }

      // testimonial swiper — re-init if the element exists and isn't already a Swiper
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      const Swiper: any = (window as any).Swiper;
      if (Swiper) {
        document.querySelectorAll('.testimonial-active').forEach((el) => {
          if (!el.classList.contains('swiper-initialized')) {
            new Swiper(el, TESTIMONIAL_SWIPER_CONFIG);
          }
        });
      }

      // "How does it work?" work-section auto-cycler — clear any previous interval
      // (from a prior route visit) and re-bind to the fresh DOM. main.js's original
      // setInterval holds closures over removed nodes and never re-attaches.
      if (workIntervalRef.current !== null) {
        clearInterval(workIntervalRef.current);
        workIntervalRef.current = null;
      }
      if ($) {
        const workItems = $('.work__item');
        const workImages = $('.work__img');
        if (workItems.length > 0 && workImages.length > 0) {
          let idx = 0;
          const showItem = (i: number) => {
            workItems.removeClass('active');
            workItems.find('.work__content').slideUp();
            workImages.removeClass('active').hide();
            const item = workItems.eq(i);
            const image = workImages.eq(i);
            item.addClass('active');
            item.find('.work__content').slideDown();
            image.addClass('active').fadeIn();
          };
          showItem(0);
          workIntervalRef.current = window.setInterval(() => {
            idx = (idx + 1) % workItems.length;
            showItem(idx);
          }, 6000);
          // delegated click handler — `.off(ns)` first prevents stacking after re-renders
          $(document).off('click.work-cycle').on(
            'click.work-cycle',
            '.work__item-button',
            function (this: HTMLElement) {
              const clickedItem = $(this).parent('.work__item');
              idx = workItems.index(clickedItem);
              if (workIntervalRef.current !== null) {
                clearInterval(workIntervalRef.current);
              }
              showItem(idx);
              workIntervalRef.current = window.setInterval(() => {
                idx = (idx + 1) % workItems.length;
                showItem(idx);
              }, 6000);
            }
          );
        } else {
          $(document).off('click.work-cycle');
        }
      }

      // cart-plus-minus +/- button injection (idempotent — skip if already prepended)
      if ($) {
        const minusSVG = '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14.25 9.75C14.25 9.94891 14.171 10.1397 14.0303 10.2803C13.8897 10.421 13.6989 10.5 13.5 10.5H6C5.80109 10.5 5.61033 10.421 5.46967 10.2803C5.32902 10.1397 5.25 9.94891 5.25 9.75C5.25 9.55109 5.32902 9.36032 5.46967 9.21967C5.61033 9.07902 5.80109 9 6 9H13.5C13.6989 9 13.8897 9.07902 14.0303 9.21967C14.171 9.36032 14.25 9.55109 14.25 9.75Z" fill="currentColor"/></svg>';
        const plusSVG = '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14.25 9.75C14.25 9.94891 14.171 10.1397 14.0303 10.2803C13.8897 10.421 13.6989 10.5 13.5 10.5H10.5V13.5C10.5 13.6989 10.421 13.8897 10.2803 14.0303C10.1397 14.171 9.94892 14.25 9.75 14.25C9.55109 14.25 9.36033 14.171 9.21967 14.0303C9.07902 13.8897 9 13.6989 9 13.5V10.5H6C5.80109 10.5 5.61033 10.421 5.46967 10.2803C5.32902 10.1397 5.25 9.94891 5.25 9.75C5.25 9.55109 5.32902 9.36032 5.46967 9.21967C5.61033 9.07902 5.80109 9 6 9H9V6C9 5.80109 9.07902 5.61032 9.21967 5.46967C9.36033 5.32902 9.55109 5.25 9.75 5.25C9.94892 5.25 10.1397 5.32902 10.2803 5.46967C10.421 5.61032 10.5 5.80109 10.5 6V9H13.5C13.6989 9 13.8897 9.07902 14.0303 9.21967C14.171 9.36032 14.25 9.55109 14.25 9.75Z" fill="currentColor"/></svg>';
        $('.cart-plus-minus').each(function (this: HTMLElement) {
          const el = $(this);
          if (!el.children('.dec.qtybutton').length) el.prepend('<div class="dec qtybutton">' + minusSVG + '</div>');
          if (!el.children('.inc.qtybutton').length) el.append('<div class="inc qtybutton">' + plusSVG + '</div>');
        });
      }
    });

    return () => cancelAnimationFrame(raf);
  }, [pathname]);

  return null;
}
