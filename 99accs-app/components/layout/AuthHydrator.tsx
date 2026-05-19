'use client';
import { useEffect } from 'react';
import { useAuthStore } from '@/lib/store/authStore';

// Mounts once in the root layout. Calls /auth/me on first paint so the
// auth store transitions out of `status='unknown'` and any client code
// that gates on auth (e.g. the Support Create-ticket button) gets a
// definitive answer without re-fetching per page.
export default function AuthHydrator() {
  const hydrate = useAuthStore((s) => s.hydrate);
  useEffect(() => { hydrate(); }, [hydrate]);
  return null;
}
