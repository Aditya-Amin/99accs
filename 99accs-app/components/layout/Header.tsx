'use client';
import { useEffect, useState } from 'react';
import Link from 'next/link';
import { useStickyHeader } from '@/lib/hooks/useStickyHeader';
import { useCartStore } from '@/lib/store/cartStore';
import { useUiStore } from '@/lib/store/uiStore';
import { useAuthStore } from '@/lib/store/authStore';
import { IconCart, IconDiscord, IconTelegram } from '@/components/icons';
import { Dropdown } from '@/components/ui/Dropdown';
import HeaderUserMenu from './HeaderUserMenu';

export default function Header() {
  const sticky = useStickyHeader();
  const cartTotal = useCartStore((s) => s.total());
  const { openAuthModal, toggleMobileMenu } = useUiStore();
  const authStatus = useAuthStore((s) => s.status);

  // Cart is localStorage-backed (zustand/persist). SSR renders $0.00; the
  // client hydrates with the real total. Gate the displayed value on a
  // mounted flag so server + first-client paint match — same pattern used
  // for authStatus above.
  const [cartHydrated, setCartHydrated] = useState(false);
  useEffect(() => setCartHydrated(true), []);

  return (
    <header className="transparent-header">
      <div id="header-fixed-height"></div>
      <div id="sticky-header" className={`tg-header__area${sticky ? ' sticky-menu' : ''}`}>
        <div className="container custom-container">
          <div className="row">
            <div className="col-12">
              <div className="tgmenu__wrap">
                <nav className="tgmenu__nav">
                  <div className="logo">
                    <Link href="/"><img src="/img/logo/logo.svg" alt="Logo" /></Link>
                  </div>
                  <div className="tgmenu__categories d-none d-xl-flex">
                    <ul className="list-wrap">
                      <li>
                        <ValorantCategoryDropdown />
                      </li>
                      <li>
                        <div className="dropdown">
                          <Link href="/shop/fortnite" className="dropdown-toggle-two">
                            <img src="/img/icons/header_cat02.svg" alt="icon" />
                            Fortnite
                          </Link>
                        </div>
                      </li>
                      <li>
                        <div className="dropdown">
                          <Link href="/shop/legends" className="dropdown-toggle-two">
                            <img src="/img/icons/header_cat03.svg" alt="icon" />
                            League Of Legends
                          </Link>
                        </div>
                      </li>
                    </ul>
                  </div>
                  <div className="tgmenu__navbar-wrap tgmenu__main-menu d-none d-xl-flex">
                    <ul className="navigation">
                      <li><Link href="/account">Account</Link></li>
                      <li><Link href="/support/contact">Contact</Link></li>
                      <li><Link href="/support">Help Articles</Link></li>
                      <li><Link href="#">Legal</Link></li>
                    </ul>
                  </div>
                  <div className="tgmenu__action">
                    <ul className="list-wrap">
                      <li className="header-cart">
                        <Link href="/cart" className="cart-count">
                          <IconCart />
                          <span>${(cartHydrated ? cartTotal : 0).toFixed(2)}</span>
                        </Link>
                      </li>
                      <li className="header-btn">
                        {/* During 'unknown' (pre-hydrate) render nothing — avoids a Sign-in → UserMenu flash for logged-in users. */}
                        {authStatus === 'authed' && <HeaderUserMenu />}
                        {authStatus === 'guest' && (
                          <button onClick={() => openAuthModal('login')} className="tg-btn">Sign in</button>
                        )}
                      </li>
                    </ul>
                  </div>
                  <div className="mobile-nav-toggler" onClick={toggleMobileMenu}>
                    <i className="tg-flaticon-menu-1"></i>
                  </div>
                </nav>
              </div>
            </div>
          </div>
        </div>
      </div>
      <MobileMenu />
    </header>
  );
}

// React-controlled Valorant region picker. Now built on the shared Dropdown
// component (same one HeaderUserMenu uses). Close-on-route-change and
// outside-click behavior live there.
const VALORANT_REGIONS = [
  { slug: 'na',    label: 'North America' },
  { slug: 'eu',    label: 'Europe' },
  { slug: 'apac',  label: 'Asia Pacific' },
  { slug: 'latam', label: 'Latin America' },
  { slug: 'br',    label: 'Brazil' },
];

function ValorantCategoryDropdown() {
  return (
    <Dropdown
      align="left"
      toggle={
        <>
          <img src="/img/icons/header_cat01.svg" alt="icon" />
          Valorant
        </>
      }
      items={VALORANT_REGIONS.map((r) => ({
        kind: 'link' as const,
        href: `/shop/valorant?region=${r.slug}`,
        label: r.label,
        key: r.slug,
      }))}
    />
  );
}

function MobileMenu() {
  const { mobileMenuOpen, closeMobileMenu, openAuthModal } = useUiStore();
  const authStatus = useAuthStore((s) => s.status);
  const logout = useAuthStore((s) => s.logout);

  const handleLogout = async () => {
    closeMobileMenu();
    await logout();
    window.location.assign('/');
  };

  return (
    <>
      <div className={`tgmobile__menu${mobileMenuOpen ? ' menu-open' : ''}`}>
        <nav className="tgmobile__menu-box">
          <div className="close-btn" onClick={closeMobileMenu}>
            <i className="tg-flaticon-close-1"></i>
          </div>
          <div className="nav-logo">
            <Link href="/" onClick={closeMobileMenu}>
              <img src="/img/logo/logo.svg" alt="Logo" />
            </Link>
          </div>
          <div className="tgmobile__menu-outer">
            <ul className="navigation">
              <li><Link href="/shop/valorant" onClick={closeMobileMenu}>Valorant</Link></li>
              <li><Link href="/shop/fortnite" onClick={closeMobileMenu}>Fortnite</Link></li>
              <li><Link href="/shop/legends" onClick={closeMobileMenu}>League of Legends</Link></li>
              {authStatus === 'authed' && (
                <>
                  <li><Link href="/account" onClick={closeMobileMenu}>Dashboard</Link></li>
                  <li><Link href="/account/orders" onClick={closeMobileMenu}>Orders</Link></li>
                  <li><Link href="/account/wishlist" onClick={closeMobileMenu}>Wishlist</Link></li>
                  <li><Link href="/account/profile" onClick={closeMobileMenu}>Profile</Link></li>
                </>
              )}
              <li><Link href="/support" onClick={closeMobileMenu}>Support</Link></li>
              {authStatus === 'authed' ? (
                <li>
                  <button onClick={handleLogout} className="tg-btn" style={{ marginTop: 12 }}>Logout</button>
                </li>
              ) : authStatus === 'guest' ? (
                <li>
                  <button onClick={() => { openAuthModal('login'); closeMobileMenu(); }} className="tg-btn" style={{ marginTop: 12 }}>Sign In</button>
                </li>
              ) : null}
            </ul>
          </div>
          <div className="social-links">
            <ul className="list-wrap">
              <li><a href="https://discord.com/" target="_blank" rel="noreferrer"><IconDiscord /></a></li>
              <li><a href="https://web.telegram.org/" target="_blank" rel="noreferrer"><IconTelegram /></a></li>
            </ul>
          </div>
        </nav>
      </div>
      {mobileMenuOpen && <div className="tgmobile__menu-backdrop" onClick={closeMobileMenu}></div>}
    </>
  );
}
