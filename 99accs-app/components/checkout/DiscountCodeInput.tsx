'use client';
import { useState, FormEvent } from 'react';

interface Props {
  currentCode: string | null;
  onApply: (code: string) => void;
  onClear: () => void;
}

// Pill button → expanded input. Collapsed by default. When a code is already
// applied (currentCode is non-null), the pill is replaced by an "applied"
// chip with a clear/× button.
export function DiscountCodeInput({ currentCode, onApply, onClear }: Props) {
  const [open, setOpen] = useState(false);
  const [code, setCode] = useState('');

  const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    const trimmed = code.trim();
    if (!trimmed) return;
    onApply(trimmed);
    setCode('');
    setOpen(false);
  };

  if (currentCode) {
    return (
      <div className="discount-applied">
        <span>🏷 Code applied: <strong>{currentCode}</strong></span>
        <button type="button" className="discount-applied__clear" onClick={onClear} aria-label="Remove code">
          ×
        </button>
      </div>
    );
  }

  if (!open) {
    return (
      <button type="button" className="discount-pill" onClick={() => setOpen(true)}>
        🏷 Got a discount code? <span className="discount-pill__cta">Click here to apply</span>
      </button>
    );
  }

  return (
    <form onSubmit={handleSubmit} className="discount-form">
      <input
        type="text"
        value={code}
        onChange={(e) => setCode(e.target.value)}
        placeholder="Enter code"
        autoFocus
      />
      <button type="submit" className="tg-btn">Apply</button>
      <button type="button" className="discount-form__cancel" onClick={() => setOpen(false)} aria-label="Cancel">
        ×
      </button>
    </form>
  );
}
