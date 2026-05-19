'use client';

interface Props {
  checked: boolean;
  onChange: (checked: boolean) => void;
}

// Lifetime Warranty upsell. Theme-green ON state instead of GameBoost blue.
// role="switch" + aria-checked make this screen-reader friendly per the guide.
export function WarrantyToggle({ checked, onChange }: Props) {
  return (
    <div className={`warranty-upsell${checked ? ' is-on' : ''}`}>
      <div className="warranty-upsell__head">
        <div className="warranty-upsell__title">
          <span className="warranty-upsell__shield" aria-hidden="true">
            <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M11 1L3 4v6c0 5 3.5 9.5 8 11 4.5-1.5 8-6 8-11V4l-8-3z" stroke="var(--tg-theme-primary)" strokeWidth="1.5" fill="rgba(0,252,112,0.12)" />
              <path d="M7.5 11l2.5 2.5L14.5 9" stroke="var(--tg-theme-primary)" strokeWidth="1.5" fill="none" />
            </svg>
          </span>
          <span>Get Lifetime Warranty</span>
          <span className="warranty-upsell__pill">+15%</span>
        </div>
        <button
          type="button"
          role="switch"
          aria-checked={checked}
          className="warranty-upsell__switch"
          onClick={() => onChange(!checked)}
        >
          <span className="warranty-upsell__switch-thumb" />
        </button>
      </div>
      <p className="warranty-upsell__desc">
        Account replacement guaranteed for life if anything goes wrong. <a href="#" onClick={(e) => e.preventDefault()}>Learn More</a>
      </p>
    </div>
  );
}
