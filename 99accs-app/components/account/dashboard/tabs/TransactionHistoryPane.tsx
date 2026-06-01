'use client';
import { useState } from 'react';
import { FilterDropdown } from '@/components/ui/FilterDropdown';
import type { Order } from '@/lib/api/types';

function SearchIcon() {
  return (
    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M17.6929 16.7201L13.39 12.4181C14.6372 10.9208 15.2591 9.00031 15.1263 7.05619C14.9936 5.11206 14.1164 3.29395 12.6774 1.98006C11.2383 0.66618 9.34806 -0.0423181 7.39991 0.00195681C5.45177 0.0462317 3.59568 0.839871 2.21778 2.21778C0.839871 3.59568 0.0462317 5.45177 0.00195681 7.39991C-0.0423181 9.34806 0.66618 11.2383 1.98006 12.6774C3.29395 14.1164 5.11206 14.9936 7.05619 15.1263C9.00031 15.2591 10.9208 14.6372 12.4181 13.39L16.7201 17.6929C16.784 17.7568 16.8598 17.8074 16.9433 17.842C17.0267 17.8766 17.1162 17.8944 17.2065 17.8944C17.2968 17.8944 17.3863 17.8766 17.4697 17.842C17.5532 17.8074 17.629 17.7568 17.6929 17.6929C17.7568 17.629 17.8074 17.5532 17.842 17.4697C17.8766 17.3863 17.8944 17.2968 17.8944 17.2065C17.8944 17.1162 17.8766 17.0267 17.842 16.9433C17.8074 16.8598 17.7568 16.784 17.6929 16.7201ZM1.39399 7.58149C1.39399 6.35772 1.75689 5.16143 2.43678 4.1439C3.11667 3.12637 4.08302 2.33331 5.21364 1.86499C6.34426 1.39667 7.58836 1.27414 8.78862 1.51289C9.98887 1.75163 11.0914 2.34093 11.9567 3.20627C12.8221 4.07161 13.4114 5.17412 13.6501 6.37437C13.8889 7.57463 13.7663 8.81873 13.298 9.94935C12.8297 11.08 12.0366 12.0463 11.0191 12.7262C10.0016 13.4061 8.80527 13.769 7.58149 13.769C5.94103 13.7672 4.36827 13.1147 3.20828 11.9547C2.04829 10.7947 1.39581 9.22196 1.39399 7.58149Z" fill="currentColor" />
    </svg>
  );
}

