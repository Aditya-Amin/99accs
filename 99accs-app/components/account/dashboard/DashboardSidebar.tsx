'use client';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import {
  IconDashboard,
  IconOrders,
  IconAccountSupport,
  IconAccountDetails,
  IconTransactions,
  IconLogout,
} from '@/components/icons';

const NAV_ITEMS = [
  { href: '/account', exact: true, label: 'Dashboard', Icon: IconDashboard },
  { href: '/account/orders', label: 'Orders', Icon: IconOrders },
  { href: '/account/support', label: 'Support', Icon: IconAccountSupport },
  { href: '/account/details', label: 'Account Details', Icon: IconAccountDetails },
  { href: '/account/transactions', label: 'Transaction history', Icon: IconTransactions },
];

export function DashboardSidebar() {
  const pathname = usePathname();

  return (
    <div className="account__dashboard-sidebar">
      <ul className="list-wrap">
        {NAV_ITEMS.map(({ href, exact, label, Icon }) => {
          const isActive = exact ? pathname === href : pathname.startsWith(href);
          return (
            <li key={href}>
              <Link href={href} className={isActive ? 'active' : ''}>
                <Icon />
                {label}
              </Link>
            </li>
          );
        })}
        <li>
          <a href="/">
            <IconLogout />
            Logout
          </a>
        </li>
      </ul>
    </div>
  );
}
