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
      const password = fd.get('password');
      const res = await fetch('/api/mock/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          name: fd.get('name'),
          email: fd.get('email'),
          password,
          password_confirmation: password,
        }),
      });
      if (!res.ok) throw new Error('Registration failed');
      const json = (await res.json()) as {
        data: { token: string; user: { id: number; name: string; email: string; created_at: string } };
      };
      setUser(json.data.user);
      const redirect = authPostLoginRedirect;
      clearAuthPostLoginRedirect();
      closeAuthModal();
      if (redirect) window.location.assign(redirect);
    } catch {
      setError('Registration failed. Please try again.');
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
          <input type="password" id="reg-password" name="password" placeholder="• • • • • • • •" required minLength={8} />
        </div>
        <span className="text">A link to set a new password will be sent to your email address.</span>
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
