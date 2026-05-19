'use client';
import { useState, FormEvent } from 'react';
import Link from 'next/link';

export function ForgotPasswordForm() {
  const [loading, setLoading] = useState(false);
  const [sent, setSent] = useState(false);
  const [error, setError] = useState('');

  const handleSubmit = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    try {
      const fd = new FormData(e.currentTarget);
      const res = await fetch('/api/mock/forgot-password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: fd.get('email') }),
      });
      if (!res.ok) throw new Error('Failed');
      setSent(true);
    } catch {
      setError('Could not send reset link. Try again.');
    } finally {
      setLoading(false);
    }
  };

  if (sent) {
    return (
      <>
        <p>If an account exists for that email, a reset link is on its way.</p>
        <div className="account__switch">
          <p><Link href="/login">Back to login</Link></p>
        </div>
      </>
    );
  }

  return (
    <>
      <form onSubmit={handleSubmit} className="login-form">
        <div className="form-grp">
          <label htmlFor="fp-email">Email address</label>
          <input type="email" id="fp-email" name="email" placeholder="Enter your email" required />
        </div>
        <span className="text">A link to set a new password will be sent to your email address.</span>
        {error && <p className="auth-form__error">{error}</p>}
        <button type="submit" className="tg-btn" disabled={loading}>{loading ? 'Sending...' : 'Send reset link'}</button>
      </form>
      <div className="account__switch">
        <p>Remembered it? <Link href="/login">Back to login</Link></p>
      </div>
    </>
  );
}
