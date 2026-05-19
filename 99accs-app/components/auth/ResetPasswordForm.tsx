'use client';
import { useState, FormEvent } from 'react';

interface Props {
  token: string;
}

export function ResetPasswordForm({ token }: Props) {
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

      const res = await fetch('/api/mock/reset-password/forced', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          reset_token: token,
          password,
          password_confirmation: confirm,
        }),
      });
      if (!res.ok) {
        const body = (await res.json().catch(() => ({}))) as { message?: string };
        throw new Error(body.message ?? 'Reset failed.');
      }
      // Auth cookie is now set by the server. Hard-navigate to /account so
      // the proxy and server layout pick up the new auth state.
      window.location.assign('/account');
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Reset failed. Please log in again.');
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="login-form">
      <div className="form-grp">
        <label htmlFor="new-password">New password</label>
        <input id="new-password" name="password" type="password" required minLength={10} placeholder="At least 10 characters" />
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
