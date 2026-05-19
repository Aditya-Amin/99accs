import Link from 'next/link';
import {
  IconDiscord, IconTelegram, IconFacebook, IconInstagram, IconTicket,
  IconSubmitTicket, IconEmail, IconSupportArticles, IconFAQ,
  IconTerms, IconPrivacy, IconCookie, IconCartSmall, IconBlog,
} from '@/components/icons';

export default function Footer() {
  return (
    <footer className="footer__area">
      <div className="container">
        <div className="footer__logo-wrap">
          <div className="footer__logo">
            <Link href="/"><img src="/img/logo/logo.svg" alt="logo" /></Link>
          </div>
          <ul className="list-wrap footer__social">
            <li><a href="https://discord.com/" target="_blank" rel="noreferrer"><IconDiscord /></a></li>
            <li><a href="https://web.telegram.org/" target="_blank" rel="noreferrer"><IconTelegram /></a></li>
            <li><a href="https://www.facebook.com/" target="_blank" rel="noreferrer"><IconFacebook /></a></li>
            <li><a href="https://www.instagram.com/" target="_blank" rel="noreferrer"><IconInstagram /></a></li>
          </ul>
        </div>
        <div className="footer__top">
          <div className="row">
            <div className="col-lg-2 col-6">
              <div className="footer__widget">
                <h2 className="footer__widget-title">
                  <img src="/img/icons/header_cat01.svg" alt="icon" /> Valorant
                </h2>
                <ul className="footer__widget-link list-wrap">
                  <li><Link href="/shop/valorant?region=na">Valorant - NA</Link></li>
                  <li><Link href="/shop/valorant?region=eu">Valorant - EUROPE</Link></li>
                  <li><Link href="/shop/valorant?region=apac">Valorant - AP</Link></li>
                  <li><Link href="/shop/valorant?region=latam">Valorant - LATAM</Link></li>
                  <li><Link href="/shop/valorant?region=br">Valorant - BRAZIL</Link></li>
                </ul>
              </div>
            </div>
            <div className="col-lg-3 col-6">
              <div className="footer__widget">
                <h2 className="footer__widget-title">
                  <img src="/img/icons/header_cat02.svg" alt="icon" /> Fortnite
                </h2>
                <ul className="footer__widget-link list-wrap">
                  <li><Link href="/shop/fortnite">NFA Random Skins</Link></li>
                  <li><Link href="/shop/fortnite">NFA Guaranteed Skins</Link></li>
                  <li><Link href="/shop/fortnite">NFA Inactive Accounts</Link></li>
                  <li><Link href="/shop/fortnite">Skins + MAIL ACCESS</Link></li>
                  <li><Link href="/shop/fortnite">Exclusive Skins + Mail Access</Link></li>
                </ul>
              </div>
            </div>
            <div className="col-lg-3 col-sm-6">
              <div className="footer__widget">
                <h2 className="footer__widget-title">
                  <img src="/img/icons/header_cat03.svg" alt="icon" /> League Of Legends
                </h2>
                <ul className="footer__widget-link list-wrap">
                  <li><Link href="/shop/legends?region=na">North America (NA)</Link></li>
                  <li><Link href="/shop/legends?region=eu">Europe West (EUW)</Link></li>
                  <li><Link href="/shop/legends?region=apac">Southeast Asia (SEA)</Link></li>
                  <li><Link href="/shop/legends?region=latam">Latin America North (LAN)</Link></li>
                </ul>
              </div>
            </div>
            <div className="col-lg-4 col-sm-6">
              <div className="footer__widget">
                <h2 className="footer__widget-title">Need Help?</h2>
                <div className="footer__content">
                  <p>We&apos;re here to help. Our expert human-support team is at your service 24/7.</p>
                  <Link href="/support/contact" className="border-btn">
                    <IconTicket />
                    Create ticket
                  </Link>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div className="footer__menu-wrap">
          <ul className="list-wrap">
            <li><Link href="/support/contact"><IconSubmitTicket /> Submit Ticket</Link></li>
            <li><Link href="/account"><IconEmail /> Account Email</Link></li>
            <li><Link href="/support/articles"><IconSupportArticles /> Support Articles</Link></li>
            <li><Link href="#"><IconFAQ /> FAQ</Link></li>
            <li><Link href="#"><IconTerms /> Terms of Service</Link></li>
            <li><Link href="#"><IconPrivacy /> Privacy Policy</Link></li>
            <li><Link href="#"><IconCookie /> Cookie Policy</Link></li>
            <li><Link href="/cart"><IconCartSmall /> Cart</Link></li>
            <li><Link href="#"><IconBlog /> Blog</Link></li>
          </ul>
        </div>
        <div className="footer__bottom">
          <div className="row align-items-center">
            <div className="col-lg-6 order-0 order-lg-2">
              <div className="cart__img">
                <img src="/img/images/cart.png" alt="img" />
              </div>
            </div>
            <div className="col-lg-6">
              <div className="copyright-text">
                <p>Copyright &copy; 2021-{new Date().getFullYear()} All Rights Reserved By <Link href="/">99accs.com</Link></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </footer>
  );
}
