'use client';
import { useState, FormEvent } from 'react';

interface Props {
  /** Real password-reset token from the email link. */
  token: string;
  /** Email the reset was issued for — Laravel requires it on submit. */
  email: string;
}

interface ResetError {
  code?: string;
  message?: string;
  errors?: Record<string, string[]>;
}

export function ResetPasswordForm({ token, email }: Props) {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleSubmit = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    try {
      const fd = new FormData(e.currentTarget);
      const password = String(fd.get('password') ?? '');
      const confirm = String(fd.get('password_confirmation') ?? '');
      if (password.length < 10) {
        setError('Password must be at least 10 characters.');
        setLoading(false);
        return;
      }
      if (password !== confirm) {
        setError('Passwords do not match.');
        setLoading(false);
        return;
      }

      const res = await fetch('/api/auth/reset-password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
          token,
          email,
          password,
          password_confirmation: confirm,
        }),
      });
      if (!res.ok) {
        const body = (await res.json().catch(() => ({}))) as ResetError;
        const firstFieldError = body.errors ? Object.values(body.errors).flat()[0] : undefined;
        throw new Error(firstFieldError ?? body.message ?? 'Reset failed.');
      }
      // BFF set the auth cookie. Hard-navigate so the proxy + server layout
      // pick up the new session.
      window.location.assign('/account');
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Reset failed. Please request a new link.');
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="login-form">
      <div className="form-grp">
        <label htmlFor="new-password">New password</label>
        <input id="new-password" name="password" type="password" required minLength={10} placeholder="At least 10 characters, letters + numbers" />
      </div>
      <div className="form-grp">
        <label htmlFor="confirm-password">Confirm password</label>
        <input id="confirm-password" name="password_confirmation" type="password" required minLength={10} placeholder="Re-enter password" />
      </div>
      {error && <p className="auth-form__error">{error}</p>}
      <button type="submit" className="tg-btn" disabled={loading}>
        {loading ? 'Saving…' : 'Set new password'}
      </button>
    </form>
  );
}
