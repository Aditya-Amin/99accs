'use client';
import { useScrollTop } from '@/lib/hooks/useScrollTop';
import { IconArrowUp } from '@/components/icons';

export default function ScrollTop() {
  const { visible, scrollToTop } = useScrollTop();

  return (
    <button
      className={`scroll__top scroll-to-target${visible ? ' open' : ''}`}
      onClick={scrollToTop}
      aria-label="Scroll to top"
    >
      <IconArrowUp />
    </button>
  );
}
