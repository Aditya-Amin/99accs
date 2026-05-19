// --- About section image (left column) ---
export function AboutImage({ src }: { src: string }) {
  return (
    <div className="about__img wow fadeInLeft" data-wow-delay=".3s">
      <img src={src} alt="img" />
    </div>
  );
}
