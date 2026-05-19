// Static trust badge. The HTML/Next versions render the same green-tinted
// pill — it intentionally uses 99accs theme green (not Trustpilot green)
// so the visual language stays consistent with the rest of the site.
export function TrustpilotBadge() {
  return (
    <div className="trustpilot-badge">
      <div className="trustpilot-badge__top">
        <span className="trustpilot-badge__stars" aria-label="4.8 out of 5 stars">
          {Array.from({ length: 5 }).map((_, i) => (
            <svg key={i} width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path d="M8 1l2.163 4.382 4.837.703-3.5 3.412.826 4.815L8 12.027 3.674 14.312l.826-4.815-3.5-3.412 4.837-.703L8 1z" fill="var(--tg-theme-primary)" />
            </svg>
          ))}
        </span>
        <span className="trustpilot-badge__score">4.8</span>
      </div>
      <p className="trustpilot-badge__text">
        Excellent · Based on <strong>15,224</strong> reviews
      </p>
    </div>
  );
}
