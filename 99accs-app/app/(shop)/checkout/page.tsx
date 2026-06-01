import { CheckoutStarter } from '@/components/checkout/CheckoutStarter';
import { PageBreadcrumb } from '@/components/ui/PageBreadcrumb';

// /checkout has no session id of its own. It's the transition between Cart
// and /checkout/{id}: this page reads the local cart, asks the backend to
// open a session, and redirects to /checkout/{returned-id}.
//
// Wrapping in PageBreadcrumb + container keeps the loading state visually
// consistent with the rest of the app (avoids a blank flash before the
// session redirect lands).
export default function CheckoutBootstrapPage() {
  return (
    <main className="main-area fix">
      <PageBreadcrumb title="Checkout" />
      <section className="checkout__area section-pb-130">
        <div className="container">
          <CheckoutStarter />
        </div>
      </section>
    </main>
  );
}
