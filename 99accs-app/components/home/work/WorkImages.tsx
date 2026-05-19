// --- Single work image slot ---
export function WorkImage({ src }: { src: string }) {
  return (
    <div className="work__img">
      <img src={src} alt="img" />
    </div>
  );
}

// --- Grid of work images (right column) ---
export function WorkImages({ images }: { images: string[] }) {
  return (
    <div className="work__img-wrap">
      {images.map((src, i) => (
        <WorkImage key={i} src={src} />
      ))}
    </div>
  );
}
