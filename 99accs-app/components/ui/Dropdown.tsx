'use client';
import { useRef, useState, useEffect, type ReactNode, type CSSProperties } from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useOutsideClick } from '@/lib/hooks/useOutsideClick';

// Reusable header dropdown — replaces the bespoke implementations in
// Header.tsx (Valorant region picker) and HeaderUserMenu.tsx.
//
// Self-contained: positioning + look are inline-styled so the menu works
// anywhere, not just inside .tgmenu__categories (whose CSS originally
// supplied the absolute positioning and blur backdrop). Hover styling is
// driven by per-item React state because CSS `:hover` rules for these
// classes are similarly scoped in globals.css.

export type DropdownItem =
  | {
      kind: 'link';
      href: string;
      icon?: ReactNode;
      label: ReactNode;
      key?: string;
    }
  | {
      kind: 'button';
      onClick: () => void;
      icon?: ReactNode;
      label: ReactNode;
      key?: string;
    };

interface Props {
  /** Contents of the toggle button — usually an icon + label. */
  toggle: ReactNode;
  items: DropdownItem[];
  /** Side the menu's edge sticks to. Default 'left'. */
  align?: 'left' | 'right';
  /** Container class. Defaults to "dropdown children" to match existing CSS. */
  containerClassName?: string;
  /** Toggle button class. Defaults to "dropdown-toggle children". */
  toggleClassName?: string;
  /** Inline overrides merged onto the menu's default styles (e.g. opaque bg). */
  menuStyleOverride?: CSSProperties;
}

// Mirrors .tgmenu__categories .dropdown-menu in globals.css.
const MENU_BASE: CSSProperties = {
  position: 'absolute',
  top: '110%',
  minWidth: 220,
  backdropFilter: 'blur(100px)',
  WebkitBackdropFilter: 'blur(100px)',
  boxShadow:
    '0 1px 1px 0 rgba(0,11,4,0.1), 0 2px 2px 0 rgba(0,11,4,0.1), 0 4px 4px 0 rgba(0,11,4,0.1), 0 8px 8px 0 rgba(0,11,4,0.1), 0 16px 16px 0 rgba(0,11,4,0.2)',
  // background: 'rgba(255,255,255,0.06)',
  border: '1px solid rgba(255,255,255,0.06)',
  borderRadius: 8,
  padding: 8,
  listStyle: 'none',
  margin: 0,
  zIndex: 1000,
};

// Mirrors .tgmenu__categories .dropdown-menu li a in globals.css, with the
// additions needed for icon+label flex layout used by HeaderUserMenu.
const ITEM_BASE: CSSProperties = {
  fontWeight: 500,
  fontSize: 17,
  color: 'var(--tg-color-white-default)',
  borderRadius: 4,
  padding: '12px 10px',
  display: 'flex',
  alignItems: 'center',
  gap: 10,
  width: '100%',
  textAlign: 'left',
  textDecoration: 'none',
  border: 'none',
  background: 'transparent',
  cursor: 'pointer',
};

const ITEM_HOVER: CSSProperties = { background: 'rgba(255,255,255,0.06)' };

export function Dropdown({
  toggle,
  items,
  align = 'left',
  containerClassName = 'dropdown children',
  toggleClassName = 'dropdown-toggle children',
  menuStyleOverride,
}: Props) {
  const pathname = usePathname();
  const [open, setOpen] = useState(false);
  const [hoverIndex, setHoverIndex] = useState<number | null>(null);
  const ref = useRef<HTMLDivElement>(null);

  // Close on navigation (matches the prior bespoke behaviour).
  useEffect(() => { setOpen(false); }, [pathname]);
  useOutsideClick(ref, () => setOpen(false));

  const close = () => setOpen(false);

  // Order matters: defaults → caller override → open/align (always last so
  // visibility + side anchor can't be accidentally lost by a caller).
  const menuStyle: CSSProperties = {
    ...MENU_BASE,
    ...menuStyleOverride,
    display: open ? 'block' : 'none',
    left: align === 'left' ? 0 : 'auto',
    right: align === 'right' ? 0 : 'auto',
  };

  const itemStyle = (i: number): CSSProperties => ({
    ...ITEM_BASE,
    ...(hoverIndex === i ? ITEM_HOVER : null),
  });

  return (
    <div className={containerClassName} ref={ref} style={{ position: 'relative' }}>
      <button
        type="button"
        className={toggleClassName}
        onClick={(e) => { e.preventDefault(); setOpen((o) => !o); }}
        aria-haspopup="menu"
        aria-expanded={open}
      >
        {toggle}
      </button>
      <ul className="dropdown-menu" style={menuStyle}>
        {items.map((item, i) => (
          <li
            key={item.key ?? i}
            onMouseEnter={() => setHoverIndex(i)}
            onMouseLeave={() => setHoverIndex((cur) => (cur === i ? null : cur))}
          >
            {item.kind === 'link' ? (
              <Link className="dropdown-item" href={item.href} onClick={close} style={itemStyle(i)}>
                {item.icon}
                {item.label}
              </Link>
            ) : (
              <button
                type="button"
                className="dropdown-item"
                onClick={() => { close(); item.onClick(); }}
                style={itemStyle(i)}
              >
                {item.icon}
                {item.label}
              </button>
            )}
          </li>
        ))}
      </ul>
    </div>
  );
}
