import { SupportBreadcrumb } from '@/components/support/contact/SupportBreadcrumb';
import { SupportPortalClient } from '@/components/support/contact/SupportPortalClient';
import CtaSection from '@/components/home/CtaSection';
import { getMockHome } from '@/lib/mock/home';

// TODO: swap getMockHome() → getHome() from '@/lib/api/endpoints' once Laravel API is live
export default function ContactPage() {
  const { cta } = getMockHome();

  return (
    <main className="main-area fix">
      <SupportBreadcrumb />
      <SupportPortalClient />
      <CtaSection data={cta} />
    </main>
  );
}
