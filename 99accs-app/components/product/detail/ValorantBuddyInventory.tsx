import type { ProductBuddyInventory } from '@/lib/api/types';

interface Props {
  inventory: ProductBuddyInventory;
}

// Renders the `.shop__details-buddies` section from shop-details.html — a
// summary header (Total / Purchased / VP) plus a flat grid of buddy cards.
// No filtering here; buddies are a single-purpose collectible category.
export default function ValorantBuddyInventory({ inventory }: Props) {
  return (
    <div className="shop__details-buddies">
      <div className="inventory-title-wrap">
        <h2 className="inventory-title"><span>{inventory.total}</span>Total</h2>
        <h2 className="inventory-title"><span>{inventory.purchased}</span>Purchased</h2>
        <h2 className="inventory-title"><span>{inventory.vp}</span>VP</h2>
      </div>
      <div className="buddies__item-wrap">
        {inventory.items.map((buddy) => (
          <div key={buddy.id} className="buddies__item">
            <div className="thumb">
              <img src={buddy.image} alt={buddy.name} />
            </div>
            <div className="content">
              <span className="title">{buddy.name}</span>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
