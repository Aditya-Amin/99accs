'use client';
import { useState, FormEvent } from 'react';
import Link from 'next/link';
import { useSearchParams } from 'next/navigation';
import { useAuthStore } from '@/lib/store/authStore';
import { OAuthSimDialog } from './OAuthSimDialog';

interface LoginSuccess {
  data: { token: string; user: { id: number; name: string; email: string; created_at: string } };
}
interface ForcedReset {
  must_reset_password: true;
  reset_token: string;
  email: string;
}

export function LoginForm() {
  const params = useSearchParams();
  const redirect = params.get('redirect') || '/account';
  const setUser = useAuthStore((s) => s.setUser);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [oauthProvider, setOauthProvider] = useState<'google' | 'facebook' | null>(null);

  const handleSubmit = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    try {
      const fd = new FormData(e.currentTarget);
      const res = await fetch('/api/mock/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: fd.get('email'), password: fd.get('password') }),
      });
      if (!res.ok) throw new Error('Invalid credentials');
      const json = (await res.json()) as LoginSuccess | ForcedReset;

      if ('must_reset_password' in json) {
        // Server set the reset cookies — hard-navigate to /reset-password.
        // proxy.ts will hard-block every other URL until this is completed.
        window.location.assign('/reset-password');
        return;
      }

      setUser(json.data.user);
      window.location.assign(redirect);
    } catch {
      setError('Login failed. Please check your credentials.');
      setLoading(false);
    }
  };

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
        <button type="submit" className="tg-btn" disabled={loading}>{loading ? 'Signing in...' : 'Login'}</button>
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
