'use client';
import { useState } from 'react';

const ChevronIcon = () => (
  <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path
      d="M6.69023 9.93453L2.31523 5.55953C2.27458 5.51888 2.24233 5.47063 2.22034 5.41752C2.19834 5.36441 2.18701 5.30748 2.18701 5.25C2.18701 5.19251 2.19834 5.13559 2.22034 5.08248C2.24233 5.02937 2.27458 4.98112 2.31523 4.94047C2.35587 4.89982 2.40413 4.86758 2.45724 4.84558C2.51035 4.82358 2.56727 4.81226 2.62476 4.81226C2.68224 4.81226 2.73916 4.82358 2.79227 4.84558C2.84538 4.86758 2.89364 4.89982 2.93429 4.94047L6.99976 9.00648L11.0652 4.94047C11.1473 4.85838 11.2587 4.81226 11.3748 4.81226C11.4909 4.81226 11.6022 4.85838 11.6843 4.94047C11.7664 5.02256 11.8125 5.1339 11.8125 5.25C11.8125 5.3661 11.7664 5.47744 11.6843 5.55953L7.30929 9.93453C7.26866 9.97521 7.22041 10.0075 7.16729 10.0295C7.11418 10.0515 7.05725 10.0628 6.99976 10.0628C6.94226 10.0628 6.88533 10.0515 6.83222 10.0295C6.77911 10.0075 6.73086 9.97521 6.69023 9.93453Z"
      fill="currentColor"
      fillOpacity="0.5"
    />
  </svg>
);

export interface AccordionChildItem {
  id: string;
  name: string;
}

interface Props {
  groupId: string;
  label: string;
  count: number;
  checked: boolean;
  onToggle: () => void;
  items: AccordionChildItem[];
  selectedItemIds: Set<string>;
  onToggleItem: (id: string) => void;
}

export default function WeaponTypeAccordionItem({
  groupId,
  label,
  count,
  checked,
  onToggle,
  items,
  selectedItemIds,
  onToggleItem,
}: Props) {
  const [open, setOpen] = useState(false);

  return (
    <li>
      <div className="dropdown-check-wrap">
        <div className="dropdown-check dropdown-check-three">
          <input
            type="checkbox"
            id={`weapon-${groupId}`}
            className="form-check-input"
            checked={checked}
            onChange={onToggle}
          />
          <label htmlFor={`weapon-${groupId}`}>{label}</label>
        </div>
        <div className="dropdown-check-right">
          <span className="number">{count}</span>
          {/* CSS: .arrow.active { transform: rotate(180deg) } — no inline transform */}
          <button
            type="button"
            className={open ? 'arrow active' : 'arrow'}
            onClick={() => setOpen((v) => !v)}
          >
            <ChevronIcon />
          </button>
        </div>
      </div>
      {/*
        Always rendered — CSS sets display:none by default.
        When open, inline display:block overrides it (mirrors jQuery slideToggle).
        When closed, no inline style → CSS display:none takes effect.
      */}
      <ul
        className="list-wrap inner-dropdown-check"
        style={open ? { display: 'block' } : undefined}
      >
        {items.map((item) => (
          <li key={item.id}>
            <div className="dropdown-check dropdown-check-three">
              <input
                type="checkbox"
                id={`skin-${item.id}`}
                className="form-check-input"
                checked={selectedItemIds.has(item.id)}
                onChange={() => onToggleItem(item.id)}
              />
              <label htmlFor={`skin-${item.id}`}>{item.name}</label>
            </div>
          </li>
        ))}
      </ul>
    </li>
  );
}
