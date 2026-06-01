'use client';
import { useAuthStore } from '@/lib/store/authStore';
import { Dropdown, type DropdownItem } from '@/components/ui/Dropdown';
import {
  IconUser,
  IconDashboard,
  IconOrders,
  IconWishlist,
  IconAccountDetails,
  IconTransactions,
  IconAccountSupport,
  IconLogout,
} from '@/components/icons';

// Renders the header's "logged-in user" pill + dropdown. Identical UI/UX to
// the Valorant region picker (shares the Dropdown component).

const ACCOUNT_LINKS: { href: string; label: string; Icon: typeof IconDashboard }[] = [
  { href: '/account', label: 'Dashboard', Icon: IconDashboard },
  { href: '/account/orders', label: 'Orders', Icon: IconOrders },
  { href: '/account/wishlist', label: 'Wishlist', Icon: IconWishlist },
  { href: '/account/profile', label: 'Profile', Icon: IconAccountDetails },
  { href: '/account/transactions', label: 'Transactions', Icon: IconTransactions },
  { href: '/account/support', label: 'Support', Icon: IconAccountSupport },
];

export default function HeaderUserMenu() {
  const user = useAuthStore((s) => s.user);
  const logout = useAuthStore((s) => s.logout);

  // Prefer first name, then the email local-part, then a generic label.
  // Use `||` (not `??`) so an empty/whitespace name from a legacy import
  // (full_name = "") falls through to the email instead of rendering blank.
  const label =
    user?.name?.trim().split(' ')[0] ||
    user?.email?.split('@')[0] ||
    'Account';

  const handleLogout = async () => {
    await logout();
    // Hard-navigate so the proxy + server layouts pick up the cleared cookie.
    window.location.assign('/');
  };

  const items: DropdownItem[] = [
    ...ACCOUNT_LINKS.map(({ href, label, Icon }) => ({
      kind: 'link' as const,
      href,
      label,
      icon: <Icon />,
      key: href,
    })),
    {
      kind: 'button' as const,
      onClick: handleLogout,
      icon: <IconLogout />,
      label: 'Logout',
      key: 'logout',
    },
  ];

  return (
    <Dropdown
      align="right"
      // Marker class lets globals.css scope the bordered toggle look + the
      // .tg-btn-style hover to ONLY this dropdown, not the Valorant one.
      toggleClassName="dropdown-toggle children header-user-toggle"
      // Opaque dark surface — translucent default is fine for the Valorant
      // category bar (sits over hero imagery) but reads poorly over the
      // account dashboard's mixed-color content.
      toggle={
        <>
          <IconUser />
          {label}
        </>
      }
      items={items}
    />
  );
}
