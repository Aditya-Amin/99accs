import Header from '@/components/layout/Header';
import Footer from '@/components/layout/Footer';
import { getFooter } from '@/lib/api/server';

// This page lives at the app root (not in (auth)) because it has its own
// access rules. Unlike /login etc., it must be reachable by users with a
// reset cookie even when they don't have an auth cookie — proxy.ts hard-
// blocks them onto this URL and the page server-side reads the cookies.
export default async function ResetPasswordLayout({ children }: { children: React.ReactNode }) {
  const { data: footer } = await getFooter();
  return (
    <>
      <Header />
      <main className="main-area fix">
        <section className="checkout__area section-py-120">
          <div className="container">
            <div className="row justify-content-center">
              <div className="col-xl-5 col-lg-6 col-md-8">
                {children}
              </div>
            </div>
          </div>
        </section>
      </main>
      <Footer data={footer} />
    </>
  );
}
