'use client';
import Link from 'next/link';
import { useAuthStore } from '@/lib/store/authStore';

export default function AccountSidebar() {
  const logout = useAuthStore((s) => s.logout);

  const handleLogout = async () => {
    // Real logout: /api/auth/logout revokes the Sanctum token in the backend
    // DB AND clears every auth cookie (token + user snapshot). The old
    // /api/mock/logout left the real server-side token alive.
    await logout();
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
