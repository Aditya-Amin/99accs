'use client';
import { useState, FormEvent } from 'react';

export default function ProfileClient() {
  const [saved, setSaved] = useState(false);
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setLoading(true);
    await new Promise((r) => setTimeout(r, 600));
    setLoading(false);
    setSaved(true);
    setTimeout(() => setSaved(false), 3000);
  };

  return (
    <form onSubmit={handleSubmit} style={{ maxWidth: 500 }}>
      <div className="form-grp" style={{ marginBottom: 16 }}>
        <label>Full Name</label>
        <input type="text" defaultValue="Demo User" style={{ width: '100%', padding: '10px 14px', background: 'transparent', border: '1px solid rgba(255,255,255,0.15)', color: 'inherit', borderRadius: 4 }} />
      </div>
      <div className="form-grp" style={{ marginBottom: 16 }}>
        <label>Email Address</label>
        <input type="email" defaultValue="demo@99accs.com" style={{ width: '100%', padding: '10px 14px', background: 'transparent', border: '1px solid rgba(255,255,255,0.15)', color: 'inherit', borderRadius: 4 }} />
      </div>
      <div className="form-grp" style={{ marginBottom: 16 }}>
        <label>New Password <span style={{ opacity: 0.5, fontSize: '0.85em' }}>(leave blank to keep current)</span></label>
        <input type="password" placeholder="••••••••" style={{ width: '100%', padding: '10px 14px', background: 'transparent', border: '1px solid rgba(255,255,255,0.15)', color: 'inherit', borderRadius: 4 }} />
      </div>
      {saved && <p style={{ color: '#4ade80', marginBottom: 12 }}>Profile updated successfully.</p>}
      <button type="submit" className="tg-btn" disabled={loading}>{loading ? 'Saving...' : 'Save Changes'}</button>
    </form>
  );
}
