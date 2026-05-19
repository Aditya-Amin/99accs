// --- Single star SVG ---
export function StarIcon() {
  return (
    <svg width="18" height="17" viewBox="0 0 18 17" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path
        d="M8.55953 13.32L3.26944 16.2811L4.45094 10.3349L0 6.21885L6.02028 5.50504L8.55953 0L11.0987 5.50504L17.119 6.21885L12.6681 10.3349L13.8496 16.2811L8.55953 13.32Z"
        fill="currentColor"
      />
    </svg>
  );
}

// --- Row of N stars ---
interface StarRatingProps {
  count: number;
}
export function StarRating({ count }: StarRatingProps) {
  return (
    <div className="rating">
      {Array.from({ length: count }).map((_, i) => (
        <StarIcon key={i} />
      ))}
    </div>
  );
}
