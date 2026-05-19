import { cookies } from 'next/headers';
import { notFound, redirect } from 'next/navigation';
import { SupportBreadcrumb } from '@/components/support/SupportBreadcrumb';
import SupportTicketThread from '@/components/support/SupportTicketThread';
import { getMockTicket } from '@/lib/mock/support';

interface Props {
  params: Promise<{ id: string }>;
}

export default async function SupportTicketDetailPage({ params }: Props) {
  const store = await cookies();
  if (!store.has('99accs_token')) {
    redirect('/support');
  }

  const { id } = await params;
  const ticketId = parseInt(id, 10);
  if (Number.isNaN(ticketId)) notFound();

  const ticket = getMockTicket(ticketId);
  if (!ticket) notFound();

  return (
    <main className="main-area fix">
      <SupportBreadcrumb title="Support Portal" />
      <SupportTicketThread ticket={ticket} />
    </main>
  );
}
