# Coding Patterns & Conventions

## Stack
- **Next.js 14/15** — App Router, TypeScript
- **CSS**: `public/main.css` loaded last in `layout.tsx`; class names match the original HTML templates exactly
- **Images**: Always use native `<img>` tags — **no `next/image`**
- **Grid**: Bootstrap class names only (no Bootstrap JS)

---

## Component Architecture

### File Organization
```
components/
  <feature>/
    <Feature>Client.tsx       ← 'use client' orchestrator with state
    <Feature>Breadcrumb.tsx   ← server component
    tabs/
      DashboardPane.tsx       ← individual tab panes (server or client)
      OrdersPane.tsx
      ...
app/
  <feature>/
    layout.tsx                ← minimal shell, no sidebar
    page.tsx                  ← imports Breadcrumb + Client orchestrator
```

### Server vs Client
- **Server components by default** — no `'use client'` unless hooks/events are needed
- Add `'use client'` when using: `useState`, `useEffect`, event handlers, `useRef`

---

## Tab System

The HTML uses JS `data-tab` switching. React equivalent:

```tsx
// Orchestrator (AccountDashboardClient.tsx)
'use client';
const [activeTab, setActiveTab] = useState('tab1');
// conditionally render — only active pane is mounted
{activeTab === 'tab1' && <DashboardPane />}
{activeTab === 'tab2' && <OrdersPane />}
```

### CRITICAL — `.account-pane` visibility
The CSS hides all `.account-pane` elements and only shows `.account-pane.active`:
```css
.account__dashboard-details > .account-pane { display: none; }
.account__dashboard-details > .account-pane.active { display: block; }
```
Since React only mounts the active pane, **always add `active` to the root div** of every pane component:
```tsx
<div className="support__table-wrap-two account-pane active">
```

Same rule applies to `.support__table-tab .table-pane` — always add `active` to the single rendered table pane.

---

## Account Dashboard Components

| File | Role |
|---|---|
| `app/account/layout.tsx` | `<main className="main-area fix">{children}</main>` only |
| `app/account/page.tsx` | Imports `AccountBreadcrumb` + `AccountDashboardClient` |
| `components/account/dashboard/AccountDashboardClient.tsx` | `'use client'`, tab state, renders sidebar + pane |
| `components/account/dashboard/DashboardSidebar.tsx` | `'use client'`, takes `{ activeTab, onTabChange }` props |
| `components/account/dashboard/tabs/DashboardPane.tsx` | Tab 1 — welcome |
| `components/account/dashboard/tabs/OrdersPane.tsx` | Tab 2 — orders table, sub-tabs |
| `components/account/dashboard/tabs/SupportPane.tsx` | Tab 3 — support tickets, sub-tabs |
| `components/account/dashboard/tabs/AccountDetailsPane.tsx` | Tab 4 — account form |
| `components/account/dashboard/tabs/TransactionHistoryPane.tsx` | Tab 5 — transaction history |

---

## Status Class Names (in table cells)

| Status | CSS class |
|---|---|
| Completed / Open | `open` |
| Cancelled / Closed | `closed` |
| In Progress | `country__code ap` |
| New | `new` |

---

## Image Paths
HTML source uses `assets/img/...` → Next.js serves from `/img/...` (mapped to `public/img/`)

---

## Breadcrumb Pattern
```tsx
<section
  className="breadcrumb__area-two"
  style={{ backgroundImage: 'url(/img/bg/breadcrumb_bg.jpg)' }}
>
```

---

## HTML Reference
All original designs live in `d:\99accs-v2.html\`:
- `my-account.html` — account dashboard
- `support.html` — support portal
- `shop.html`, `shop-details.html` — shop pages
- `index.html` — home page

**Always read the HTML source** for exact class names, SVG paths, and mock data before implementing a component.

---

## Nested Filter Sidebar (parent group + expandable inner checkboxes)

Used by `components/product/detail/ValorantSkinInventory.tsx`. HTML reference: `shop-details.html` → `.shop__details-widget` → `.dropdown-check-wrap` → `ul.list-wrap.inner-dropdown-check`.

### Markup contract (must match HTML exactly)
```html
<li>
  <div class="dropdown-check-wrap">
    <div class="dropdown-check dropdown-check-three">
      <input type="checkbox" id="weapon-{key}" />
      <label for="weapon-{key}">{Label}</label>
    </div>
    <div class="dropdown-check-right">
      <span class="number">{count}</span>
      <button class="arrow active">…chevron svg…</button>   <!-- toggle "active" class, NOT inline transform -->
    </div>
  </div>
  <ul class="list-wrap inner-dropdown-check" style="display: block;">  <!-- only when expanded -->
    <li>
      <div class="dropdown-check dropdown-check-three">
        <input type="checkbox" id="{group}-{item.id}" />
        <label for="{group}-{item.id}">{item.name}</label>
      </div>
    </li>
    …
  </ul>
</li>
```

### React state shape (multi-select with three independent dimensions)
```tsx
const [selectedGroups, setSelectedGroups]   = useState<Set<GroupKey>>(new Set());     // parent (e.g. weapon type)
const [selectedItemIds, setSelectedItemIds] = useState<Set<string>>(new Set());       // inner (e.g. specific skin)
const [expandedGroups, setExpandedGroups]   = useState<Set<GroupKey>>(
  () => new Set(filters.groups.map(g => g.key)),                                       // open by default
);
```

### Filter precedence rule (do not change without reason)
Inner selections **override** parent selections when present — so picking specific items still works even if the parent group isn't checked:
```ts
items.filter((item) => {
  if (selectedItemIds.size > 0) return selectedItemIds.has(item.id);   // inner wins
  if (selectedGroups.size > 0 && !selectedGroups.has(item.groupKey)) return false;
  if (selectedRarities.size > 0 && !selectedRarities.has(item.rarity)) return false;
  return true;
});
```

### Set toggle helper (re-use, do not mutate the existing Set)
```tsx
const toggle = <K,>(setter: Dispatch<SetStateAction<Set<K>>>) => (key: K) =>
  setter(prev => {
    const next = new Set(prev);
    next.has(key) ? next.delete(key) : next.add(key);
    return next;
  });
```

### Why these specifics
- **`arrow active` class, not inline `transform`** — the HTML/CSS already styles `.arrow.active` to rotate the chevron; using inline style fights the stylesheet and breaks visual parity.
- **`style={{ display: 'block' }}` on the inner `<ul>`** — matches the reference markup so jQuery plugins that probe inline style (if any get re-introduced) don't collapse it.
- **Inner overrides parent** — users naturally drill down: when they tick a specific item, they want only it, not "this item plus the whole parent group".
- **Open-by-default expansion** — discoverability; the user immediately sees the sub-list is collapsible.
- **API supplies filter options + counts** — the sidebar is data-driven (`filters.rarities`, `filters.weapon_types` with `count`). Don't compute counts in the component; the API/mock provides them so DOM filtering stays consistent across the listing.
