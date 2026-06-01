import { readToken } from '@/lib/auth/cookies';
import { getSupportTickets } from '@/lib/api/endpoints';
import type { SupportTicket } from '@/lib/api/types';
import { SupportPane } from '@/components/account/dashboard/tabs/SupportPane';

export default async function SupportPage() {
  const token = await readToken();

  let tickets: SupportTicket[] = [];
  if (token) {
    try {
      const res = await getSupportTickets(token, { per_page: 50 });
      tickets = res.data;
    } catch {
      // API unreachable — render empty state
    }
  }

  return <SupportPane initialTickets={tickets} />;
}
