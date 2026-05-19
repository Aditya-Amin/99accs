'use client';
import { useState, FormEvent } from 'react';

export default function ContactForm() {
  const [sent, setSent] = useState(false);
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setLoading(true);
    const fd = new FormData(e.currentTarget);
    await fetch('/api/mock/support/contact', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(Object.fromEntries(fd)),
    });
    setLoading(false);
    setSent(true);
  };

  if (sent) {
    return (
      <div style={{ textAlign: 'center', padding: '60px 32px', background: 'rgba(255,255,255,0.04)', border: '1px solid rgba(255,255,255,0.08)', borderRadius: 8 }}>
        <div style={{ fontSize: '3em', marginBottom: 16 }}>✓</div>
        <h3>Message Sent!</h3>
        <p style={{ opacity: 0.7, marginTop: 8 }}>We will get back to you within 24 hours.</p>
      </div>
    );
  }

  return (
    <form onSubmit={handleSubmit} style={{ padding: 32, background: 'rgba(255,255,255,0.04)', border: '1px solid rgba(255,255,255,0.08)', borderRadius: 8 }}>
      <div className="form-grp" style={{ marginBottom: 16 }}>
        <label>Full Name</label>
        <input name="name" type="text" placeholder="John Doe" required style={{ width: '100%', padding: '10px 14px', background: 'transparent', border: '1px solid rgba(255,255,255,0.15)', color: 'inherit', borderRadius: 4, marginTop: 6 }} />
      </div>
      <div className="form-grp" style={{ marginBottom: 16 }}>
        <label>Email Address</label>
        <input name="email" type="email" placeholder="john@example.com" required style={{ width: '100%', padding: '10px 14px', background: 'transparent', border: '1px solid rgba(255,255,255,0.15)', color: 'inherit', borderRadius: 4, marginTop: 6 }} />
      </div>
      <div className="form-grp" style={{ marginBottom: 16 }}>
        <label>Subject</label>
        <input name="subject" type="text" placeholder="How can we help?" required style={{ width: '100%', padding: '10px 14px', background: 'transparent', border: '1px solid rgba(255,255,255,0.15)', color: 'inherit', borderRadius: 4, marginTop: 6 }} />
      </div>
      <div className="form-grp" style={{ marginBottom: 24 }}>
        <label>Message</label>
        <textarea name="message" rows={5} placeholder="Describe your issue..." required style={{ width: '100%', padding: '10px 14px', background: 'transparent', border: '1px solid rgba(255,255,255,0.15)', color: 'inherit', borderRadius: 4, resize: 'vertical', marginTop: 6 }} />
      </div>
      <button type="submit" className="tg-btn" disabled={loading}>{loading ? 'Sending...' : 'Send Message'}</button>
    </form>
  );
}
