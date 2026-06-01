'use client';
import { useState, FormEvent } from 'react';
import Link from 'next/link';
import { useSearchParams } from 'next/navigation';
import { useAuthStore } from '@/lib/store/authStore';
import { OAuthSimDialog } from './OAuthSimDialog';

export function LoginForm() {
  const params = useSearchParams();
  const redirect = params.get('redirect') || '/account';
  const login = useAuthStore((s) => s.login);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [legacyNotice, setLegacyNotice] = useState<{ email: string; message: string } | null>(null);
  const [oauthProvider, setOauthProvider] = useState<'google' | 'facebook' | null>(null);

  const handleSubmit = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    setLegacyNotice(null);

    const fd = new FormData(e.currentTarget);
    const result = await login(
      String(fd.get('email') ?? ''),
      String(fd.get('password') ?? ''),
    );

    if (result.ok) {
      window.location.assign(redirect);
      return;
    }

    if (result.legacy) {
      // Account was migrated from the previous platform. Show the reset notice
      // inline and keep the user here to read it. They stay a guest (no auth
      // cookie) and are free to browse anywhere until they open the emailed
      // link, set a new password, and sign in.
      setLegacyNotice({ email: result.email, message: result.message });
      setLoading(false);
      return;
    }

    setError(result.message);
    setLoading(false);
  };

  if (legacyNotice) {
    return (
      <div className="auth-form__legacy-notice">
        <h3 className="title" style={{ marginBottom: 12 }}>Account migrated</h3>
        <p style={{ marginBottom: 12 }}>{legacyNotice.message}</p>
        <p style={{ marginBottom: 16, opacity: 0.75 }}>
          We just emailed a secure password-reset link to{' '}
          <strong>{legacyNotice.email}</strong>. Open it to finish signing in.
        </p>
        <p style={{ marginBottom: 20, fontSize: '0.9em', opacity: 0.7 }}>
          Didn&apos;t get the email? Check spam, or{' '}
          <Link href="/forgot-password">request another link</Link>.
        </p>
        <button
          type="button"
          className="tg-btn"
          onClick={() => setLegacyNotice(null)}
        >
          Back to login
        </button>
      </div>
    );
  }

  return (
    <>
      <form onSubmit={handleSubmit} className="login-form">
        <div className="form-grp">
          <label htmlFor="email">Username or email address</label>
          <input type="email" id="email" name="email" placeholder="Enter your username or email" required />
        </div>
        <div className="form-grp">
          <label htmlFor="password">Password</label>
          <input id="password" name="password" type="password" placeholder="• • • • • • • •" required />
        </div>
        <div className="account__check">
          <div className="account__check-remember">
            <input type="checkbox" className="form-check-input" id="terms-check" />
            <label htmlFor="terms-check" className="form-check-label">Remember me</label>
          </div>
          <div className="account__check-forgot">
            <Link href="/forgot-password">Lost your password?</Link>
          </div>
        </div>
        {error && <p className="auth-form__error">{error}</p>}
        <button type="submit" className="tg-btn" disabled={loading}>
          {loading ? 'Signing in...' : 'Login'}
        </button>
      </form>
      <div className="account__switch">
        <p>Don&apos;t have an account? <Link href="/register">Register</Link></p>
      </div>
      <div className="account__divider"><span>OR</span></div>
      <div className="account__social">
        <button type="button" className="account__social-btn" onClick={() => setOauthProvider('google')}>
          <img src="/img/icons/google.svg" alt="google" />
          Login with Google
        </button>
        <button type="button" className="account__social-btn" onClick={() => setOauthProvider('facebook')}>
          <img src="/img/icons/facebook.svg" alt="facebook" />
          Login with Facebook
        </button>
      </div>
      <OAuthSimDialog
        provider={oauthProvider}
        redirect={redirect}
        onClose={() => setOauthProvider(null)}
      />
    </>
  );
}
