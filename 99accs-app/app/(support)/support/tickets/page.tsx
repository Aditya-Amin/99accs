import { cookies } from 'next/headers';
import { redirect } from 'next/navigation';
import { SupportBreadcrumb } from '@/components/support/SupportBreadcrumb';
import SupportTicketsTable from '@/components/support/SupportTicketsTable';
import { getMockTickets } from '@/lib/mock/support';
import type { SupportTicketStatus, Game } from '@/lib/api/types';
// TODO: swap getMockTickets() → getSupportTickets() once Laravel API is live

interface Props {
  searchParams: Promise<Record<string, string>>;
}

// Ticket list — mirrors support-2.html. Auth-gated server-side: a guest
// hitting this URL is bounced back to /support so the portal CTA can prompt
// login. The status/game/search params are reflected in the table filter UI.
export default async function SupportTicketsPage({ searchParams }: Props) {
  const store = await cookies();
  if (!store.has('99accs_token')) {
    redirect('/support');
  }

  const sp = await searchParams;
  const status = (sp.status === 'new' || sp.status === 'open' || sp.status === 'closed')
    ? (sp.status as SupportTicketStatus)
    : undefined;
  const game = (sp.game === 'valorant' || sp.game === 'fortnite' || sp.game === 'legends')
    ? (sp.game as Game)
    : undefined;

  const tickets = getMockTickets({ status, game, search: sp.search });

  return (
    <main className="main-area fix">
      <SupportBreadcrumb title="Support Portal" />
      <SupportTicketsTable tickets={tickets} />
    </main>
  );
}
