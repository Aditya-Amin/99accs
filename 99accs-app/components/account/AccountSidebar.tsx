'use client';
import Link from 'next/link';

export default function AccountSidebar() {
  const handleLogout = async () => {
    await fetch('/api/mock/logout', { method: 'POST' });
    window.location.href = '/';
  };

  return (
    <div className="account__sidebar">
      <ul className="account__sidebar-menu list-wrap">
        <li><Link href="/account">Dashboard</Link></li>
        <li><Link href="/account/orders">Orders</Link></li>
        <li><Link href="/account/wishlist">Wishlist</Link></li>
        <li><Link href="/account/profile">Profile</Link></li>
        <li>
          <button onClick={handleLogout} style={{ background: 'none', border: 'none', color: 'inherit', cursor: 'pointer', padding: 0 }}>
            Logout
          </button>
        </li>
      </ul>
    </div>
  );
}
