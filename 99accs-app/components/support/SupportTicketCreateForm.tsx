'use client';
import { FormEvent, useState, CSSProperties } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';

// Shared field style — matches the ContactForm pattern. The
// `support__table-form-two` CSS only styles textareas, so bare <input> and
// <select> children fall through to the dark page background with no border
// and become invisible. Inline styles keep this colocated with the form.
const FIELD: CSSProperties = {
  width: '100%',
  padding: '12px 16px',
  background: 'transparent',
  border: '1px solid rgba(255,255,255,0.15)',
  color: 'inherit',
  borderRadius: 4,
  marginTop: 6,
  outline: 'none',
};

const FIELD_LABEL: CSSProperties = {
  display: 'block',
  marginBottom: 4,
  fontSize: '0.95em',
  opacity: 0.85,
};

// Native <option> elements ignore the parent <select>'s background/color
// — they render with the browser's OS dropdown chrome. Override per-option
// so the popup panel matches the dark app background (--tg-color-dark) and
// keeps light text. Modern Chrome/Firefox/Edge honor these inline values.
const OPTION: CSSProperties = {
  background: '#000E06',
  color: '#FFF',
};

export default function SupportTicketCreateForm() {
  const router = useRouter();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const onSubmit = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setError(null);
    setLoading(true);
    try {
      const fd = new FormData(e.currentTarget);
      const res = await fetch('/api/support/tickets', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          subject: fd.get('subject'),
          body: fd.get('body'),
          game: fd.get('game'),
        }),
        credentials: 'include',
      });
      if (res.status === 401) {
        router.replace('/support');
        return;
      }
      if (!res.ok) throw new Error('Failed to create ticket');
      router.push('/support/tickets');
      router.refresh();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to create ticket');
    } finally {
      setLoading(false);
    }
  };

  return (
    <section className="support__area section-pb-130">
      <div className="container">
        <div className="support__table-wrap">
          <div className="support__table-top-two">
            <div className="support__table-top-left">
              <Link href="/support/tickets" className="icon" aria-label="Back to tickets">
                <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M6.38821 11.4864L13.2632 18.3614C13.3271 18.4252 13.4029 18.4759 13.4864 18.5105C13.5698 18.545 13.6593 18.5628 13.7496 18.5628C13.84 18.5628 13.9294 18.545 14.0129 18.5105C14.0963 18.4759 14.1721 18.4252 14.236 18.3614C14.2999 18.2975 14.3506 18.2217 14.3851 18.1382C14.4197 18.0547 14.4375 17.9653 14.4375 17.875C14.4375 17.7846 14.4197 17.6952 14.3851 17.6117C14.3506 17.5283 14.2999 17.4524 14.236 17.3885L7.84657 11L14.236 4.61136C14.365 4.48236 14.4375 4.30739 14.4375 4.12495C14.4375 3.94252 14.365 3.76755 14.236 3.63855C14.107 3.50955 13.9321 3.43707 13.7496 3.43707C13.5672 3.43707 13.3922 3.50955 13.2632 3.63855L6.38821 10.5135C6.32429 10.5774 6.27358 10.6532 6.23898 10.7367C6.20438 10.8201 6.18658 10.9096 6.18658 11C6.18658 11.0903 6.20438 11.1798 6.23898 11.2632C6.27358 11.3467 6.32429 11.4225 6.38821 11.4864Z" fill="currentColor" />
                </svg>
              </Link>
              <div className="order-info">
                <div className="content">
                  <h2 className="title">Create a new ticket</h2>
                  <p style={{ marginTop: 6, opacity: 0.7 }}>
                    Tell us what&rsquo;s going on. An order number is generated automatically when you submit &mdash; you can reference it later from your ticket list.
                  </p>
                </div>
              </div>
            </div>
          </div>

          <form className="support__table-form-two" onSubmit={onSubmit}>
            <div className="form-grp" style={{ marginBottom: 18 }}>
              <label htmlFor="subject" style={FIELD_LABEL}>Subject</label>
              <input
                id="subject"
                name="subject"
                type="text"
                required
                maxLength={255}
                placeholder="e.g. Can't connect to the server"
                style={FIELD}
              />
            </div>

            <div className="form-grp" style={{ marginBottom: 18 }}>
              <label htmlFor="game" style={FIELD_LABEL}>Product</label>
              <select id="game" name="game" required defaultValue="" style={FIELD}>
                <option style={OPTION} value="" disabled>Select a product&hellip;</option>
                <option style={OPTION} value="valorant">Valorant</option>
                <option style={OPTION} value="fortnite">Fortnite</option>
                <option style={OPTION} value="legends">League Of Legends</option>
              </select>
            </div>

            <div className="form-grp" style={{ marginBottom: 18 }}>
              <label htmlFor="body" style={FIELD_LABEL}>Message</label>
              <textarea
                id="body"
                name="body"
                required
                maxLength={5000}
                placeholder="Describe the issue in as much detail as you can…"
              />
            </div>

            <div className="support__table-form-bottom">
              <div className="left-side">
                <div className="upload-box">
                  <input type="file" id="fileInput" hidden />
                  <label htmlFor="fileInput" className="upload-btn">CLICK TO UPLOAD</label>
                </div>
                <span>Supported Types: Photos and max file size: 5.0MB</span>
              </div>
              <div className="right-side">
                {error && <span style={{ color: 'crimson', marginRight: 12 }}>{error}</span>}
                <button type="submit" className="tg-btn" disabled={loading}>
                  {loading ? 'Submitting…' : 'Submit ticket'}
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </section>
  );
}
