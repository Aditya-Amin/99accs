'use client';
import { useState, FormEvent } from 'react';
import { useUiStore } from '@/lib/store/uiStore';
import { useAuthStore } from '@/lib/store/authStore';

interface LoginSuccess {
  data: { token: string; user: { id: number; name: string; email: string; created_at: string } };
}
interface ForcedReset {
  must_reset_password: true;
  reset_token: string;
  email: string;
}

export function LoginPane() {
  const { openAuthModal, closeAuthModal, authPostLoginRedirect, clearAuthPostLoginRedirect } = useUiStore();
  const setUser = useAuthStore((s) => s.setUser);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleSubmit = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    try {
      const fd = new FormData(e.currentTarget);
      const email = String(fd.get('email') ?? '');
      const res = await fetch('/api/mock/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password: fd.get('password') }),
      });
      if (!res.ok) throw new Error('Invalid credentials');
      const json = (await res.json()) as LoginSuccess | ForcedReset;

      if ('must_reset_password' in json) {
        // Server set the reset cookies. Hard-navigate so proxy.ts picks them up
        // and locks us into /reset-password until the reset is complete.
        closeAuthModal();
        window.location.assign('/reset-password');
        return;
      }

      setUser(json.data.user);
      const redirect = authPostLoginRedirect;
      clearAuthPostLoginRedirect();
      closeAuthModal();
      if (redirect) window.location.assign(redirect);
    } catch {
      setError('Login failed. Please check your credentials.');
    } finally {
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
            <button
              type="button"
              onClick={() => openAuthModal('forgot-password')}
              style={{ background: 'none', border: 'none', cursor: 'pointer', color: 'inherit', textDecoration: 'underline', padding: 0 }}
            >
              Lost your password?
            </button>
          </div>
        </div>
        {error && <p style={{ color: 'red', marginBottom: 8 }}>{error}</p>}
        <button type="submit" className="tg-btn" disabled={loading}>{loading ? 'Signing in...' : 'Login'}</button>
      </form>
      <div className="account__switch">
        <p>
          Don&apos;t have an account?{' '}
          <button
            type="button"
            onClick={() => openAuthModal('register')}
            style={{ background: 'none', border: 'none', cursor: 'pointer', color: 'inherit', textDecoration: 'underline', padding: 0 }}
          >
            Register
          </button>
        </p>
      </div>
      <div className="account__divider"><span>OR</span></div>
      <div className="account__social">
        <a href="#" className="account__social-btn">
          <img src="/img/icons/google.svg" alt="google" />
          Login with Google
        </a>
        <a href="#" className="account__social-btn">
          <img src="/img/icons/facebook.svg" alt="facebook" />
          Login with Facebook
        </a>
      </div>
    </>
  );
}
