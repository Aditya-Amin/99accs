import type { ProductGuarantee } from '@/lib/api/types';

const DEFAULT_GUARANTEE: ProductGuarantee = {
  title: '99Accs Guarantee',
  body: 'Your purchase made on the 99Accs platform are protected by us.',
};

interface ProductGuaranteeBoxProps {
  guarantee?: ProductGuarantee;
}

export default function ProductGuaranteeBox({ guarantee }: ProductGuaranteeBoxProps) {
  const g = guarantee ?? DEFAULT_GUARANTEE;
  return (
    <div className="shop__details-guarantee">
      <div className="icon">
        <img src="/img/icons/guarantee_icon.svg" alt="" />
      </div>
      <div className="content">
        <h2 className="guarantee-title">{g.title}</h2>
        <p>{g.body}</p>
      </div>
    </div>
  );
}
