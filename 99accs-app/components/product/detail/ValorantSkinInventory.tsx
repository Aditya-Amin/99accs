'use client';
import { useMemo, useState } from 'react';
import type {
  ProductSkinFilters,
  ProductSkinInventory,
  SkinRarity,
  SkinRarityOption,
  WeaponTypeKey,
  WeaponTypeOption,
} from '@/lib/api/types';

interface Props {
  inventory: ProductSkinInventory;
  filters: ProductSkinFilters;
}

const ChevronIcon = () => (
  <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M6.69023 9.93453L2.31523 5.55953C2.27458 5.51888 2.24233 5.47063 2.22034 5.41752C2.19834 5.36441 2.18701 5.30748 2.18701 5.25C2.18701 5.19251 2.19834 5.13559 2.22034 5.08248C2.24233 5.02937 2.27458 4.98112 2.31523 4.94047C2.35587 4.89982 2.40413 4.86758 2.45724 4.84558C2.51035 4.82358 2.56727 4.81226 2.62476 4.81226C2.68224 4.81226 2.73916 4.82358 2.79227 4.84558C2.84538 4.86758 2.89364 4.89982 2.93429 4.94047L6.99976 9.00648L11.0652 4.94047C11.1473 4.85838 11.2587 4.81226 11.3748 4.81226C11.4909 4.81226 11.6022 4.85838 11.6843 4.94047C11.7664 5.02256 11.8125 5.1339 11.8125 5.25C11.8125 5.3661 11.7664 5.47744 11.6843 5.55953L7.30929 9.93453C7.26866 9.97521 7.22041 10.0075 7.16729 10.0295C7.11418 10.0515 7.05725 10.0628 6.99976 10.0628C6.94226 10.0628 6.88533 10.0515 6.83222 10.0295C6.77911 10.0075 6.73086 9.97521 6.69023 9.93453Z" fill="currentColor" fillOpacity="0.5" />
  </svg>
);

