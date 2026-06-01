'use client';
import { useState } from 'react';
import Link from 'next/link';
import type { Order } from '@/lib/api/types';

function SearchIcon() {
  return (
    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M17.6929 16.7201L13.39 12.4181C14.6372 10.9208 15.2591 9.00031 15.1263 7.05619C14.9936 5.11206 14.1164 3.29395 12.6774 1.98006C11.2383 0.66618 9.34806 -0.0423181 7.39991 0.00195681C5.45177 0.0462317 3.59568 0.839871 2.21778 2.21778C0.839871 3.59568 0.0462317 5.45177 0.00195681 7.39991C-0.0423181 9.34806 0.66618 11.2383 1.98006 12.6774C3.29395 14.1164 5.11206 14.9936 7.05619 15.1263C9.00031 15.2591 10.9208 14.6372 12.4181 13.39L16.7201 17.6929C16.784 17.7568 16.8598 17.8074 16.9433 17.842C17.0267 17.8766 17.1162 17.8944 17.2065 17.8944C17.2968 17.8944 17.3863 17.8766 17.4697 17.842C17.5532 17.8074 17.629 17.7568 17.6929 17.6929C17.7568 17.629 17.8074 17.5532 17.842 17.4697C17.8766 17.3863 17.8944 17.2968 17.8944 17.2065C17.8944 17.1162 17.8766 17.0267 17.842 16.9433C17.8074 16.8598 17.7568 16.784 17.6929 16.7201ZM1.39399 7.58149C1.39399 6.35772 1.75689 5.16143 2.43678 4.1439C3.11667 3.12637 4.08302 2.33331 5.21364 1.86499C6.34426 1.39667 7.58836 1.27414 8.78862 1.51289C9.98887 1.75163 11.0914 2.34093 11.9567 3.20627C12.8221 4.07161 13.4114 5.17412 13.6501 6.37437C13.8889 7.57463 13.7663 8.81873 13.298 9.94935C12.8297 11.08 12.0366 12.0463 11.0191 12.7262C10.0016 13.4061 8.80527 13.769 7.58149 13.769C5.94103 13.7672 4.36827 13.1147 3.20828 11.9547C2.04829 10.7947 1.39581 9.22196 1.39399 7.58149Z" fill="currentColor" />
    </svg>
  );
}

function SortIcon() {
  return (
    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M6.67429 11.2016C6.73821 11.2655 6.78892 11.3413 6.82352 11.4248C6.85811 11.5082 6.87592 11.5977 6.87592 11.688C6.87592 11.7784 6.85811 11.8679 6.82352 11.9513C6.78892 12.0348 6.73821 12.1106 6.67429 12.1744L3.92429 14.9244C3.86044 14.9884 3.78461 15.0391 3.70115 15.0737C3.61769 15.1083 3.52823 15.1261 3.43788 15.1261C3.34753 15.1261 3.25807 15.1083 3.17461 15.0737C3.09115 15.0391 3.01533 14.9884 2.95148 14.9244L0.201476 12.1744C0.1376 12.1106 0.0869313 12.0347 0.0523619 11.9513C0.0177926 11.8678 0 11.7784 0 11.688C0 11.5977 0.0177926 11.5083 0.0523619 11.4248C0.0869313 11.3413 0.1376 11.2655 0.201476 11.2016C0.330479 11.0726 0.505445 11.0002 0.687882 11.0002C0.778216 11.0002 0.867666 11.018 0.951124 11.0525C1.03458 11.0871 1.11041 11.1378 1.17429 11.2016L2.75038 12.7786V0.688041C2.75038 0.505705 2.82282 0.330836 2.95175 0.201905C3.08068 0.0729739 3.25555 0.000540912 3.43788 0.000540912C3.62022 0.000540912 3.79509 0.0729739 3.92402 0.201905C4.05295 0.330836 4.12538 0.505705 4.12538 0.688041V12.7786L5.70148 11.2016C5.76533 11.1377 5.84115 11.087 5.92461 11.0524C6.00807 11.0178 6.09753 11 6.18788 11C6.27823 11 6.36769 11.0178 6.45115 11.0524C6.53462 11.087 6.61044 11.1377 6.67429 11.2016ZM14.9243 2.95163L12.1743 0.201635C12.1104 0.137713 12.0346 0.0870041 11.9512 0.0524062C11.8677 0.0178082 11.7782 0 11.6879 0C11.5975 0 11.5081 0.0178082 11.4246 0.0524062C11.3411 0.0870041 11.2653 0.137713 11.2015 0.201635L8.45148 2.95163C8.32247 3.08064 8.25 3.2556 8.25 3.43804C8.25 3.62048 8.32247 3.79544 8.45148 3.92445C8.58048 4.05345 8.75544 4.12592 8.93788 4.12592C9.12032 4.12592 9.29529 4.05345 9.42429 3.92445L11.0004 2.34749V14.438C11.0004 14.6204 11.0728 14.7952 11.2017 14.9242C11.3307 15.0531 11.5055 15.1255 11.6879 15.1255C11.8702 15.1255 12.0451 15.0531 12.174 14.9242C12.303 14.7952 12.3754 14.6204 12.3754 14.438V2.34749L13.9515 3.92445C14.0805 4.05345 14.2554 4.12592 14.4379 4.12592C14.6203 4.12592 14.7953 4.05345 14.9243 3.92445C15.0533 3.79544 15.1258 3.62048 15.1258 3.43804C15.1258 3.2556 15.0533 3.08064 14.9243 2.95163Z" fill="currentColor" />
    </svg>
  );
}

