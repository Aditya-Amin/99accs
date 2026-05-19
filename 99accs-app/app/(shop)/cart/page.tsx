import CartClient from '@/components/cart/CartClient';
import { PageBreadcrumb } from '@/components/ui/PageBreadcrumb';

export default function CartPage() {
  return (
    <main className="main-area fix">
      <PageBreadcrumb title="Cart" />
      <div className="cart__area section-pb-130">
        <div className="container">
          <CartClient />
        </div>
      </div>
    </main>
  );
}
