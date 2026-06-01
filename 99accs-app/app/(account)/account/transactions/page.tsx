import { readToken } from '@/lib/auth/cookies';
import { getOrders } from '@/lib/api/endpoints';
import type { Order } from '@/lib/api/types';
import { TransactionHistoryPane } from '@/components/account/dashboard/tabs/TransactionHistoryPane';

export default async function TransactionsPage() {
  const token = await readToken();

  let orders: Order[] = [];
  if (token) {
    try {
      // Fetch up to 50 orders for the transaction history view
      const res = await getOrders(token, 1);
      orders = res.data;
    } catch {
      // API unreachable — render empty state
    }
  }

  return <TransactionHistoryPane initialOrders={orders} />;
}
