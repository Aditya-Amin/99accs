import type { FooterData, HomeData } from '@/lib/api/types';
import banner from '@/mocks/home/banner.json';
import about from '@/mocks/home/about.json';
import work from '@/mocks/home/work.json';
import features from '@/mocks/home/features.json';
import testimonials from '@/mocks/home/testimonials.json';
import cta from '@/mocks/home/cta.json';
import footer from '@/mocks/home/footer.json';

export function getMockHome(): HomeData {
  return {
    banner:       banner       as HomeData['banner'],
    about:        about        as HomeData['about'],
    work:         work         as HomeData['work'],
    features:     features     as HomeData['features'],
    testimonials: testimonials as HomeData['testimonials'],
    cta:          cta          as HomeData['cta'],
  };
}

export function getMockFooter(): FooterData {
  return footer as FooterData;
}
