import { AccountBreadcrumb } from '@/components/account/dashboard/AccountBreadcrumb';
import { DashboardSidebar } from '@/components/account/dashboard/DashboardSidebar';

export default function AccountLayout({ children }: { children: React.ReactNode }) {
  return (
    <main className="main-area fix">
      <AccountBreadcrumb />
      <section className="account__dashboard-area section-pb-130">
        <div className="container">
          <div className="row">
            <div className="col-xl-3">
              <DashboardSidebar />
            </div>
            <div className="col-xl-9">
              <div className="account__dashboard-details">
                {children}
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>
  );
}
