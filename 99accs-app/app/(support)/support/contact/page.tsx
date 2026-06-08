import { SupportBreadcrumb } from '@/components/support/contact/SupportBreadcrumb';
import ContactForm from '@/components/support/ContactForm';
import CtaSection from '@/components/home/CtaSection';
import { getMockHome } from '@/lib/mock/home';

// TODO: swap getMockHome() → getHome() from '@/lib/api/endpoints' once Laravel API is live
export default function ContactPage() {
  const { cta } = getMockHome();

  return (
    <main className="main-area fix">
      <SupportBreadcrumb title="Contact Us" />

      <section className="support__area section-pb-130">
        <div className="container">
          <div className="row justify-content-center">
            <div className="col-xl-8 col-lg-10">
              <div className="support__wrap" style={{ marginBottom: 40 }}>
                <span className="shape"></span>
                <img src="/img/icons/support_icon01.png" alt="" />
                <h2 className="title">
                  <span>Get in Touch</span> We&rsquo;re Here to Help
                </h2>
                <p>Send us a message and our team will get back to you within 24 hours.</p>
              </div>
              <ContactForm />
            </div>
          </div>
        </div>
      </section>

      <CtaSection data={cta} />
    </main>
  );
}
