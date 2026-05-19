import Link from 'next/link';

export function DashboardPane() {
  return (
    <div id="tab1" className="account-pane account__dashboard-info active">
      <p>
        Hello <span>Kellyburn</span> (not <span>Kellyburn</span>?{' '}
        <Link href="/">Log out</Link>)
      </p>
      <p>
        From your account dashboard you can view your{' '}
        <a href="#!">recent orders</a>, manage your{' '}
        <a href="#!">shipping and billing addresses</a>, and edit your <br />
        <a href="#!">password and account details</a>.
      </p>
    </div>
  );
}
