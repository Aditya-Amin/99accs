import type { ProductGuarantee } from '@/lib/api/types';

interface ProductGuaranteeBoxProps {
  guarantee?: ProductGuarantee;
}

export default function ProductGuaranteeBox({ guarantee }: ProductGuaranteeBoxProps) {
  if (!guarantee) return null;
  return (
    <div className="shop__details-guarantee">
      <div className="icon">
        <img src="/img/icons/guarantee_icon.svg" alt="" />
      </div>
      <div className="content">
        <h2 className="guarantee-title">{guarantee.title}</h2>
        <p>{guarantee.body}</p>
      </div>
    </div>
  );
}
