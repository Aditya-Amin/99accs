interface ShopDetailsListProps {
  shortDescription?: string | null;
}

export default function ShopDetailsList({ shortDescription }: ShopDetailsListProps) {
  if (shortDescription) {
    return (
      <div
        className="shop__details-list list-wrap"
        dangerouslySetInnerHTML={{ __html: shortDescription }}
      />
    );
  }

  return (
    <ul className="shop__details-list list-wrap">
      <li>
        <img src="/img/icons/shop_details_icon01.png" alt="icon" />
        High-Quality Accounts
      </li>
      <li>
        <img src="/img/icons/shop_details_icon02.png" alt="icon" />
        Instant Delivery After Payment
      </li>
      <li>
        <img src="/img/icons/shop_details_icon03.png" alt="icon" />
        Free Warranty and Support
      </li>
    </ul>
  );
}
