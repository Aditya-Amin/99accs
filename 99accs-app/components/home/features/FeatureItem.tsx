// --- Decorative background shape inside a card ---
export function FeatureShape() {
  return (
    <span className="shape">
      <img src="/img/images/features_shape.svg" alt="" />
    </span>
  );
}

// --- Feature icon image ---
interface FeatureIconProps {
  src: string;
}
export function FeatureIcon({ src }: FeatureIconProps) {
  return (
    <div className="features__icon">
      <img src={src} alt="" />
    </div>
  );
}

// --- Title + description text block ---
interface FeatureContentProps {
  title: string;
  text: string;
}
export function FeatureContent({ title, text }: FeatureContentProps) {
  return (
    <div className="features__content">
      <h2 className="title">{title}</h2>
      <p>{text}</p>
    </div>
  );
}

// --- Full feature card: shape + icon + content ---
interface FeatureItemCardProps {
  title: string;
  icon: string;
  text: string;
}
export function FeatureItemCard({ title, icon, text }: FeatureItemCardProps) {
  return (
    <div className="features__item">
      <FeatureShape />
      <FeatureIcon src={icon} />
      <FeatureContent title={title} text={text} />
    </div>
  );
}
