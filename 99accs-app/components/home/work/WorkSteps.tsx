import type { WorkStep } from '@/lib/api/types';

// --- Section heading ---
export function WorkSectionTitle({ title }: { title: string }) {
  return (
    <div className="section__title mb-40">
      <h2 className="title">{title}</h2>
    </div>
  );
}

// --- Single accordion step ---
interface WorkStepProps {
  step: WorkStep;
  active?: boolean;
}
export function WorkStepItem({ step, active }: WorkStepProps) {
  return (
    <div className={`work__item${active ? ' active' : ''}`}>
      <button className="work__item-button">
        <span>{step.num}</span>
        {step.title}
      </button>
      <div className="work__content">
        <p>{step.text}</p>
      </div>
    </div>
  );
}

// --- All accordion steps ---
export function WorkStepList({ steps }: { steps: WorkStep[] }) {
  return (
    <div className="work__item-wrap">
      {steps.map((step, i) => (
        <WorkStepItem key={i} step={step} active={i === 0} />
      ))}
    </div>
  );
}

// --- Left column: heading + step list ---
export function WorkContentWrap({ title, steps }: { title: string; steps: WorkStep[] }) {
  return (
    <div className="work__content-wrap">
      <WorkSectionTitle title={title} />
      <WorkStepList steps={steps} />
    </div>
  );
}
