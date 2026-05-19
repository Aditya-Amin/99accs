import type { ProductDescriptionSection } from '@/lib/api/types';

interface DescriptionSectionsProps {
  sections: ProductDescriptionSection[];
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
    const className = section.list_class ? `list-wrap ${section.list_class}` : 'list-wrap inside-list';
    return (
      <ul className={className}>
        {section.items.map((it, i) => <li key={i}>{it}</li>)}
      </ul>
    );
  }
  return (
    <ul className="list-wrap faq-list">
      {section.items.map((it, i) => (
        <li key={i} className="faq-list-item">
          <h2 className="title">{it.question}</h2>
          <p>{it.answer}</p>
        </li>
      ))}
    </ul>
  );
}

export default function DescriptionSections({ sections }: DescriptionSectionsProps) {
  if (!sections.length) return null;
  const [first, ...rest] = sections;

  return (
    <div className="shop__description-wrap">
      <SectionBody section={first} />
      {rest.map((s, i) => (
        <div key={i} className="shop__description-inner">
          <h2 className="title-two">{s.heading}</h2>
          <SectionBody section={s} />
        </div>
      ))}
    </div>
  );
}
