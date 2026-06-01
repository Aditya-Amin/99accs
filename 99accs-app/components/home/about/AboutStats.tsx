// --- Single animated counter item ---
interface StatCounterProps {
  value: string;
  label: string;
}
export function StatCounter({ value, label }: StatCounterProps) {
  return (
    <div className="counter__item">
      <h2 className="counter-number">{value}</h2>
      <p>{label}</p>
    </div>
  );
}

// --- Both stats counters side by side ---
interface AboutStatsProps {
  happy_customers: number;
  accounts_sold: number;
}
export function AboutStats({ happy_customers, accounts_sold }: AboutStatsProps) {
  return (
    <div className="about__content-bottom">
      <StatCounter value={(happy_customers ?? 0).toLocaleString()} label="Happy Clients" />
      <StatCounter value={(accounts_sold ?? 0).toLocaleString()} label="Sold Accounts" />
    </div>
  );
}
