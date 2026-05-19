'use client';
import { useState, FormEvent } from 'react';
import Link from 'next/link';
import { useSearchParams } from 'next/navigation';
import { useAuthStore } from '@/lib/store/authStore';
import { OAuthSimDialog } from './OAuthSimDialog';

export function RegisterForm() {
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
      const password = fd.get('password');
      const res = await fetch('/api/mock/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: fd.get('name'), email: fd.get('email'), password, password_confirmation: password }),
      });
      if (!res.ok) throw new Error('Registration failed');
      const json = (await res.json()) as { data: { token: string; user: { id: number; name: string; email: string; created_at: string } } };
      setUser(json.data.user);
      window.location.assign(redirect);
    } catch {
      setError('Registration failed. Please try again.');
      setLoading(false);
    }
  };

  return (
    <>
      <form onSubmit={handleSubmit} className="login-form">
        <div className="form-grp">
          <label htmlFor="reg-name">Full Name</label>
          <input type="text" id="reg-name" name="name" placeholder="Enter your name" required />
        </div>
        <div className="form-grp">
          <label htmlFor="reg-email">Email address</label>
          <input type="email" id="reg-email" name="email" placeholder="Enter your email" required />
        </div>
        <div className="form-grp">
          <label htmlFor="reg-password">Password</label>
          <input type="password" id="reg-password" name="password" placeholder="• • • • • • • •" required />
        </div>
        <span className="text">A link to set a new password will be sent to your email address.</span>
        {error && <p className="auth-form__error">{error}</p>}
        <button type="submit" className="tg-btn" disabled={loading}>{loading ? 'Registering...' : 'Register'}</button>
      </form>
      <div className="account__switch">
        <p>Already have an account? <Link href="/login">Login</Link></p>
      </div>
      <div className="account__divider"><span>OR</span></div>
      <div className="account__social">
        <button type="button" className="account__social-btn" onClick={() => setOauthProvider('google')}>
          <img src="/img/icons/google.svg" alt="google" />
          Register with Google
        </button>
        <button type="button" className="account__social-btn" onClick={() => setOauthProvider('facebook')}>
          <img src="/img/icons/facebook.svg" alt="facebook" />
          Register with Facebook
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
