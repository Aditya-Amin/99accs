'use client';
import { useMemo, useState } from 'react';
import type {
  ProductSkinFilters,
  ProductSkinInventory,
  SkinRarity,
  SkinRarityOption,
  WeaponTypeKey,
} from '@/lib/api/types';
import WeaponTypeAccordionItem from '@/components/ui/WeaponTypeAccordionItem';

interface Props {
  inventory: ProductSkinInventory;
  filters: ProductSkinFilters;
}

export default function ValorantSkinInventory({ inventory, filters }: Props) {
  const [selectedRarities, setSelectedRarities] = useState<Set<SkinRarity>>(new Set());
  const [selectedWeapons, setSelectedWeapons] = useState<Set<WeaponTypeKey>>(new Set());
  const [selectedItemIds, setSelectedItemIds] = useState<Set<string>>(new Set());

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
                <WeaponTypeAccordionItem
                  key={w.key}
                  groupId={w.key}
                  label={w.label}
                  count={w.count}
                  checked={selectedWeapons.has(w.key)}
                  onToggle={() => toggleWeapon(w.key)}
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
