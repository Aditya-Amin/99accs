'use client';
import { useState } from 'react';
import { FilterDropdown } from '@/components/ui/FilterDropdown';

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

const FILTER_OPTIONS = [
  { id: 'filter-completed', label: 'Completed' },
  { id: 'filter-cancelled', label: 'Canceled' },
  { id: 'filter-in-progress', label: 'In Progress' },
];

interface TxRow {
  id: string;
  orderId: string;
  amount: string;
  description: string;
  status: string;
  statusClass: string;
  date: string;
}

const TRANSACTIONS: TxRow[] = [
  { id: '#15941', orderId: '#15941', amount: '$220', description: 'Payment for order #15941', status: 'Completed', statusClass: 'open', date: 'Nov 14, 2023' },
  { id: '#15942', orderId: '#15942', amount: '$300', description: 'Payment for order #15942', status: 'In Progress', statusClass: 'country__code ap', date: 'Nov 18, 2023' },
  { id: '#15943', orderId: '#15943', amount: '$120', description: 'Payment for order #15943', status: 'Completed', statusClass: 'open', date: 'Dec 2, 2023' },
  { id: '#15944', orderId: '#15944', amount: '$450', description: 'Payment for order #15944', status: 'Canceled', statusClass: 'closed', date: 'Dec 10, 2023' },
  { id: '#15945', orderId: '#15945', amount: '$20', description: 'Payment for order #15945', status: 'Completed', statusClass: 'open', date: 'Dec 20, 2023' },
  { id: '#15946', orderId: '#15946', amount: '$202', description: 'Payment for order #15946', status: 'Canceled', statusClass: 'closed', date: 'Jan 5, 2024' },
];

export function TransactionHistoryPane() {
  const [checkedFilters, setCheckedFilters] = useState<string[]>([]);

  const toggleFilter = (label: string) => {
    setCheckedFilters((prev) =>
      prev.includes(label) ? prev.filter((f) => f !== label) : [...prev, label]
    );
  };

  const displayedRows =
    checkedFilters.length === 0
      ? TRANSACTIONS
      : TRANSACTIONS.filter((r) => checkedFilters.includes(r.status));

  return (
    <div className="support__table-wrap-two account-pane active">
      <div className="support__table-top">
        <form action="#" className="support__table-form">
          <div className="form-grp">
            <label htmlFor="tx-search"><SearchIcon /></label>
            <input type="text" id="tx-search" placeholder="Search..." />
          </div>
          <FilterDropdown
            options={FILTER_OPTIONS}
            checked={checkedFilters}
            onChange={toggleFilter}
          />
        </form>
        <button type="button" className="tg-btn">
          <PrinterIcon />
          Show printable view
        </button>
      </div>
      <h2 className="balance-title">
        Your current balance is: <span>$0.00</span>
      </h2>
      <div className="support__table-tab">
        <div className="table-pane active">
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
              {displayedRows.map((row, i) => (
                <tr key={i}>
                  <td className="product__id">{row.id}</td>
                  <td className="product__id">{row.orderId}</td>
                  <td className="product__price"><span>{row.amount}</span></td>
                  <td className="product__description"><p>{row.description}</p></td>
                  <td className="product__status">
                    <span className={row.statusClass}>{row.status}</span>
                  </td>
                  <td className="product__date"><span>{row.date}</span></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
