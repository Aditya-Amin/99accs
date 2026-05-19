import { SupportBreadcrumb } from '@/components/support/SupportBreadcrumb';
import SupportPortalCta from '@/components/support/SupportPortalCta';
import CtaSection from '@/components/home/CtaSection';
import { getMockHome } from '@/lib/mock/home';
// TODO: swap getMockHome() → getHome() from '@/lib/api/endpoints' once Laravel API is live

// Support Portal landing — mirrors support.html. Auth-gated only at the CTA:
// guests can view the page, but clicking "Create ticket" prompts login.
export default function SupportPortalPage() {
  const { cta } = getMockHome();

  return (
    <main className="main-area fix">
      <SupportBreadcrumb title="Support Portal" />

      <section className="support__area">
        <div className="container">
          <div className="row justify-content-center">
            <div className="col-xl-8 col-lg-10">
              <div className="support__wrap">
                <span className="shape"></span>
                <img src="/img/icons/support_icon01.png" alt="" />
                <h2 className="title">
                  <span>Need Help?</span> Let&rsquo;s Solve It Together
                </h2>
                <p>Please log in or create an account to access the support desk.</p>
                <SupportPortalCta />
              </div>
            </div>
          </div>
        </div>
      </section>

      <CtaSection data={cta} />
    </main>
  );
}
