'use client';
import { useState, useEffect, FormEvent } from 'react';
import { useAuthStore } from '@/lib/store/authStore';
import type { AuthUser } from '@/lib/api/types';

type Provider = 'google' | 'facebook';

interface Props {
  provider: Provider | null;
  redirect: string;
  onClose: () => void;
}

const META: Record<Provider, { label: string; brandColor: string; icon: string }> = {
  google: { label: 'Google', brandColor: '#4285F4', icon: '/img/icons/google.svg' },
  facebook: { label: 'Facebook', brandColor: '#1877F2', icon: '/img/icons/facebook.svg' },
};

// Simulates the Google/Facebook consent screen. In production this is a full
// OAuth redirect flow; for dev we just collect the email the user wants to
// log in as and POST it to /api/mock/auth/oauth/{provider}. Tip for demo:
// type `demo@99accs.com` to get the seeded account with mock data; anything
// else creates a fresh account with no data.
export function OAuthSimDialog({ provider, redirect, onClose }: Props) {
  const setUser = useAuthStore((s) => s.setUser);
  const [email, setEmail] = useState('');
  const [name, setName] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  // Reset state whenever the dialog re-opens for a different provider.
  useEffect(() => {
    if (provider) {
      setEmail('');
      setName('');
      setError('');
      setLoading(false);
    }
  }, [provider]);

  // Esc-to-close + lock body scroll while open.
  useEffect(() => {
    if (!provider) return;
    const onKey = (e: KeyboardEvent) => { if (e.key === 'Escape') onClose(); };
    document.addEventListener('keydown', onKey);
    document.body.style.overflow = 'hidden';
    return () => {
      document.removeEventListener('keydown', onKey);
      document.body.style.overflow = '';
    };
  }, [provider, onClose]);

  if (!provider) return null;
  const meta = META[provider];

  const handleSubmit = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    try {
      const res = await fetch(`/api/mock/auth/oauth/${provider}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ email: email.trim(), name: name.trim() || undefined }),
      });
      if (!res.ok) {
        const raw = (await res.json().catch(() => ({}))) as { message?: string };
        throw new Error(raw.message ?? `Sign-in failed (${res.status})`);
      }
      const json = (await res.json()) as { data: { user: AuthUser } };
      setUser(json.data.user);
      window.location.assign(redirect);
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Sign-in failed.');
      setLoading(false);
    }
  };

  return (
    <div
      className="oauth-sim__backdrop"
      role="dialog"
      aria-modal="true"
      aria-label={`Sign in with ${meta.label}`}
      onClick={onClose}
    >
      <div className="oauth-sim__panel" onClick={(e) => e.stopPropagation()}>
        <button type="button" className="oauth-sim__close" onClick={onClose} aria-label="Close">×</button>
        <div className="oauth-sim__head">
          <img src={meta.icon} alt="" className="oauth-sim__icon" />
          <h2 className="oauth-sim__title">Continue with {meta.label}</h2>
          <p className="oauth-sim__sub">
            Simulated sign-in. Use <code>demo@99accs.com</code> to see seeded data,
            or any other email to start with a clean account.
          </p>
        </div>
        <form onSubmit={handleSubmit} className="oauth-sim__form">
          <div className="form-grp">
            <label htmlFor="oauth-sim-email">{meta.label} email</label>
            <input
              id="oauth-sim-email"
              type="email"
              required
              autoFocus
              value={email}
              placeholder="you@example.com"
              onChange={(e) => setEmail(e.target.value)}
            />
          </div>
          <div className="form-grp">
            <label htmlFor="oauth-sim-name">Display name <span className="oauth-sim__optional">(optional)</span></label>
            <input
              id="oauth-sim-name"
              type="text"
              value={name}
              placeholder="Auto-derived from email"
              onChange={(e) => setName(e.target.value)}
            />
          </div>
          {error && <p className="auth-form__error">{error}</p>}
          <button
            type="submit"
            className="tg-btn oauth-sim__submit"
            style={{ background: meta.brandColor, color: '#fff' }}
            disabled={loading}
          >
            {loading ? 'Signing in…' : `Continue as ${email || 'this account'}`}
          </button>
        </form>
      </div>
    </div>
  );
}
