// --- Left column heading for features section ---
export function FeaturesHeading({ heading }: { heading: string }) {
  return (
    <div className="features__content-wrap">
      <div className="section__title">
        <h2 className="title" dangerouslySetInnerHTML={{ __html: heading }} />
      </div>
    </div>
  );
}
