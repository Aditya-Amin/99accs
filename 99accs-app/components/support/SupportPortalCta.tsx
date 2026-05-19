'use client';
import { useRouter } from 'next/navigation';
import { useAuthStore } from '@/lib/store/authStore';
import { useUiStore } from '@/lib/store/uiStore';

const TICKET_LIST_URL = '/support/tickets';

// "Create ticket" CTA from support.html. If the user is signed in, takes
// them straight to their ticket list. If not, opens the login modal with
// `authPostLoginRedirect` set so AuthModal navigates here after success.
export default function SupportPortalCta() {
  const router = useRouter();
  const status = useAuthStore((s) => s.status);
  const openAuthModal = useUiStore((s) => s.openAuthModal);

  const handleClick = () => {
    if (status === 'authed') {
      router.push(TICKET_LIST_URL);
    } else {
      // Guests (and the brief 'unknown' window during hydration) get the
      // login modal. After login AuthModal forwards to the ticket list.
      openAuthModal('login', TICKET_LIST_URL);
    }
  };

  return (
    <button type="button" className="tg-btn" onClick={handleClick}>
      Create ticket
    </button>
  );
}
