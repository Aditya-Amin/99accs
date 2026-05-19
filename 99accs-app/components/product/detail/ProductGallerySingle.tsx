interface ProductGallerySingleProps {
  image: string;
  alt: string;
  variant?: 'default' | 'two';
}

export default function ProductGallerySingle({ image, alt, variant = 'default' }: ProductGallerySingleProps) {
  const className =
    variant === 'two' ? 'shop__details-thumb shop__details-thumb-two' : 'shop__details-thumb';
  return (
    <div className={className}>
      <img src={image} alt={alt} />
    </div>
  );
}
