import Link from 'next/link';
import { redirect } from 'next/navigation';
import { ResetPasswordForm } from '@/components/auth/ResetPasswordForm';

export const metadata = { title: 'Set a new password — 99Accs' };

interface PageProps {
  searchParams: Promise<Record<string, string | undefined>>;
}

/**
 * Reachable via the reset link emailed to the user:
 *   /reset-password?token=X&email=Y
 *
 * Legacy/migrated users are NOT funneled or trapped here anymore — they reach
 * this page only by opening their reset email. After a successful reset the BFF
 * logs them straight in (and Laravel clears is_legacy/must_reset_password), so
 * they are never asked to reset again.
 */
export default async function ResetPasswordPage({ searchParams }: PageProps) {
  const sp = await searchParams;
  const urlToken = sp.token;
  // Tolerate links pasted from an HTML email where "&" was copied verbatim as
  // "&amp;": the browser then parses the second param as "amp;email" rather
  // than "email", which previously dumped the user on /login for no reason.
  const urlEmail = sp.email ?? sp['amp;email'];

  // Valid reset link — render the password form.
  if (urlToken && urlEmail) {
    return (
      <div className="tg-modal-content" style={{ position: 'static', maxWidth: 'none' }}>
        <div className="modal-header">
          <h1 className="modal-title">Set a new password</h1>
        </div>
        <div className="modal-body">
          <p style={{ marginBottom: 16 }}>
            Choose a strong password to finish signing in.
          </p>
          <p style={{ marginBottom: 16, opacity: 0.7, fontSize: '0.9em' }}>
            Account: <strong>{urlEmail}</strong>
          </p>
          <ResetPasswordForm token={urlToken} email={urlEmail} />
        </div>
      </div>
    );
  }

  // A token but no email (e.g. a truncated/garbled link) — don't bounce to
  // login; explain and let them request a fresh link.
  if (urlToken && !urlEmail) {
    return (
      <div className="tg-modal-content" style={{ position: 'static', maxWidth: 'none' }}>
        <div className="modal-header">
          <h1 className="modal-title">This reset link looks incomplete</h1>
        </div>
        <div className="modal-body">
          <p style={{ marginBottom: 16 }}>
            We couldn&apos;t read the email address from this link — it may have
            been copied or wrapped incorrectly. Please open the link directly
            from your email, or request a new one below.
          </p>
          <Link href="/forgot-password" className="tg-btn">Request a new reset link</Link>
        </div>
      </div>
    );
  }

  // No token at all — nothing to do here.
  redirect('/login');
}