export default function ValorantSkinInventory({ inventory, filters }: Props) {
  const [selectedRarities, setSelectedRarities] = useState<Set<SkinRarity>>(new Set());
  const [selectedWeapons, setSelectedWeapons] = useState<Set<WeaponTypeKey>>(new Set());
  const [selectedItemIds, setSelectedItemIds] = useState<Set<string>>(new Set());
  const [expandedWeapons, setExpandedWeapons] = useState<Set<WeaponTypeKey>>(
    () => new Set(filters.weapon_types.map((w) => w.key)),
  );

  const filteredItems = useMemo(() => {
    return inventory.items.filter((item) => {
      if (selectedItemIds.size > 0) return selectedItemIds.has(item.id);
      if (selectedRarities.size > 0 && !selectedRarities.has(item.rarity)) return false;
      if (selectedWeapons.size > 0 && !selectedWeapons.has(item.weapon_type)) return false;
      return true;
    });
  }, [inventory.items, selectedRarities, selectedWeapons, selectedItemIds]);

  const rarityIconByKey = useMemo(
    () => Object.fromEntries(filters.rarities.map((r) => [r.key, r.icon])) as Record<SkinRarity, string>,
    [filters.rarities],
  );

  const toggleRarity = (key: SkinRarity) =>
    setSelectedRarities((prev) => {
      const next = new Set(prev);
      if (next.has(key)) next.delete(key);
      else next.add(key);
      return next;
    });

  const toggleWeapon = (key: WeaponTypeKey) =>
    setSelectedWeapons((prev) => {
      const next = new Set(prev);
      if (next.has(key)) next.delete(key);
      else next.add(key);
      return next;
    });

  const toggleItem = (id: string) =>
    setSelectedItemIds((prev) => {
      const next = new Set(prev);
      if (next.has(id)) next.delete(id);
      else next.add(id);
      return next;
    });

  const toggleExpanded = (key: WeaponTypeKey) =>
    setExpandedWeapons((prev) => {
      const next = new Set(prev);
      if (next.has(key)) next.delete(key);
      else next.add(key);
      return next;
    });

  return (
    <div className="shop__details-skin">
      <div className="inventory-title-wrap">
        <h2 className="inventory-title"><span>{inventory.total}</span>Total</h2>
        <h2 className="inventory-title"><span>{inventory.purchased}</span>Purchased</h2>
        <h2 className="inventory-title"><span>{inventory.vp}</span>VP</h2>
      </div>
      <div className="shop__details-skin-wrap">
        <div className="shop__details-skin-sidebar">
          <div className="shop__details-widget">
            <h2 className="shop__details-widget-title">Rarity</h2>
            <ul className="list-wrap">
              {filters.rarities.map((r) => (
                <RarityCheckbox key={r.key} option={r} checked={selectedRarities.has(r.key)} onToggle={() => toggleRarity(r.key)} />
              ))}
            </ul>
          </div>
          <div className="shop__details-widget">
            <h2 className="shop__details-widget-title">Weapon Type</h2>
            <ul className="list-wrap">
              {filters.weapon_types.map((w) => (
                <WeaponTypeRow
                  key={w.key}
                  option={w}
                  checked={selectedWeapons.has(w.key)}
                  expanded={expandedWeapons.has(w.key)}
                  onToggle={() => toggleWeapon(w.key)}
                  onExpand={() => toggleExpanded(w.key)}
                  items={inventory.items.filter((i) => i.weapon_type === w.key)}
                  selectedItemIds={selectedItemIds}
                  onToggleItem={toggleItem}
                />
              ))}
            </ul>
          </div>
        </div>

        <div className="skin__item-wrap">
          {filteredItems.length === 0 ? (
            <div style={{ padding: '60px 0', textAlign: 'center', opacity: 0.7 }}>
              No skins match the current filter.
            </div>
          ) : (
            <div className="row gutter-12 gutter-y-12">
              {filteredItems.map((item) => (
                <div key={item.id} className="col-xl-3 col-lg-4 col-sm-6">
                  <div className="skin__item">
                    <div className={item.thumb_modifier === 'two' ? 'skin__thumb skin__thumb-two' : 'skin__thumb'}>
                      <img src="/img/valorant/skin_item_bg.jpg" alt="" className="bg-img" />
                      <img src="/img/valorant/skin_item_bg_shape.png" alt="" className="bg-shape" />
                      <img src={item.image} alt={item.name} className="main-img" />
                      <div className="icon">
                        <img src={rarityIconByKey[item.rarity]} alt={item.rarity} />
                      </div>
                    </div>
                    <div className="skin__content">
                      <h2 className="title">{item.name}</h2>
                      {item.color_variants && item.color_variants.length > 0 && (
                        <ul className="list-wrap skin__card-color">
                          {item.color_variants.map((cv, i) => (
                            <li key={cv.id} className={i === 0 ? 'active' : undefined}>
                              <button type="button">
                                <img src={cv.image} alt="" />
                              </button>
                            </li>
                          ))}
                        </ul>
                      )}
                      {item.levels && item.levels > 0 && (
                        <ul className="list-wrap skin__card-level">
                          {Array.from({ length: item.levels }).map((_, i) => (
                            <li key={i}><span>{i + 1}</span></li>
                          ))}
                        </ul>
                      )}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

function RarityCheckbox({ option, checked, onToggle }: { option: SkinRarityOption; checked: boolean; onToggle: () => void }) {
  const id = `rarity-${option.key}`;
  return (
    <li>
      <div className="dropdown-check dropdown-check-three">
        <input type="checkbox" id={id} className="form-check-input" checked={checked} onChange={onToggle} />
        <label htmlFor={id}>
          <img src={option.icon} alt="" />
          {option.label}
        </label>
      </div>
    </li>
  );
}

function WeaponTypeRow({
  option, checked, expanded, onToggle, onExpand, items, selectedItemIds, onToggleItem,
}: {
  option: WeaponTypeOption;
  checked: boolean;
  expanded: boolean;
  onToggle: () => void;
  onExpand: () => void;
  items: { id: string; name: string }[];
  selectedItemIds: Set<string>;
  onToggleItem: (id: string) => void;
}) {
  const id = `weapon-${option.key}`;
  return (
    <li>
      <div className="dropdown-check-wrap">
        <div className="dropdown-check dropdown-check-three">
          <input type="checkbox" id={id} className="form-check-input" checked={checked} onChange={onToggle} />
          <label htmlFor={id}>{option.label}</label>
        </div>
        <div className="dropdown-check-right">
          <span className="number">{option.count}</span>
          <button
            type="button"
            className={expanded ? 'arrow active' : 'arrow'}
            onClick={onExpand}
            aria-expanded={expanded}
            aria-label={expanded ? 'Collapse' : 'Expand'}
          >
            <ChevronIcon />
          </button>
        </div>
      </div>
      {expanded && items.length > 0 && (
        <ul className="list-wrap inner-dropdown-check" style={{ display: 'block' }}>
          {items.map((item) => {
            const itemId = `skin-${item.id}`;
            return (
              <li key={item.id}>
                <div className="dropdown-check dropdown-check-three">
                  <input
                    type="checkbox"
                    id={itemId}
                    className="form-check-input"
                    checked={selectedItemIds.has(item.id)}
                    onChange={() => onToggleItem(item.id)}
                  />
                  <label htmlFor={itemId}>{item.name}</label>
                </div>
              </li>
            );
          })}
        </ul>
      )}
    </li>
  );
}
