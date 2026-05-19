'use client';

interface Props {
  value: number;
  onChange: (next: number) => void;
  min?: number;
  max?: number;
}

// Renders the .cart-plus-minus markup used on cart.html (and the qty
// selector on product detail). Visual styling comes from globals.css —
// the +/- buttons here use the dec/inc qtybutton classes the CSS
// targets, with inline SVGs that match what RouteEffects.tsx would
// have jQuery-injected on a vanilla HTML page.
//
// React-controlled so the value can drive a real store. RouteEffects'
// idempotent injection check (`!el.children('.dec.qtybutton').length`)
// skips this element because the buttons are already present.

const MINUS_SVG = (
  <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M14.25 9.75C14.25 9.94891 14.171 10.1397 14.0303 10.2803C13.8897 10.421 13.6989 10.5 13.5 10.5H6C5.80109 10.5 5.61033 10.421 5.46967 10.2803C5.32902 10.1397 5.25 9.94891 5.25 9.75C5.25 9.55109 5.32902 9.36032 5.46967 9.21967C5.61033 9.07902 5.80109 9 6 9H13.5C13.6989 9 13.8897 9.07902 14.0303 9.21967C14.171 9.36032 14.25 9.55109 14.25 9.75Z" fill="currentColor" />
  </svg>
);

const PLUS_SVG = (
  <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M14.25 9.75C14.25 9.94891 14.171 10.1397 14.0303 10.2803C13.8897 10.421 13.6989 10.5 13.5 10.5H10.5V13.5C10.5 13.6989 10.421 13.8897 10.2803 14.0303C10.1397 14.171 9.94892 14.25 9.75 14.25C9.55109 14.25 9.36033 14.171 9.21967 14.0303C9.07902 13.8897 9 13.6989 9 13.5V10.5H6C5.80109 10.5 5.61033 10.421 5.46967 10.2803C5.32902 10.1397 5.25 9.94891 5.25 9.75C5.25 9.55109 5.32902 9.36032 5.46967 9.21967C5.61033 9.07902 5.80109 9 6 9H9V6C9 5.80109 9.07902 5.61032 9.21967 5.46967C9.36033 5.32902 9.55109 5.25 9.75 5.25C9.94892 5.25 10.1397 5.32902 10.2803 5.46967C10.421 5.61032 10.5 5.80109 10.5 6V9H13.5C13.6989 9 13.8897 9.07902 14.0303 9.21967C14.171 9.36032 14.25 9.55109 14.25 9.75Z" fill="currentColor" />
  </svg>
);

export function QuantityStepper({ value, onChange, min = 1, max = 99 }: Props) {
  const dec = () => onChange(Math.max(min, value - 1));
  const inc = () => onChange(Math.min(max, value + 1));

  return (
    <div className="cart-plus-minus">
      <div className="dec qtybutton" onClick={dec} role="button" aria-label="Decrease">
        {MINUS_SVG}
      </div>
      <input
        type="text"
        value={value}
        onChange={(e) => {
          const n = parseInt(e.target.value.replace(/\D/g, ''), 10);
          if (!Number.isFinite(n)) return;
          onChange(Math.min(max, Math.max(min, n)));
        }}
      />
      <div className="inc qtybutton" onClick={inc} role="button" aria-label="Increase">
        {PLUS_SVG}
      </div>
    </div>
  );
}
