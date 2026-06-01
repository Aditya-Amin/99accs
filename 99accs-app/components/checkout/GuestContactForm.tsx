'use client';
import { FormEvent, useState } from 'react';
import Link from 'next/link';

export interface GuestContact {
  email: string;
  phone: string;
  first_name: string;
  last_name: string;
}

interface Props {
  loading?: boolean;
  error?: string | null;
  onSubmit: (contact: GuestContact) => void;
}

/**
 * Guest checkout contact step. Markup mirrors the existing auth forms
 * (`my-account.html` modals) and wraps in `.tg-modal-content` so the form
 * inherits the established label/input styling from main.css line 690 —
 * 17px labels with `display: block`, full-width 46px inputs, etc.
 */
export function GuestContactForm({ loading = false, error, onSubmit }: Props) {
  const [touched, setTouched] = useState(false);

  const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setTouched(true);
    const fd = new FormData(e.currentTarget);
    onSubmit({
      email: String(fd.get('email') ?? '').trim().toLowerCase(),
      phone: String(fd.get('phone') ?? '').trim(),
      first_name: String(fd.get('first_name') ?? '').trim(),
      last_name: String(fd.get('last_name') ?? '').trim(),
    });
  };

  return (
    <div className="tg-modal-content" style={{ position: 'static', maxWidth: 'none' }}>
      <div className="modal-header">
        <h2 className="modal-title">Your contact info</h2>
      </div>
      <div className="modal-body">
        <span className="text">
          We&apos;ll email your order details + account credentials here. Already have an account?{' '}
          <Link href={`/login?redirect=${encodeURIComponent('/checkout')}`}>Sign in</Link>.
        </span>

        <form onSubmit={handleSubmit} className="login-form">
          <div className="row">
            <div className="col-md-6">
              <div className="form-grp">
                <label htmlFor="guest-first-name">First name</label>
                <input
                  id="guest-first-name"
                  name="first_name"
                  type="text"
                  required
                  autoComplete="given-name"
                  placeholder="Jane"
                />
              </div>
            </div>
            <div className="col-md-6">
              <div className="form-grp">
                <label htmlFor="guest-last-name">Last name</label>
                <input
                  id="guest-last-name"
                  name="last_name"
                  type="text"
                  autoComplete="family-name"
                  placeholder="Doe"
                />
              </div>
            </div>
          </div>

          <div className="form-grp">
            <label htmlFor="guest-email">Email address</label>
            <input
              id="guest-email"
              name="email"
              type="email"
              required
              autoComplete="email"
              placeholder="you@example.com"
            />
          </div>

          <div className="form-grp">
            <label htmlFor="guest-phone">Phone number</label>
            <input
              id="guest-phone"
              name="phone"
              type="tel"
              required
              autoComplete="tel"
              placeholder="+1 555 0100"
            />
          </div>

          <span className="text">
            By placing your order you&apos;ll get a 99accs account so you can
            track this order and any future purchases. We&apos;ll email you a
            link to set a password.
          </span>

          {touched && error && (
            <p className="auth-form__error" style={{ marginBottom: 20 }}>
              {error}
            </p>
          )}

          <button type="submit" className="tg-btn" disabled={loading}>
            {loading ? 'Placing order…' : 'Continue to payment'}
          </button>
        </form>
      </div>
    </div>
  );
}
