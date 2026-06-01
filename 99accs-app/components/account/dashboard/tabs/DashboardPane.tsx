'use client';
import Link from 'next/link';
import {
  IconOrders,
  IconTransactions,
  IconAccountSupport,
  IconShieldCheck,
  IconUser,
  IconCart,
  IconWishlist,
  IconAccountDetails,
} from '@/components/icons';
import type { AccountDashboard, Order } from '@/lib/api/types';
import type { SessionUser } from '@/lib/auth/cookies';

// ── Helpers ───────────────────────────────────────────────────────────────────
const STATUS_STYLES: Record<string, { color: string; background: string; border: string }> = {
  completed:  { color: '#00fc70', background: 'rgba(0,252,112,0.1)',  border: '1px solid #00fc70' },
  processing: { color: '#ffff00', background: 'rgba(255,255,0,0.1)',  border: '1px solid #ffff00' },
  pending:    { color: '#ffff00', background: 'rgba(255,255,0,0.1)',  border: '1px solid #ffff00' },
  cancelled:  { color: '#ff4639', background: 'rgba(255,70,57,0.1)',  border: '1px solid #ff4639' },
};

const STATUS_LABEL: Record<Order['status'], string> = {
  completed:  'Completed',
  processing: 'In Progress',
  pending:    'Pending',
  cancelled:  'Cancelled',
};

function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
}

function formatCurrency(n: number) {
  return `$${n.toFixed(2)}`;
}

// ── Component ─────────────────────────────────────────────────────────────────
interface Props {
  dashboard: AccountDashboard | null;
  user: SessionUser | null;
}

const QUICK_LINKS = [
  { href: '/account/orders',       label: 'My Orders',    Icon: IconOrders,         desc: 'View & track orders'  },
  { href: '/account/transactions', label: 'Transactions', Icon: IconTransactions,   desc: 'Payment history'       },
  { href: '/account/support',      label: 'Get Support',  Icon: IconAccountSupport, desc: 'Open a ticket'        },
  { href: '/account/details',      label: 'Edit Profile', Icon: IconAccountDetails, desc: 'Update your info'     },
];

