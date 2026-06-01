import { readToken } from '@/lib/auth/cookies';
import { getOrders } from '@/lib/api/endpoints';
import type { Order } from '@/lib/api/types';
import { OrdersPane } from '@/components/account/dashboard/tabs/OrdersPane';

export default async function OrdersPage() {
  const token = await readToken();

  let orders: Order[] = [];
  let total = 0;
  if (token) {
    try {
      const res = await getOrders(token, 1);
      orders = res.data;
      total = res.meta.total;
    } catch {
      // API unreachable — render empty state
    }
  }

  return <OrdersPane initialOrders={orders} totalCount={total} />;
}
