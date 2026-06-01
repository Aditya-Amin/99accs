import BannerSection from '@/components/home/BannerSection';
import AboutSection from '@/components/home/AboutSection';
import WorkSection from '@/components/home/WorkSection';
import FeaturesSection from '@/components/home/FeaturesSection';
import TestimonialsSection from '@/components/home/TestimonialsSection';
import CtaSection from '@/components/home/CtaSection';
import { getHome } from '@/lib/api/server';

export const dynamic = 'force-dynamic';

export default async function HomePage() {
  const { data } = await getHome();

  return (
    <main className="main-area fix">
      <BannerSection data={data.banner} />
      <AboutSection data={data.about} />
      <WorkSection data={data.work} />
      <FeaturesSection data={data.features} />
      <TestimonialsSection data={data.testimonials} />
      <CtaSection data={data.cta} />
    </main>
  );
}
