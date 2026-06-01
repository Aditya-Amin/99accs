import type { DescriptionFaqItem } from '@/lib/api/types';

interface Props {
  items: DescriptionFaqItem[];
}

export default function ProductFaqSection({ items }: Props) {
  if (!items.length) return null;

  return (
    <div className="shop__description-inner">
      <h2 className="title-two">Frequently Asked Questions</h2>
      <ul className="list-wrap faq-list">
        {items.map((item, i) => (
          <li key={i} className="faq-list-item">
            <h2 className="title">{item.question}</h2>
            <p>{item.answer}</p>
          </li>
        ))}
      </ul>
    </div>
  );
}
