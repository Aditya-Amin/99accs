'use client';
import { useState, FormEvent } from 'react';
import { useUiStore } from '@/lib/store/uiStore';
import { useAuthStore } from '@/lib/store/authStore';

export function RegisterPane() {
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
        const body = (await res.json().catch(() => ({}))) as {
          message?: string;
          errors?: Record<string, string[]>;
        };
        // Surface field-level validation (e.g. "email has already been taken").
        const firstFieldError = body.errors ? Object.values(body.errors).flat()[0] : undefined;
        throw new Error(firstFieldError ?? body.message ?? 'Registration failed.');
      }
      const json = (await res.json()) as {
        data: { user: { id: number; name: string; email: string; created_at: string } };
      };
      setUser(json.data.user);
      const redirect = authPostLoginRedirect;
      clearAuthPostLoginRedirect();
      closeAuthModal();
      if (redirect) window.location.assign(redirect);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Registration failed. Please try again.');
    } finally {
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
          <input type="password" id="reg-password" name="password" placeholder="At least 10 characters, letters + numbers" required minLength={10} />
        </div>
        <span className="text">Use at least 10 characters with a mix of letters and numbers.</span>
        {error && <p style={{ color: 'red', marginBottom: 8 }}>{error}</p>}
        <button type="submit" className="tg-btn" disabled={loading}>{loading ? 'Registering...' : 'Register'}</button>
      </form>
      <div className="account__switch">
        <p>
          Already have an account?{' '}
          <button
            type="button"
            onClick={() => openAuthModal('login')}
            style={{ background: 'none', border: 'none', cursor: 'pointer', color: 'inherit', textDecoration: 'underline', padding: 0 }}
          >
            Login
          </button>
        </p>
      </div>
      <div className="account__divider"><span>OR</span></div>
      <div className="account__social">
        <a href="#" className="account__social-btn">
          <img src="/img/icons/google.svg" alt="google" />
          Register with Google
        </a>
        <a href="#" className="account__social-btn">
          <img src="/img/icons/facebook.svg" alt="facebook" />
          Register with Facebook
        </a>
      </div>
    </>
  );
}
