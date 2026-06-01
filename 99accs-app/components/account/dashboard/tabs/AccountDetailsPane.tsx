'use client';
import { useState, FormEvent } from 'react';
import { updateProfileBff, changePassword } from '@/lib/api/endpoints';
import type { AuthUser } from '@/lib/api/types';

interface Props {
  user: AuthUser | null;
}

type SaveState = 'idle' | 'saving' | 'saved' | 'error';

export function AccountDetailsPane({ user }: Props) {
  // ── Profile form state ───────────────────────────────────────────────────
  const [firstName, setFirstName] = useState(user?.first_name ?? '');
  const [lastName,  setLastName]  = useState(user?.last_name  ?? '');
  const [email,     setEmail]     = useState(user?.email      ?? '');
  const [phone,     setPhone]     = useState(user?.phone      ?? '');

  // ── Password form state ──────────────────────────────────────────────────
  const [currentPw, setCurrentPw] = useState('');
  const [newPw,     setNewPw]     = useState('');
  const [confirmPw, setConfirmPw] = useState('');

  const [saveState,   setSaveState]   = useState<SaveState>('idle');
  const [errorMsg,    setErrorMsg]    = useState('');
  const [pwState,     setPwState]     = useState<SaveState>('idle');
  const [pwErrorMsg,  setPwErrorMsg]  = useState('');

  // ── Save profile ─────────────────────────────────────────────────────────
  async function handleProfileSave(e: FormEvent) {
    e.preventDefault();
    setSaveState('saving');
    setErrorMsg('');
    try {
      await updateProfileBff({ first_name: firstName, last_name: lastName, email, phone: phone || undefined });
      setSaveState('saved');
      setTimeout(() => setSaveState('idle'), 3000);
    } catch (err: unknown) {
      const msg = err instanceof Error ? err.message : 'Failed to save profile.';
      setErrorMsg(msg);
      setSaveState('error');
    }
  }

  // ── Change password ──────────────────────────────────────────────────────
  async function handlePasswordSave(e: FormEvent) {
    e.preventDefault();
    if (!currentPw || !newPw) return;
    if (newPw !== confirmPw) {
      setPwErrorMsg('New passwords do not match.');
      setPwState('error');
      return;
    }
    setPwState('saving');
    setPwErrorMsg('');
    try {
      await changePassword(currentPw, newPw, confirmPw);
      setPwState('saved');
      setCurrentPw('');
      setNewPw('');
      setConfirmPw('');
      setTimeout(() => setPwState('idle'), 3000);
    } catch (err: unknown) {
      const msg = err instanceof Error ? err.message : 'Failed to change password.';
      setPwErrorMsg(msg);
      setPwState('error');
    }
  }

  return (
    <div id="tab4" className="account-pane active">
      {/* ── Profile info form ──────────────────────────────────────────────── */}
      <form
        className="account-form customer__form-wrap customer__form-wrap-two"
        onSubmit={handleProfileSave}
      >
        <div className="row">
          <div className="col-md-6">
            <div className="form-grp">
              <label htmlFor="firstName">First name <span>*</span></label>
              <input
                type="text"
                id="firstName"
                value={firstName}
                onChange={(e) => setFirstName(e.target.value)}
                required
              />
            </div>
          </div>
          <div className="col-md-6">
            <div className="form-grp">
              <label htmlFor="lastName">Last name <span>*</span></label>
              <input
                type="text"
                id="lastName"
                value={lastName}
                onChange={(e) => setLastName(e.target.value)}
              />
            </div>
          </div>
        </div>
        <div className="form-grp">
          <label htmlFor="emailAddress">Email address <span>*</span></label>
          <input
            type="email"
            id="emailAddress"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            required
          />
        </div>
        <div className="form-grp">
          <label htmlFor="phone">Phone number</label>
          <input
            type="tel"
            id="phone"
            value={phone ?? ''}
            onChange={(e) => setPhone(e.target.value)}
            placeholder="Optional"
          />
        </div>

        {saveState === 'error' && (
          <p style={{ color: '#ff4639', marginBottom: 12, fontSize: '0.875rem' }}>{errorMsg}</p>
        )}
        {saveState === 'saved' && (
          <p style={{ color: '#00fc70', marginBottom: 12, fontSize: '0.875rem' }}>Profile saved successfully.</p>
        )}

        <button type="submit" className="tg-btn" disabled={saveState === 'saving'}>
          {saveState === 'saving' ? 'Saving…' : 'Save changes'}
        </button>
      </form>

      {/* ── Password change form ───────────────────────────────────────────── */}
      <form
        className="account-form customer__form-wrap customer__form-wrap-two"
        style={{ marginTop: '32px' }}
        onSubmit={handlePasswordSave}
      >
        <h2 className="title">Password change</h2>
        <div className="form-grp">
          <label htmlFor="currentPassword">Current password</label>
          <input
            type="password"
            id="currentPassword"
            value={currentPw}
            onChange={(e) => setCurrentPw(e.target.value)}
            autoComplete="current-password"
          />
        </div>
        <div className="form-grp">
          <label htmlFor="newPassword">New password</label>
          <input
            type="password"
            id="newPassword"
            value={newPw}
            onChange={(e) => setNewPw(e.target.value)}
            autoComplete="new-password"
          />
        </div>
        <div className="form-grp">
          <label htmlFor="confirmPassword">Confirm new password</label>
          <input
            type="password"
            id="confirmPassword"
            value={confirmPw}
            onChange={(e) => setConfirmPw(e.target.value)}
            autoComplete="new-password"
          />
        </div>

        {pwState === 'error' && (
          <p style={{ color: '#ff4639', marginBottom: 12, fontSize: '0.875rem' }}>{pwErrorMsg}</p>
        )}
        {pwState === 'saved' && (
          <p style={{ color: '#00fc70', marginBottom: 12, fontSize: '0.875rem' }}>Password changed successfully.</p>
        )}

        <button
          type="submit"
          className="tg-btn"
          disabled={pwState === 'saving' || !currentPw || !newPw}
        >
          {pwState === 'saving' ? 'Changing…' : 'Change password'}
        </button>
      </form>
    </div>
  );
}
