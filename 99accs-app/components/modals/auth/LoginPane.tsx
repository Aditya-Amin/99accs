'use client';
import { useState, FormEvent } from 'react';
import { useUiStore } from '@/lib/store/uiStore';
import { useAuthStore } from '@/lib/store/authStore';

export function LoginPane() {
  const { openAuthModal, closeAuthModal, authPostLoginRedirect, clearAuthPostLoginRedirect } = useUiStore();
  // Use the real auth store → /api/auth/login BFF → Laravel Hash::check + a real
  // Sanctum token cookie. (The old mock route accepted ANY password and minted a
  // random user, which also broke the header name + authed checkout.)
  const login = useAuthStore((s) => s.login);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [legacyNotice, setLegacyNotice] = useState<{ email: string; message: string } | null>(null);

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
      // Store already holds the real user → header re-renders with the correct
      // name. Close the modal and honor any pending post-login redirect.
      const redirect = authPostLoginRedirect;
      clearAuthPostLoginRedirect();
      closeAuthModal();
      if (redirect) window.location.assign(redirect);
      return;
    }

    if (result.legacy) {
      // Migrated account — show the reset notice inline. The user is NOT logged
      // in and NOT trapped: they can close this modal and keep browsing as a
      // guest until they open the emailed link and set a new password.
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
        <h3 className="title" style={{ marginBottom: 12 }}>Password reset required</h3>
        <p style={{ marginBottom: 12 }}>{legacyNotice.message}</p>
        <p style={{ marginBottom: 16, opacity: 0.75 }}>
          We just emailed a secure password-reset link to{' '}
          <strong>{legacyNotice.email}</strong>. Open it to set a new password and finish signing in.
        </p>
        <p style={{ marginBottom: 20, fontSize: '0.9em', opacity: 0.7 }}>
          You can close this and keep browsing as a guest in the meantime.
        </p>
        <button type="button" className="tg-btn" style={{ marginRight: 8 }} onClick={() => openAuthModal('forgot-password')}>
          Resend reset link
        </button>
        <button
          type="button"
          onClick={() => setLegacyNotice(null)}
          style={{ background: 'none', border: 'none', cursor: 'pointer', color: 'inherit', textDecoration: 'underline', padding: 0 }}
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
