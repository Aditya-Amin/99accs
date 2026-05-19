import { getWishlist } from '@/lib/api/endpoints';
import ProductCard from '@/components/product/ProductCard';

export default async function WishlistPage() {
  const res = await getWishlist('mock_token').catch(() => null);
  const items = res?.data ?? [];

  return (
    <div>
      <h2 className="title" style={{ marginBottom: 32 }}>My Wishlist</h2>
      {items.length === 0 ? (
        <p style={{ opacity: 0.7 }}>Your wishlist is empty.</p>
      ) : (
        <div className="row gutter-y-24">
          {items.map((item) => (
            <div key={item.id} className="col-xl-4 col-sm-6">
              <ProductCard product={item.product} />
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
