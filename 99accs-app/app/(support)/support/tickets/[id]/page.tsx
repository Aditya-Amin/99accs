import { notFound, redirect } from 'next/navigation';
import { SupportBreadcrumb } from '@/components/support/SupportBreadcrumb';
import SupportTicketThread from '@/components/support/SupportTicketThread';
import { readToken } from '@/lib/auth/cookies';
import { getSupportTicket } from '@/lib/api/endpoints';
import type { SupportTicket } from '@/lib/api/types';

interface Props {
  params: Promise<{ id: string }>;
}

export default async function SupportTicketDetailPage({ params }: Props) {
  const token = await readToken();
  if (!token) {
    redirect('/support');
  }

  const { id } = await params;
  const ticketId = parseInt(id, 10);
  if (Number.isNaN(ticketId)) notFound();

  let ticket: SupportTicket | null = null;
  try {
    const res = await getSupportTicket(token, ticketId);
    ticket = res.data;
  } catch {
    // 404 (not found / not owned) or API unreachable — both render notFound().
  }
  if (!ticket) notFound();

  return (
    <main className="main-area fix">
      <SupportBreadcrumb title="Support Portal" />
      <SupportTicketThread ticket={ticket} />
    </main>
  );
}
