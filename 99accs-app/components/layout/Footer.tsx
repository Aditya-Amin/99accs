import Link from 'next/link';
import {
  IconDiscord, IconTelegram, IconFacebook, IconInstagram,
  IconTicket, IconSubmitTicket, IconEmail, IconSupportArticles,
  IconFAQ, IconTerms, IconPrivacy, IconCookie, IconCartSmall, IconBlog,
} from '@/components/icons';
import type {
  FooterData, FooterHelpCtaConfig, FooterMenuWidgetConfig,
  FooterQuickLinkIcon, FooterSocialPlatform,
} from '@/lib/api/types';

// ── Icon maps ─────────────────────────────────────────────────────────────────

const SOCIAL_ICON_MAP: Record<FooterSocialPlatform, React.ComponentType> = {
  discord:   IconDiscord,
  telegram:  IconTelegram,
  facebook:  IconFacebook,
  instagram: IconInstagram,
};

const QUICK_LINK_ICON_MAP: Record<FooterQuickLinkIcon, React.ComponentType> = {
  submit_ticket:    IconSubmitTicket,
  account_email:    IconEmail,
  support_articles: IconSupportArticles,
  faq:              IconFAQ,
  terms:            IconTerms,
  privacy:          IconPrivacy,
  cookie:           IconCookie,
  cart:             IconCartSmall,
  blog:             IconBlog,
};

// ── Widget renderers ──────────────────────────────────────────────────────────

function MenuWidget({ config }: { config: FooterMenuWidgetConfig }) {
  return (
    <div className="footer__widget">
      <h2 className="footer__widget-title">
        {config.icon_url && <img src={config.icon_url} alt="icon" />}
        {config.title}
      </h2>
      <ul className="footer__widget-link list-wrap">
        {config.links.map((link, i) => (
          <li key={i}>
            <Link href={link.href}>{link.label}</Link>
          </li>
        ))}
      </ul>
    </div>
  );
}

function HelpCtaWidget({ config }: { config: FooterHelpCtaConfig }) {
  return (
    <div className="footer__widget">
      <h2 className="footer__widget-title">{config.title}</h2>
      <div className="footer__content">
        <p>{config.description}</p>
        <Link href={config.button_href} className="border-btn">
          <IconTicket />
          {config.button_label}
        </Link>
      </div>
    </div>
  );
}

// ── Main component ────────────────────────────────────────────────────────────

export default function Footer({ data }: { data: FooterData }) {
  const { settings, widgets } = data;
  const copyrightText = (settings.copyright ?? '').replace('{year}', String(new Date().getFullYear()));

  return (
    <footer className="footer__area">
      <div className="container">

        {/* Logo + Socials */}
        <div className="footer__logo-wrap">
          <div className="footer__logo">
            <Link href={settings.logo_href ?? '/'}>
              {settings.logo && <img src={settings.logo} alt="logo" />}
            </Link>
          </div>
          <ul className="list-wrap footer__social">
            {settings.social_links.map((s, i) => {
              const Icon = SOCIAL_ICON_MAP[s.platform];
              return Icon ? (
                <li key={i}>
                  <a href={s.url} target="_blank" rel="noreferrer"><Icon /></a>
                </li>
              ) : null;
            })}
          </ul>
        </div>

        {/* Widget columns */}
        {widgets.length > 0 && (
          <div className="footer__top">
            <div className="row">
              {widgets.map((widget) => (
                <div key={widget.id} className={widget.col_class}>
                  {widget.type === 'menu' ? (
                    <MenuWidget config={widget.config as FooterMenuWidgetConfig} />
                  ) : (
                    <HelpCtaWidget config={widget.config as FooterHelpCtaConfig} />
                  )}
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Quick links bar */}
        {settings.quick_links.length > 0 && (
          <div className="footer__menu-wrap">
            <ul className="list-wrap">
              {settings.quick_links.map((ql, i) => {
                const Icon = QUICK_LINK_ICON_MAP[ql.icon];
                return (
                  <li key={i}>
                    <Link href={ql.href ?? '#'}>
                      {Icon && <Icon />} {ql.label}
                    </Link>
                  </li>
                );
              })}
            </ul>
          </div>
        )}

        {/* Footer bottom */}
        <div className="footer__bottom">
          <div className="row align-items-center">
            <div className="col-lg-6 order-0 order-lg-2">
              {settings.payment_image && (
                <div className="cart__img">
                  <img src={settings.payment_image} alt="payment methods" />
                </div>
              )}
            </div>
            <div className="col-lg-6">
              <div className="copyright-text">
                <p>
                  {copyrightText}{' '}
                  <Link href={settings.copyright_href ?? '/'}>{settings.copyright_site_name}</Link>
                </p>
              </div>
            </div>
          </div>
        </div>

      </div>
    </footer>
  );
}
