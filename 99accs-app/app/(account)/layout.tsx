import { redirect } from 'next/navigation';
import { getToken } from '@/lib/auth/session';
import Header from '@/components/layout/Header';
import Footer from '@/components/layout/Footer';
import RouteEffects from '@/components/layout/RouteEffects';
import { getFooter } from '@/lib/api/server';

// Defeat any RSC/route-segment caching — every request to /account/* must
// re-check the cookie. Otherwise a previously-cached "authed" render could be
// served to a logged-out user.
export const dynamic = 'force-dynamic';

export default async function AccountLayout({ children }: { children: React.ReactNode }) {
  if (!(await getToken())) redirect('/login');
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