function PlusCircleIcon() {
  return (
    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M8.9375 0C7.16983 0 5.44186 0.524175 3.9721 1.50624C2.50233 2.48831 1.35679 3.88415 0.680331 5.51727C0.00387248 7.15038 -0.17312 8.94742 0.171736 10.6811C0.516591 12.4148 1.36781 14.0073 2.61774 15.2573C3.86767 16.5072 5.46018 17.3584 7.19388 17.7033C8.92759 18.0481 10.7246 17.8711 12.3577 17.1947C13.9909 16.5182 15.3867 15.3727 16.3688 13.9029C17.3508 12.4331 17.875 10.7052 17.875 8.9375C17.872 6.56804 16.9295 4.29646 15.254 2.621C13.5785 0.945532 11.307 0.00295629 8.9375 0ZM12.375 9.625H9.625V12.375C9.625 12.5573 9.55257 12.7322 9.42364 12.8611C9.29471 12.9901 9.11984 13.0625 8.9375 13.0625C8.75517 13.0625 8.5803 12.9901 8.45137 12.8611C8.32244 12.7322 8.25 12.5573 8.25 12.375V9.625H5.5C5.31767 9.625 5.1428 9.55257 5.01387 9.42364C4.88494 9.29471 4.8125 9.11984 4.8125 8.9375C4.8125 8.75516 4.88494 8.5803 5.01387 8.45136C5.1428 8.32243 5.31767 8.25 5.5 8.25H8.25V5.5C8.25 5.31766 8.32244 5.1428 8.45137 5.01386C8.5803 4.88493 8.75517 4.8125 8.9375 4.8125C9.11984 4.8125 9.29471 4.88493 9.42364 5.01386C9.55257 5.1428 9.625 5.31766 9.625 5.5V8.25H12.375C12.5573 8.25 12.7322 8.32243 12.8611 8.45136C12.9901 8.5803 13.0625 8.75516 13.0625 8.9375C13.0625 9.11984 12.9901 9.29471 12.8611 9.42364C12.7322 9.55257 12.5573 9.625 12.375 9.625Z" fill="currentColor" />
    </svg>
  );
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function fmtDate(iso: string) {
  return new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function fmtPrice(n: number) {
  return `$${n.toFixed(2)}`;
}

type StatusInfo = { label: string; cssClass: string };

function statusInfo(s: Order['status']): StatusInfo {
  switch (s) {
    case 'completed':  return { label: 'Completed',   cssClass: 'open' };
    case 'cancelled':  return { label: 'Cancelled',   cssClass: 'closed' };
    case 'processing': return { label: 'In Progress', cssClass: 'country__code ap' };
    default:           return { label: 'Pending',     cssClass: 'country__code ap' };
  }
}

const TAB_STATUSES: Record<string, Order['status'][]> = {
  tableTab1: ['pending', 'processing', 'completed', 'cancelled'],
  tableTab2: ['completed'],
  tableTab3: ['pending', 'processing'],
  tableTab4: ['cancelled'],
};

// ── Sub-components ────────────────────────────────────────────────────────────

function OrdersTable({ rows, search }: { rows: Order[]; search: string }) {
  const q = search.toLowerCase();
  const visible = q
    ? rows.filter((o) =>
        (o.number ?? String(o.id)).toLowerCase().includes(q) ||
        o.items.some((i) => i.product_title.toLowerCase().includes(q))
      )
    : rows;

  if (visible.length === 0) {
    return <p style={{ color: 'rgba(255,255,255,0.4)', padding: '24px', textAlign: 'center' }}>No orders found.</p>;
  }

  return (
    <table className="support__table-inner">
      <thead>
        <tr>
          <th>Order ID</th>
          <th>Product</th>
          <th>Payment</th>
          <th>Amount</th>
          <th>Status</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        {visible.map((order) => {
          const firstItem = order.items?.[0];
          const { label, cssClass } = statusInfo(order.status);
          return (
            <tr key={order.id}>
              <td className="product__id">{order.number ?? `#${order.id}`}</td>
              <td className="product__info">
                {firstItem?.product_image && (
                  <div className="thumb"><img src={firstItem.product_image} alt="" /></div>
                )}
                <p>{firstItem?.product_title ?? `Order #${order.id}`}</p>
              </td>
              <td className="product__payment"><span>{order.payment_method ?? '—'}</span></td>
              <td className="product__price"><span>{fmtPrice(order.total)}</span></td>
              <td className="product__status"><span className={cssClass}>{label}</span></td>
              <td className="product__date"><span>{fmtDate(order.created_at)}</span></td>
            </tr>
          );
        })}
      </tbody>
    </table>
  );
}

// ── Main component ────────────────────────────────────────────────────────────

interface Props {
  initialOrders: Order[];
  totalCount: number;
}

export function OrdersPane({ initialOrders, totalCount: _totalCount }: Props) {
  const [subTab, setSubTab] = useState('tableTab1');
  const [search, setSearch]   = useState('');

  const filtered = subTab === 'tableTab1'
    ? initialOrders
    : initialOrders.filter((o) => TAB_STATUSES[subTab]?.includes(o.status));

  return (
    <div className="support__table-wrap-two account-pane active">
      <div className="support__table-top">
        <form action="#" className="support__table-form" onSubmit={(e) => e.preventDefault()}>
          <div className="form-grp">
            <label htmlFor="orders-search"><SearchIcon /></label>
            <input
              type="text"
              id="orders-search"
              placeholder="Search..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
            />
          </div>
          <button type="button"><SortIcon /></button>
        </form>
        <Link href="/support/contact" className="tg-btn">
          <PlusCircleIcon />
          Create New ticket
        </Link>
      </div>
      <div className="support__table-nav-two">
        {[
          { id: 'tableTab1', label: 'All' },
          { id: 'tableTab2', label: 'Completed' },
          { id: 'tableTab3', label: 'In Progress' },
          { id: 'tableTab4', label: 'Cancelled' },
        ].map((t) => (
          <button
            key={t.id}
            data-tab={t.id}
            className={subTab === t.id ? 'active' : ''}
            onClick={() => setSubTab(t.id)}
          >
            {t.label}
          </button>
        ))}
      </div>
      <div className="support__table-tab">
        <div className="table-pane active" style={{ display: 'block' }}>
          <OrdersTable rows={filtered} search={search} />
        </div>
      </div>
    </div>
  );
}
