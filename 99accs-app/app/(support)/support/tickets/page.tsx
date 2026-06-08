import { redirect } from 'next/navigation';
import { SupportBreadcrumb } from '@/components/support/SupportBreadcrumb';
import SupportTicketsTable from '@/components/support/SupportTicketsTable';
import { readToken } from '@/lib/auth/cookies';
import { getSupportTickets } from '@/lib/api/endpoints';
import type { SupportTicket, SupportTicketStatus, Game } from '@/lib/api/types';

interface Props {
  searchParams: Promise<Record<string, string>>;
}

// Ticket list — mirrors support-2.html. Auth-gated server-side: a guest
// hitting this URL is bounced back to /support so the portal CTA can prompt
// login. The status/game/search params are reflected in the table filter UI.
export default async function SupportTicketsPage({ searchParams }: Props) {
  const token = await readToken();
  if (!token) {
    redirect('/support');
  }

  const sp = await searchParams;
  const status = (sp.status === 'new' || sp.status === 'open' || sp.status === 'closed')
    ? (sp.status as SupportTicketStatus)
    : undefined;
  const game = (sp.game === 'valorant' || sp.game === 'fortnite' || sp.game === 'legends')
    ? (sp.game as Game)
    : undefined;

  let tickets: SupportTicket[] = [];
  try {
    const res = await getSupportTickets(token, { status, game, search: sp.search, per_page: 50 });
    tickets = res.data;
  } catch {
    // API unreachable — render empty state rather than erroring the page.
  }

  return (
    <main className="main-area fix">
      <SupportBreadcrumb title="Support Portal" />
      <SupportTicketsTable tickets={tickets} />
    </main>
  );
}
