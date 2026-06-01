'use client';
import { useState, FormEvent } from 'react';
import Link from 'next/link';
import { useSearchParams } from 'next/navigation';
import { useAuthStore } from '@/lib/store/authStore';
import { OAuthSimDialog } from './OAuthSimDialog';

interface RegisterError {
  code?: string;
  message?: string;
  errors?: Record<string, string[]>;
}

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
      const password = String(fd.get('password') ?? '');
      const res = await fetch('/api/auth/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
          name: fd.get('name'),
          email: fd.get('email'),
          password,
          password_confirmation: password,
        }),
      });
      if (!res.ok) {
        const body = (await res.json().catch(() => ({}))) as RegisterError;
        // Surface field-level validation messages when available (e.g. "email
        // has already been taken"), otherwise fall back to the message body.
        const firstFieldError = body.errors ? Object.values(body.errors).flat()[0] : undefined;
        throw new Error(firstFieldError ?? body.message ?? 'Registration failed.');
      }
      const json = (await res.json()) as { data: { user: { id: number; name: string; email: string; created_at: string } } };
      setUser(json.data.user);
      window.location.assign(redirect);
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Registration failed. Please try again.');
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
          <input
            type="password"
            id="reg-password"
            name="password"
            placeholder="At least 10 characters, letters + numbers"
            required
            minLength={10}
          />
        </div>
        <span className="text">Use at least 10 characters with a mix of letters and numbers.</span>
        {error && <p className="auth-form__error">{error}</p>}
        <button type="submit" className="tg-btn" disabled={loading}>
          {loading ? 'Registering...' : 'Register'}
        </button>
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
