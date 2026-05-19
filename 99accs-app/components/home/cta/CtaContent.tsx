import { CtaButtons } from './CtaButtons';
import type { HomeCta } from '@/lib/api/types';

// --- Particles background placeholder ---
export function CtaParticles() {
  return <div id="cta-particles"></div>;
}

// --- Section heading ---
export function CtaTitle({ lines }: { lines: string[] }) {
  return (
    <h2 className="title">
      {lines.map((line, i) => (
        <span key={i}>
          {i > 0 && <br />}
          {line}
        </span>
      ))}
    </h2>
  );
}

// --- Full content block: title + button group ---
export function CtaContent({ data }: { data: HomeCta }) {
  return (
    <div className="cta__content">
      <CtaTitle lines={data.title_lines} />
      <CtaButtons buttons={data.buttons} />
    </div>
  );
}
