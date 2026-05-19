interface Props {
  id: string;
  size?: number;
}

// Per-method icon. Rendered inline so the bundle isn't dragged down by an
// img-tag set; uses 99accs theme green (--tg-theme-primary) for accents.
export function PaymentIcon({ id, size = 36 }: Props) {
  const common = { width: size, height: size, viewBox: '0 0 36 36', fill: 'none' as const };
  switch (id) {
    case 'card':
      return (
        <svg {...common} xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <rect x="3" y="8" width="30" height="20" rx="3" fill="rgba(0,252,112,0.12)" stroke="var(--tg-theme-primary)" strokeWidth="1.4" />
          <rect x="3" y="13" width="30" height="3" fill="var(--tg-theme-primary)" />
          <rect x="7" y="21" width="8" height="2" rx="1" fill="rgba(255,255,255,0.6)" />
        </svg>
      );
    case 'apple_pay':
      return (
        <svg {...common} xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <rect x="2" y="9" width="32" height="18" rx="3" fill="rgba(255,255,255,0.05)" stroke="rgba(255,255,255,0.15)" />
          <text x="18" y="22" textAnchor="middle" fontSize="9" fontWeight="700" fill="#fff" fontFamily="-apple-system,BlinkMacSystemFont,sans-serif"></text>
          <text x="22" y="22" textAnchor="middle" fontSize="9" fontWeight="700" fill="#fff" fontFamily="-apple-system,BlinkMacSystemFont,sans-serif">Pay</text>
        </svg>
      );
    case 'google_pay':
      return (
        <svg {...common} xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <rect x="2" y="9" width="32" height="18" rx="3" fill="rgba(255,255,255,0.05)" stroke="rgba(255,255,255,0.15)" />
          <text x="9" y="22" fontSize="9" fontWeight="700" fill="#4285F4">G</text>
          <text x="14" y="22" fontSize="9" fontWeight="700" fill="#EA4335">o</text>
          <text x="18" y="22" fontSize="9" fontWeight="700" fill="#FBBC04">o</text>
          <text x="22" y="22" fontSize="9" fontWeight="700" fill="#34A853">Pay</text>
        </svg>
      );
    case 'paysafe':
      return (
        <svg {...common} xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <rect x="3" y="6" width="30" height="24" rx="3" fill="#FFC107" />
          <text x="18" y="22" textAnchor="middle" fontSize="9" fontWeight="700" fill="#1a1a1a">paysafe</text>
        </svg>
      );
    case 'crypto':
      return (
        <svg {...common} xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <circle cx="18" cy="18" r="13" fill="rgba(0,252,112,0.15)" stroke="var(--tg-theme-primary)" strokeWidth="1.4" />
          <text x="18" y="22" textAnchor="middle" fontSize="14" fontWeight="800" fill="var(--tg-theme-primary)">₿</text>
        </svg>
      );
    case 'skrill':
      return (
        <svg {...common} xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <rect x="3" y="6" width="30" height="24" rx="3" fill="#862165" />
          <text x="18" y="22" textAnchor="middle" fontSize="10" fontWeight="700" fill="#fff">Skrill</text>
        </svg>
      );
    default:
      return (
        <svg {...common} xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <circle cx="18" cy="18" r="12" fill="rgba(255,255,255,0.06)" stroke="rgba(255,255,255,0.18)" />
        </svg>
      );
  }
}
