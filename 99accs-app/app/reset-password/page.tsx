import { cookies } from 'next/headers';
import { redirect } from 'next/navigation';
import { ResetPasswordForm } from '@/components/auth/ResetPasswordForm';

export const metadata = { title: 'Set a new password — 99Accs' };

// Reached only by users whose login set 99accs_reset_token + 99accs_reset_email.
// proxy.ts hard-blocks all other routes until this is completed. If the
// cookies aren't present (direct URL access by a non-migrated user, or after
// the 15-min TTL expires), bounce back to /login.
export default async function ResetPasswordPage() {
  const store = await cookies();
  const email = store.get('99accs_reset_email')?.value;
  const token = store.get('99accs_reset_token')?.value;

  if (!email || !token) {
    redirect('/login');
  }

  return (
    <div className="tg-modal-content" style={{ position: 'static', maxWidth: 'none' }}>
      <div className="modal-header">
        <h1 className="modal-title">Set a new password</h1>
      </div>
      <div className="modal-body">
        <p style={{ marginBottom: 16 }}>
          Your account was migrated. Please set a new password to continue.
        </p>
        <p style={{ marginBottom: 16, opacity: 0.7, fontSize: '0.9em' }}>
          Account: <strong>{email}</strong>
        </p>
        <ResetPasswordForm token={token} />
      </div>
    </div>
  );
}
