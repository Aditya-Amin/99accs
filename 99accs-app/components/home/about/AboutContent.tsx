import { AboutStats } from './AboutStats';
import type { HomeAbout } from '@/lib/api/types';

// --- Section heading ---
export function AboutSectionTitle({ title }: { title: string }) {
  return (
    <div className="section__title mb-25">
      <h2 className="title">{title}</h2>
    </div>
  );
}

// --- Body paragraphs ---
export function AboutText({ paragraphs }: { paragraphs: string[] }) {
  return (
    <>
      {paragraphs.map((p, i) => (
        <p key={i}>{p}</p>
      ))}
    </>
  );
}

// --- Right column: title + text + stats ---
export function AboutContent({ data }: { data: HomeAbout }) {
  return (
    <div className="about__content">
      <AboutSectionTitle title={data.title} />
      <AboutText paragraphs={data.paragraphs} />
      <AboutStats happy_customers={data.stats.happy_customers} accounts_sold={data.stats.accounts_sold} />
    </div>
  );
}
