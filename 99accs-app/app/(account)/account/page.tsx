import { cookies } from 'next/headers';
import { readToken, USER_COOKIE } from '@/lib/auth/cookies';
import type { SessionUser } from '@/lib/auth/cookies';
import { getDashboard } from '@/lib/api/endpoints';
import type { AccountDashboard } from '@/lib/api/types';
import { DashboardPane } from '@/components/account/dashboard/tabs/DashboardPane';

export default async function AccountPage() {
  const token = await readToken();

  const cookieStore = await cookies();
  const userRaw = cookieStore.get(USER_COOKIE)?.value;
  const user: SessionUser | null = userRaw ? JSON.parse(userRaw) : null;

  let dashboard: AccountDashboard | null = null;
  if (token) {
    try {
      const res = await getDashboard(token);
      dashboard = res.data;
    } catch {
      // API unreachable — render with nulls, pane handles gracefully
    }
  }

  return <DashboardPane dashboard={dashboard} user={user} />;
}
