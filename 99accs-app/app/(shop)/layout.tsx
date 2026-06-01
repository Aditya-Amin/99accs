import Header from '@/components/layout/Header';
import Footer from '@/components/layout/Footer';
import RouteEffects from '@/components/layout/RouteEffects';
import { getFooter } from '@/lib/api/server';

export default async function ShopLayout({ children }: { children: React.ReactNode }) {
  const { data: footer } = await getFooter();
  return (
    <>
      <Header />
      {children}
      <Footer data={footer} />
      <RouteEffects />
    </>
  );
}
