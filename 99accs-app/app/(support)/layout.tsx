import Header from '@/components/layout/Header';
import Footer from '@/components/layout/Footer';
import RouteEffects from '@/components/layout/RouteEffects';

export default function SupportLayout({ children }: { children: React.ReactNode }) {
  return (
    <>
      <Header />
      {children}
      <Footer />
      <RouteEffects />
    </>
  );
}
