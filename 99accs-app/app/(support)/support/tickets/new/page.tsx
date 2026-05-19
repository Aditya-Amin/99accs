import { cookies } from 'next/headers';
import { redirect } from 'next/navigation';
import { SupportBreadcrumb } from '@/components/support/SupportBreadcrumb';
import SupportTicketCreateForm from '@/components/support/SupportTicketCreateForm';

// Auth-gated ticket creation page. Guest → bounce to portal so the
// post-login redirect picks back up here on next attempt.
export default async function NewSupportTicketPage() {
  const store = await cookies();
  if (!store.has('99accs_token')) {
    redirect('/support');
  }

  return (
    <main className="main-area fix">
      <SupportBreadcrumb title="New Ticket" />
      <SupportTicketCreateForm />
    </main>
  );
}