function PrinterIcon() {
  return (
    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M15.75 5.25H14.25V1.5C14.25 1.10218 14.092 0.720644 13.8107 0.43934C13.5294 0.158035 13.1478 0 12.75 0H5.25C4.85218 0 4.47064 0.158035 4.18934 0.43934C3.90804 0.720644 3.75 1.10218 3.75 1.5V5.25H2.25C1.65326 5.25 1.08097 5.48705 0.65901 5.90901C0.237053 6.33097 0 6.90326 0 7.5V12.75C0 13.3467 0.237053 13.919 0.65901 14.341C1.08097 14.7629 1.65326 15 2.25 15H3.75V16.5C3.75 16.8978 3.90804 17.2794 4.18934 17.5607C4.47064 17.842 4.85218 18 5.25 18H12.75C13.1478 18 13.5294 17.842 13.8107 17.5607C14.092 17.2794 14.25 16.8978 14.25 16.5V15H15.75C16.3467 15 16.919 14.7629 17.341 14.341C17.7629 13.919 18 13.3467 18 12.75V7.5C18 6.90326 17.7629 6.33097 17.341 5.90901C16.919 5.48705 16.3467 5.25 15.75 5.25ZM5.25 1.5H12.75V5.25H5.25V1.5ZM12.75 16.5H5.25V12H12.75V16.5ZM15.75 9.75C15.4516 9.75 15.1645 9.63147 14.9538 9.42081C14.7431 9.21016 14.625 8.92337 14.625 8.625C14.625 8.32663 14.7431 8.03984 14.9538 7.82919C15.1645 7.61853 15.4516 7.5 15.75 7.5C16.0484 7.5 16.3355 7.61853 16.5462 7.82919C16.7569 8.03984 16.875 8.32663 16.875 8.625C16.875 8.92337 16.7569 9.21016 16.5462 9.42081C16.3355 9.63147 16.0484 9.75 15.75 9.75Z" fill="currentColor" />
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

// Map order status → display label + CSS class (reuse account table colour scheme)
type TxStatus = 'Completed' | 'In Progress' | 'Pending' | 'Cancelled';

function txStatus(s: Order['status']): { label: TxStatus; cssClass: string } {
  switch (s) {
    case 'completed':  return { label: 'Completed',   cssClass: 'open' };
    case 'cancelled':  return { label: 'Cancelled',   cssClass: 'closed' };
    case 'processing': return { label: 'In Progress', cssClass: 'country__code ap' };
    default:           return { label: 'Pending',     cssClass: 'country__code ap' };
  }
}

const FILTER_OPTIONS = [
  { id: 'filter-completed',   label: 'Completed' },
  { id: 'filter-in-progress', label: 'In Progress' },
  { id: 'filter-pending',     label: 'Pending' },
  { id: 'filter-cancelled',   label: 'Cancelled' },
];

// ── Main component ────────────────────────────────────────────────────────────

interface Props {
  initialOrders: Order[];
}

export function TransactionHistoryPane({ initialOrders }: Props) {
  const [checkedFilters, setCheckedFilters] = useState<string[]>([]);
  const [search, setSearch] = useState('');

  const toggleFilter = (label: string) => {
    setCheckedFilters((prev) =>
      prev.includes(label) ? prev.filter((f) => f !== label) : [...prev, label]
    );
  };

  const rows = initialOrders
    .filter((o) => {
      if (checkedFilters.length === 0) return true;
      return checkedFilters.includes(txStatus(o.status).label);
    })
    .filter((o) => {
      if (!search) return true;
      const q = search.toLowerCase();
      return (
        (o.number ?? String(o.id)).toLowerCase().includes(q) ||
        o.items.some((i) => i.product_title.toLowerCase().includes(q))
      );
    });

  // Total of completed orders only = lifetime balance paid
  const totalPaid = initialOrders
    .filter((o) => o.status === 'completed')
    .reduce((acc, o) => acc + o.total, 0);

  return (
    <div className="support__table-wrap-two account-pane active">
      <div className="support__table-top">
        <form action="#" className="support__table-form" onSubmit={(e) => e.preventDefault()}>
          <div className="form-grp">
            <label htmlFor="tx-search"><SearchIcon /></label>
            <input
              type="text"
              id="tx-search"
              placeholder="Search..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
            />
          </div>
          <FilterDropdown
            options={FILTER_OPTIONS}
            checked={checkedFilters}
            onChange={toggleFilter}
          />
        </form>
        <button type="button" className="tg-btn" onClick={() => window.print()}>
          <PrinterIcon />
          Show printable view
        </button>
      </div>
      <h2 className="balance-title">
        Your total spend: <span>{fmtPrice(totalPaid)}</span>
      </h2>
      <div className="support__table-tab">
        <div className="table-pane active" style={{ display: 'block' }}>
          {rows.length === 0 ? (
            <p style={{ color: 'rgba(255,255,255,0.4)', padding: '24px', textAlign: 'center' }}>
              No transactions found.
            </p>
          ) : (
            <table className="support__table-inner">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Order ID</th>
                  <th>Amount</th>
                  <th>Description</th>
                  <th>Status</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                {rows.map((order) => {
                  const { label, cssClass } = txStatus(order.status);
                  const orderId = order.number ?? `#${order.id}`;
                  return (
                    <tr key={order.id}>
                      <td className="product__id">{orderId}</td>
                      <td className="product__id">{orderId}</td>
                      <td className="product__price"><span>{fmtPrice(order.total)}</span></td>
                      <td className="product__description">
                        <p>Payment for order {orderId}</p>
                      </td>
                      <td className="product__status">
                        <span className={cssClass}>{label}</span>
                      </td>
                      <td className="product__date"><span>{fmtDate(order.created_at)}</span></td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          )}
        </div>
      </div>
    </div>
  );
}
