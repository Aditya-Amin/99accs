import type { ProductDescriptionSection, DescriptionFaqItem } from '@/lib/api/types';
import ProductFaqSection from './ProductFaqSection';

interface DescriptionSectionsProps {
  sections: ProductDescriptionSection[];
  faqItems?: DescriptionFaqItem[];
  htmlFallback?: string | null;
}

function SectionBody({ section }: { section: ProductDescriptionSection }) {
  if (section.type === 'paragraph') {
    return (
      <>
        {section.items.map((p, i) => <p key={i}>{p}</p>)}
      </>
    );
  }
  if (section.type === 'list') {
    const cls = section.list_class ? `list-wrap ${section.list_class}` : 'list-wrap inside-list';
    return (
      <ul className={cls}>
        {section.items.map((it, i) => <li key={i}>{it}</li>)}
      </ul>
    );
  }
  return null;
}

export default function DescriptionSections({ sections, faqItems, htmlFallback }: DescriptionSectionsProps) {
  // Rich-text HTML description takes priority — renders with full emoji/formatting support.
  // description_sections is used only when description is absent.
  if (htmlFallback) {
    return (
      <div
        className="shop__description-wrap"
        dangerouslySetInnerHTML={{ __html: htmlFallback }}
      />
    );
  }

  const contentSections = (Array.isArray(sections) ? sections : []).filter((s) => s.type !== 'faq');
  const hasFaq = (faqItems?.length ?? 0) > 0;

  if (!contentSections.length && !hasFaq) return null;

  const [first, ...rest] = contentSections;

  return (
    <div className="shop__description-wrap">
      {first && (
        <>
          {first.heading && <h2 className="title">{first.heading}</h2>}
          <SectionBody section={first} />
        </>
      )}

      {rest.map((s, i) => (
        <div key={i} className="shop__description-inner">
          <h2 className="title-two">{s.heading}</h2>
          <SectionBody section={s} />
        </div>
      ))}

      {hasFaq && <ProductFaqSection items={faqItems!} />}
    </div>
  );
}