export function DashboardPane({ dashboard, user }: Props) {
  const displayName = user?.first_name || user?.name || 'User';
  const memberSince = user?.created_at ? formatDate(user.created_at) : '';

  const activeCount = dashboard?.recent_orders.filter(
    (o) => o.status === 'pending' || o.status === 'processing',
  ).length ?? 0;

  const STATS = [
    { label: 'Total Orders',  value: String(dashboard?.order_count    ?? '—'), sub: 'All time',    Icon: IconOrders,       href: '/account/orders',       accent: '#00d084' },
    { label: 'Total Spent',   value: dashboard ? formatCurrency(dashboard.total_spent) : '—',       sub: 'Completed orders', Icon: IconTransactions, href: '/account/transactions', accent: '#7c3aed' },
    { label: 'Wishlist',      value: String(dashboard?.wishlist_count ?? '—'), sub: 'Saved items', Icon: IconWishlist,     href: '/account/wishlist',     accent: '#f59e0b' },
    { label: 'Active Orders', value: String(activeCount),                      sub: 'In progress', Icon: IconCart,         href: '/account/orders',       accent: '#ef4444' },
  ];

  const recentOrders = dashboard?.recent_orders ?? [];

  return (
    <div id="tab1" className="account-pane account__dashboard-info active">

      {/* ── Welcome banner ──────────────────────────────────────────────── */}
      <div style={{
        background: 'linear-gradient(135deg, rgba(0,208,132,0.12) 0%, rgba(124,58,237,0.10) 100%)',
        border: '1px solid rgba(0,208,132,0.18)',
        borderRadius: '12px',
        padding: '24px 28px',
        marginBottom: '28px',
        display: 'flex',
        alignItems: 'center',
        gap: '18px',
      }}>
        <div style={{
          width: 56, height: 56, borderRadius: '50%',
          background: 'rgba(0,208,132,0.15)',
          border: '2px solid rgba(0,208,132,0.4)',
          display: 'flex', alignItems: 'center', justifyContent: 'center',
          flexShrink: 0,
        }}>
          <IconUser style={{ color: '#00d084', width: 26, height: 26 }} />
        </div>
        <div>
          <h4 style={{ color: '#fff', margin: 0, fontSize: '1.15rem', fontWeight: 700 }}>
            Welcome back, <span style={{ color: '#00d084' }}>{displayName}</span>
          </h4>
          <p style={{ color: 'rgba(255,255,255,0.5)', margin: '4px 0 0', fontSize: '0.875rem' }}>
            Manage your orders, track purchases, and view your account details below.
          </p>
        </div>
        {memberSince && (
          <div style={{ marginLeft: 'auto', textAlign: 'right', flexShrink: 0 }}>
            <span style={{ color: 'rgba(255,255,255,0.35)', fontSize: '0.78rem' }}>Member since</span>
            <div style={{ color: '#fff', fontWeight: 600, fontSize: '0.9rem' }}>{memberSince}</div>
          </div>
        )}
      </div>

      {/* ── Stat cards ──────────────────────────────────────────────────── */}
      <div className="row g-3" style={{ marginBottom: '28px' }}>
        {STATS.map(({ label, value, sub, Icon, href, accent }) => (
          <div key={label} className="col-6 col-xl-3">
            <Link href={href} style={{ textDecoration: 'none', display: 'block' }}>
              <div
                style={{
                  background: 'rgba(255,255,255,0.04)',
                  border: `1px solid ${accent}28`,
                  borderRadius: '12px',
                  padding: '20px',
                  transition: 'border-color 0.2s, transform 0.2s',
                  cursor: 'pointer',
                }}
                onMouseEnter={(e) => {
                  (e.currentTarget as HTMLDivElement).style.borderColor = `${accent}70`;
                  (e.currentTarget as HTMLDivElement).style.transform = 'translateY(-2px)';
                }}
                onMouseLeave={(e) => {
                  (e.currentTarget as HTMLDivElement).style.borderColor = `${accent}28`;
                  (e.currentTarget as HTMLDivElement).style.transform = 'translateY(0)';
                }}
              >
                <div style={{
                  width: 44, height: 44, borderRadius: '10px',
                  background: `${accent}18`,
                  display: 'flex', alignItems: 'center', justifyContent: 'center',
                  marginBottom: '14px',
                }}>
                  <Icon style={{ color: accent, width: 22, height: 22 }} />
                </div>
                <div style={{ color: '#fff', fontSize: '1.5rem', fontWeight: 700, lineHeight: 1 }}>
                  {value}
                </div>
                <div style={{ color: 'rgba(255,255,255,0.75)', fontSize: '0.85rem', fontWeight: 600, marginTop: 6 }}>
                  {label}
                </div>
                <div style={{ color: 'rgba(255,255,255,0.38)', fontSize: '0.78rem', marginTop: 2 }}>
                  {sub}
                </div>
              </div>
            </Link>
          </div>
        ))}
      </div>

      {/* ── Bottom grid: recent orders + quick actions ───────────────────── */}
      <div className="row g-3">

        {/* Recent orders */}
        <div className="col-xl-7">
          <div style={{
            background: 'rgba(255,255,255,0.04)',
            border: '1px solid rgba(255,255,255,0.07)',
            borderRadius: '12px',
            padding: '22px',
            height: '100%',
          }}>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '18px' }}>
              <h5 style={{ color: '#fff', margin: 0, fontSize: '0.95rem', fontWeight: 700 }}>Recent Orders</h5>
              <Link href="/account/orders" style={{ color: '#00d084', fontSize: '0.8rem', textDecoration: 'none', fontWeight: 600 }}>
                View all →
              </Link>
            </div>

            {recentOrders.length === 0 ? (
              <div style={{ color: 'rgba(255,255,255,0.3)', fontSize: '0.875rem', textAlign: 'center', padding: '24px 0' }}>
                No orders yet.
              </div>
            ) : (
              <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
                {recentOrders.map((order) => {
                  const firstItem = order.items?.[0];
                  const label = firstItem?.product_title ?? `Order ${order.number ?? `#${order.id}`}`;
                  const img   = firstItem?.product_image ?? null;
                  const style = STATUS_STYLES[order.status] ?? STATUS_STYLES.pending;

                  return (
                    <div key={order.id} style={{
                      display: 'flex', alignItems: 'center', gap: '14px',
                      padding: '12px 14px',
                      background: 'rgba(255,255,255,0.03)',
                      borderRadius: '8px',
                      border: '1px solid rgba(255,255,255,0.05)',
                    }}>
                      {img ? (
                        <img
                          src={img}
                          alt={label}
                          style={{ width: 44, height: 44, borderRadius: '8px', objectFit: 'cover', flexShrink: 0 }}
                        />
                      ) : (
                        <div style={{
                          width: 44, height: 44, borderRadius: '8px', flexShrink: 0,
                          background: 'rgba(255,255,255,0.06)',
                          display: 'flex', alignItems: 'center', justifyContent: 'center',
                        }}>
                          <IconCart style={{ color: 'rgba(255,255,255,0.25)', width: 18, height: 18 }} />
                        </div>
                      )}
                      <div style={{ flex: 1, minWidth: 0 }}>
                        <div style={{
                          color: '#fff', fontWeight: 600, fontSize: '0.85rem',
                          whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis',
                        }}>
                          {label}
                        </div>
                        <div style={{ color: 'rgba(255,255,255,0.4)', fontSize: '0.78rem', marginTop: 2 }}>
                          {order.number ?? `#${order.id}`}
                        </div>
                      </div>
                      <div style={{ textAlign: 'right', flexShrink: 0 }}>
                        <div style={{ color: '#fff', fontWeight: 700, fontSize: '0.9rem' }}>
                          {formatCurrency(order.total)}
                        </div>
                        <span style={{
                          ...style,
                          fontSize: '0.72rem', marginTop: 4, display: 'inline-block',
                          padding: '2px 8px', borderRadius: '4px', fontWeight: 600,
                        }}>
                          {STATUS_LABEL[order.status]}
                        </span>
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </div>
        </div>

        {/* Quick actions */}
        <div className="col-xl-5">
          <div style={{
            background: 'rgba(255,255,255,0.04)',
            border: '1px solid rgba(255,255,255,0.07)',
            borderRadius: '12px',
            padding: '22px',
            height: '100%',
          }}>
            <h5 style={{ color: '#fff', margin: '0 0 18px', fontSize: '0.95rem', fontWeight: 700 }}>Quick Actions</h5>
            <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
              {QUICK_LINKS.map(({ href, label, Icon, desc }) => (
                <Link key={href} href={href} style={{ textDecoration: 'none' }}>
                  <div
                    style={{
                      display: 'flex', alignItems: 'center', gap: '14px',
                      padding: '13px 16px',
                      background: 'rgba(255,255,255,0.03)',
                      borderRadius: '8px',
                      border: '1px solid rgba(255,255,255,0.06)',
                      transition: 'background 0.15s, border-color 0.15s',
                    }}
                    onMouseEnter={(e) => {
                      (e.currentTarget as HTMLDivElement).style.background = 'rgba(0,208,132,0.07)';
                      (e.currentTarget as HTMLDivElement).style.borderColor = 'rgba(0,208,132,0.22)';
                    }}
                    onMouseLeave={(e) => {
                      (e.currentTarget as HTMLDivElement).style.background = 'rgba(255,255,255,0.03)';
                      (e.currentTarget as HTMLDivElement).style.borderColor = 'rgba(255,255,255,0.06)';
                    }}
                  >
                    <div style={{
                      width: 38, height: 38, borderRadius: '8px',
                      background: 'rgba(0,208,132,0.12)',
                      display: 'flex', alignItems: 'center', justifyContent: 'center',
                      flexShrink: 0,
                    }}>
                      <Icon style={{ color: '#00d084', width: 18, height: 18 }} />
                    </div>
                    <div>
                      <div style={{ color: '#fff', fontWeight: 600, fontSize: '0.875rem' }}>{label}</div>
                      <div style={{ color: 'rgba(255,255,255,0.4)', fontSize: '0.78rem', marginTop: 1 }}>{desc}</div>
                    </div>
                    <div style={{ marginLeft: 'auto', color: 'rgba(255,255,255,0.25)', fontSize: '1rem' }}>›</div>
                  </div>
                </Link>
              ))}
            </div>

            {/* Account security badge */}
            <div style={{
              marginTop: '16px',
              padding: '12px 16px',
              background: 'rgba(0,208,132,0.07)',
              borderRadius: '8px',
              border: '1px solid rgba(0,208,132,0.15)',
              display: 'flex', alignItems: 'center', gap: '10px',
            }}>
              <IconShieldCheck style={{ color: '#00d084', width: 20, height: 20, flexShrink: 0 }} />
              <div>
                <div style={{ color: '#00d084', fontWeight: 600, fontSize: '0.82rem' }}>Account Verified</div>
                <div style={{ color: 'rgba(255,255,255,0.4)', fontSize: '0.76rem', marginTop: 1 }}>
                  {user?.email ?? 'Your email is confirmed'}
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  );
}
