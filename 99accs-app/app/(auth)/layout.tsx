import { redirect } from 'next/navigation';
import { getToken } from '@/lib/auth/session';
import Header from '@/components/layout/Header';
import Footer from '@/components/layout/Footer';
import RouteEffects from '@/components/layout/RouteEffects';
import { getFooter } from '@/lib/api/server';

export default async function AuthLayout({ children }: { children: React.ReactNode }) {
  if (await getToken()) redirect('/account');
  const { data: footer } = await getFooter();
  return (
    <>
      <Header />
      <main className="main-area fix">
        <section
          className="auth-page section-py-120 section__bg"
          style={{ backgroundImage: 'url(/img/bg/cta_bg.jpg)' }}
        >
          {/* particles.js boots this in RouteEffects on every route change.
              The id has to be "cta-particles" — that's the id RouteEffects
              probes for. */}
          <div id="cta-particles"></div>
          <div className="bg__overlay-top-two"></div>
          <div className="container">
            {/* Row justification + column width are owned by each page so e.g.
                register can use a wider column than login. */}
            <div className="row justify-content-center">
              {children}
            </div>
          </div>
        </section>
      </main>
      <Footer data={footer} />
      <RouteEffects />
    </>
  );
}
