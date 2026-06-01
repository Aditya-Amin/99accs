import { readToken } from '@/lib/auth/cookies';
import { getProfile } from '@/lib/api/endpoints';
import type { AuthUser } from '@/lib/api/types';
import { AccountDetailsPane } from '@/components/account/dashboard/tabs/AccountDetailsPane';

export default async function DetailsPage() {
  const token = await readToken();

  let user: AuthUser | null = null;
  if (token) {
    try {
      const res = await getProfile(token);
      user = res.data;
    } catch {
      // API unreachable — render empty form
    }
  }

  return <AccountDetailsPane user={user} />;
}
