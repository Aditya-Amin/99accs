# Products — Laravel API Guide

The contract between the Next.js frontend and the Laravel backend for the **shop / product list** (`/shop/[game]`) and **product detail** (`/shop/[game]/[slug]`) pages.

The Next.js shop list page currently reads **directly** from `mocks/products/{game}.json` via `getMockProducts()` in `lib/mock/products.ts` (no HTTP). When the Laravel API is live, swap the call to `getProducts()` + set `NEXT_PUBLIC_API_BASE_URL` — no other frontend code changes.

**Treat every visible attribute of the HTML product cards / detail pages as API data.** No product copy, image, badge or icon should be hard-coded in the React tree. The mock JSON files in this folder are the source of truth and a faithful 1:1 mirror of the HTML shop pages.

---

## Endpoints

```
GET  /api/products                       # paginated collection
GET  /api/products/{slug}                # single resource (includes related[])
```

- **Public** (no auth required)
- **Cache**: shop list is safe to cache 60–300s; detail page slightly longer. Inventory writes invalidate.

### Query parameters (`/api/products`)

| Param | Type | Notes |
|---|---|---|
| `game` | enum | `valorant` \| `fortnite` \| `legends` |
| `account_type` | enum | `verified` \| `inactive_exclusive` \| `nfa_random` \| `nfa_guaranteed` \| `nfa_inactive` \| `standard` — drives the detail-page layout (see [Detail page layout](#detail-page-layout--driven-by-account_type)). |
| `section` | string | catalog section slug. Used by Legends since all three Legends sections share `account_type='standard'` but split on server: `euw` / `tr` / `las`. Valorant + Fortnite section slugs match the `account_type` 1:1, so this param is redundant there. |
| `country` | string | uppercase, exact match against `country.code` (e.g. `NA`, `EUW`, `LAS`, `KR`) |
| `region` | enum | `na` \| `eu` \| `apac` \| `latam` \| `br` (lowercased; coarse region bucket) |
| `min_price` / `max_price` | float | matched against `price` |
| `rank` | string | case-insensitive equality (e.g. `Diamond`) |
| `search` | string | matches title or description (ILIKE) |
| `sort` | enum | `price_asc` \| `price_desc` \| `newest` \| `oldest` |
| `page` / `per_page` | int | default `per_page=24` (shop list pages send 48) |

Each product carries its own `section: { slug, label, order }` object — the frontend buckets results client-side by `section.slug`, sorted by `section.order`, and renders each section header from `section.label`. **Laravel does not need to group server-side** — just return the flat collection. See [Section grouping](#section-grouping).

---

## Response envelope

### Collection
```json
{
  "data": [ ...products ],
  "meta": { "current_page": 1, "last_page": 5, "per_page": 24, "total": 56 },
  "links": { "first": "...", "last": "...", "next": "...", "prev": null }
}
```

### Single resource
```json
{ "data": { ...product, "related": [ ...3-6 products ] } }
```

The detail endpoint should **include all optional rich fields** (`agents_detailed`, `account_stats`, `locker`, `seasons`, `description_sections`, `profile_info`, `guarantee`, etc. — see the [Detail-only fields](#detail-only-fields) table). The list endpoint should **omit them** to keep the payload lean.

---

## Product Resource (snake_case) — full shape

```jsonc
{
  // Always present (list + detail)
  "id": 11,
  "slug": "valorant-57-skins-inactive-na-01",
  "game": "valorant",                              // enum
  "account_type": "inactive_exclusive",            // enum — drives detail layout (see Detail page layout)
  "section": {                                     // catalog grouping (see Section grouping)
    "slug": "inactive_exclusive",
    "label": "INACTIVE EXCLUSIVE ACCOUNTS",
    "order": 2
  },
  "title": "57 Skins Inactive | NA",
  "price": 2.00,
  "price_max": 4.00,                               // nullable — when set, card renders "$2.00 – $4.00" for range pricing
  "old_price": 0.80,                               // nullable — strike-through next to price
  "country": {
    "code": "NA",                                  // free-form short string. Values seen in HTML: NA, EU, EUW, EUNE, AP, KR, CN, RU, LATAM, LAS, BR, TR. Empty string "" suppresses the badge (Fortnite NFA cards).
    "flag": "/img/icons/us.png",                   // nullable — small flag image rendered next to title
    "class_modifier": "ap"                         // optional — CSS class override when modifier differs from `code.toLowerCase()`. Legends LAS cards use `code="LAS"` + `class_modifier="ap"` → `<span class="country__code ap">LAS</span>`.
  },
  "categories": [                                  // tag chips with icons
    { "id": 1, "label": "58 Skins",       "icon": "skins_count" },
    { "id": 2, "label": "143 Champions",  "icon": "champions" },
    { "id": 3, "label": "Mail Access",    "icon": "mail_access" }
  ],
  "images": [                                      // first = card thumb. When `has_gallery`, [1..] are also previewed in the popup.
    "/img/valorant/exclusive_img_01.jpg",
    "/img/valorant/exclusive_img_02.jpg"
  ],
  "has_gallery": true,                             // optional — when true the card renders the shop__thumb-gallery popup with image-stack icon and a "{N}+" count. Independent of `account_type` (some inactive_exclusive cards don't have it, some `standard` Legends cards do).
  "discount_percent": 40,                          // nullable — "-N% OFF" badge
  "badge_icon": "/img/icons/exclusive_icon_01.png",// nullable — badge img inside .shop__content-top-right. Renders for any card with the icon set (not just inactive_exclusive).
  "region": "na",                                  // coarse region bucket for filtering
  "rank": null,
  "agents": [],
  "skins": [],
  "buddies": [],
  "description": "...",                            // short text used as the meta description
  "specs": {                                       // free-form key/value displayed in profile-info card
    "Skins": "57", "Champions": "143", "Mail Access": "Yes", "Region": "North America"
  },
  "stock": 1,
  "created_at": "2025-01-10T00:00:00Z",

  // Detail-only — see table below
  "agents_detailed": [...],
  "agents_count": 28,
  "profile_info": {...},
  "account_level": {...},
  "account_stats": [...],
  "locker": {...},
  "seasons": {...},
  "description_sections": [...],
  "min_quantity": 5,
  "last_match_label": "Last Match: August 2024",
  "guarantee": { "title": "99Accs Guarantee", "body": "..." },

  // Only on detail
  "related": [ /* 3-6 sibling products, same shape (list-level fields only) */ ]
}
```

### Core field semantics

| Field | Notes |
|---|---|
| `account_type` | Drives the detail-page layout via `accountTypeToLayout()` (one of `rich` / `simple_two` / `simple_three` / `fortnite_four`). Also controls the catalog card's thumb variant — every layout except `simple_two` uses the `shop__thumb-two` gallery-capable card. |
| `section` | `{ slug, label, order }`. Frontend groups the catalog page by `section.slug`, renders each header from `section.label`, in `section.order`. See [Section grouping](#section-grouping). |
| `country.code` | Badge text (typically uppercase, e.g. `NA`, `EUW`, `LAS`). Empty string `""` suppresses the country chip entirely — used by Fortnite NFA cards that have no country badge in HTML. |
| `country.flag` | URL or `null`. NA / EU / BR have flag PNGs; AP / LATAM / KR / CN / RU / TR / EUW omit. |
| `country.class_modifier` | Optional CSS class-modifier override. Defaults to `code.toLowerCase()`. Set when text and class diverge — Legends LAS cards render `<span class="country__code ap">LAS</span>` so `code="LAS"`, `class_modifier="ap"`. |
| `categories[]` | Each: `{ id, label, icon }`. `icon` is an **enum key** (`random_skins`, `skins_count`, `champions`, `mail_access`, `exclusive_skins`), NOT a URL. Adding a new icon requires registering it in [`components/icons/index.tsx`](../../components/icons/index.tsx) → `CATEGORY_ICONS`. |
| `images[]` | Order matters. `images[0]` is the card thumbnail. When `has_gallery` is true, the remaining indices feed the gallery popup. For `simple_three` (Legends) detail layout, the array drives the Swiper thumb carousel. |
| `has_gallery` | Optional boolean. When `true`, the catalog card renders the `shop__thumb-gallery` popup (image-stack icon + `{images.length}+` count + hidden gallery anchors). Falsy → no popup. Independent of `account_type`. |
| `price_max` | When set, the card renders `$price – $price_max`. Used by Fortnite "Random Skins" range tiers. |
| `discount_percent` | Integer 1-100. Renders the `-N% OFF` badge on the card thumb. Independent of `old_price`. |
| `badge_icon` | Asset URL or `null`. When set, renders inside `.shop__content-top-right` next to the country chip. Used by Valorant inactive_exclusive (`exclusive_icon_NN.png`) and all Legends cards (`euw_icon_NN.png`). |
| `old_price` | Strike-through next to `price` when set. |

---

## Detail-only fields

Returned only by `GET /api/products/{slug}`. Each is optional — presence drives layout selection (see [Detail page layout](#detail-page-layout)).

| Field | Type | Layout that uses it |
|---|---|---|
| `agents_detailed[]` | `[{ id, image, role_icon \| null }]` — 26-tile Valorant agent inventory | rich |
| `agents_count` | integer — display number shown next to "Agents" inventory title (defaults to `agents_detailed.length`) | rich |
| `profile_info` | `{ region, region_icon, profile_image, profile_stats[], inventory_value, ranks[], features[] }` — the page-1 sidebar block (Region card + profile thumb with stats + inventory value + 3 rank cards + 3 feature chips) | rich |
| `skin_inventory` | `{ total, purchased, vp, items: ProductSkinItem[] }` — the `.shop__details-skin` section. See [Skin + buddies inventory](#skin--buddies-inventory). | rich |
| `skin_filters` | `{ rarities: SkinRarityOption[], weapon_types: WeaponTypeOption[] }` — drives the sidebar filter. The frontend filters `skin_inventory.items` client-side; the API just supplies the option metadata + counts. | rich |
| `buddy_inventory` | `{ total, purchased, vp, items: ProductBuddy[] }` — the `.shop__details-buddies` section. | rich |
| `account_level` | `{ value: int, label?: string, description?: string }` — level badge + descriptive paragraph | fortnite_locker |
| `account_stats[]` | `[{ label, value, icon }]` — Wins/Matches/Skins/etc. `icon` ∈ `wins`, `matches`, `gold_bars`, `vbucks`, `gifts_sent`, `gifts_received`, `tickets_available`, `tickets_used`, `skins`, `back_blings`, `pickaxes`, `emotes`, `gliders`, `exclusives` | fortnite_locker |
| `locker` | `{ tabs: [{ key, label, groups: [{ title, count?, purchased?, vbucks?, items: [{ id, name, image }] }] }] }`. `key` ∈ `all`, `exclusives`, `skins`, `back_blings`, `pickaxes`, `gliders`, `emotes` | fortnite_locker |
| `seasons` | `{ current?: { number, stats[] }, history?: [...], chapter_title?, history_background? }`. `current.stats[].icon` ∈ `rank`, `level`, `season_wins`, `bp_level`, `bp_purchased`, `last_match`. `history_background` is the URL applied as inline `style.backgroundImage` on every history card. | fortnite_locker |
| `description_sections[]` | See [Description sections](#description-sections) below | simple_description, fortnite_locker, valorant_agents |
| `min_quantity` | int — minimum order quantity (page 2 only) | simple_description |
| `last_match_label` | string — chip rendered in the shop__tag-wrap above the price (e.g. `Last Match: August 2024`) | fortnite_locker |
| `guarantee` | `{ title, body }` — sidebar guarantee box. Frontend falls back to a default if absent. | shared |

### Description sections

`description_sections[]` is a discriminated union — three section types, all keyed by `type`:

```ts
type ProductDescriptionSection =
  | { heading: string; type: 'paragraph'; items: string[] }
  | { heading: string; type: 'list';      items: string[]; list_class?: string }
  | { heading: string; type: 'faq';       items: { question: string; answer: string }[] };
```

| `type` | Rendered as |
|---|---|
| `paragraph` | `<p>` per string in `items`. The very first section heading uses `<h2 class="title">`; subsequent sections use `<h2 class="title-two">` inside a `.shop__description-inner`. |
| `list` | `<ul class="list-wrap inside-list">` (or the value of `list_class` if provided — HTML uses `started-list`, `account-list`, `offer-list`). One `<li>` per string. |
| `faq` | `<ul class="list-wrap faq-list">`. Each `item` becomes `<li class="faq-list-item"><h2 class="title">{question}</h2><p>{answer}</p></li>`. |

The page-1 and page-4 detail pages share an identical six-section block (Description paragraph → Benefits → What's Included → Our Advantages → How to Purchase → FAQ). Pages 2 and 3 use a Fortnite-themed six-section block. The Laravel admin should let editors pick a *template* of preset section content per product variant.

### Skin + buddies inventory

These three fields together render the `.shop__details-skin` and `.shop__details-buddies` sections that sit under the Agents grid on shop-details.html. They only appear on `account_type='inactive_exclusive'` products (the `rich` layout).

```jsonc
{
  "skin_inventory": {
    "total": 70,
    "purchased": 17,
    "vp": 35800,
    "items": [
      {
        "id": "sk05",
        "name": "RGX 11z Pro Operator",
        "image": "/img/valorant/skin_item_04.png",
        "rarity": "exclusive",                  // enum — see below
        "weapon_type": "rifles",                // enum — see below
        "levels": 5,                            // optional — 1..5 dots in `.skin__card-level`
        "color_variants": [                     // optional — when present, renders the `.skin__card-color` row
          { "id": "cv05a", "image": "/img/valorant/skin_color_img01.jpg" },
          { "id": "cv05b", "image": "/img/valorant/skin_color_img02.jpg" }
        ],
        "thumb_modifier": null                  // 'two' → adds `.skin__thumb-two` modifier; null/omit for default framing
      }
    ]
  },
  "skin_filters": {
    "rarities": [
      { "key": "ultra",     "label": "Ultra",     "icon": "/img/icons/sidebar_icon01.svg" },
      { "key": "exclusive", "label": "Exclusive", "icon": "/img/icons/sidebar_icon02.svg" },
      { "key": "premium",   "label": "Premium",   "icon": "/img/icons/sidebar_icon03.svg" },
      { "key": "deluxe",    "label": "Deluxe",    "icon": "/img/icons/sidebar_icon04.svg" },
      { "key": "select",    "label": "Select",    "icon": "/img/icons/sidebar_icon05.svg" }
    ],
    "weapon_types": [
      { "key": "melee",         "label": "Lelle",        "count": 4 },
      { "key": "rifles",        "label": "Rifles",       "count": 5 },
      { "key": "sniper_rifles", "label": "Sniper Rifles", "count": 3 },
      { "key": "sidearms",      "label": "Sidearms",     "count": 3 },
      { "key": "smgs",          "label": "SMGs",         "count": 2 },
      { "key": "shotguns",      "label": "Shotguns",     "count": 1 },
      { "key": "machine_guns",  "label": "Machine Guns", "count": 2 }
    ]
  },
  "buddy_inventory": {
    "total": 85,
    "purchased": 0,
    "vp": 0,
    "items": [
      { "id": "b01", "name": "Ep 5 // 1 Coin",       "image": "/img/valorant/buddies_img_01.png" },
      { "id": "b02", "name": "Pocket Sized Sheriff", "image": "/img/valorant/buddies_img_02.png" }
    ]
  }
}
```

**Filter semantics** — `skin_filters` is the *menu*, not the *predicate*. The frontend renders one checkbox per option, then filters `skin_inventory.items` client-side via `(items.filter(i => selectedRarities.has(i.rarity) && selectedWeapons.has(i.weapon_type)))`. The API doesn't apply the filter — the entire item list ships in one payload (typical accounts cap around ~70 skins, so a few KB).

**`rarity` enum**

| Key | HTML icon | Display label |
|---|---|---|
| `ultra` | `sidebar_icon01.svg` | Ultra |
| `exclusive` | `sidebar_icon02.svg` | Exclusive *(the HTML reference labels this as "Ultra" too, but the canonical Valorant tier name is "Exclusive")* |
| `premium` | `sidebar_icon03.svg` | Premium |
| `deluxe` | `sidebar_icon04.svg` | Deluxe |
| `select` | `sidebar_icon05.svg` | Select |

**`weapon_type` enum** — `melee | rifles | sniper_rifles | sidearms | smgs | shotguns | machine_guns`. Add new keys here when Valorant ships a new weapon category; remember to seed an option in `skin_filters.weapon_types` too.

**Counts** — `skin_filters.weapon_types[].count` should equal `items.filter(i => i.weapon_type === key).length`. Laravel can compute this in `ProductDetailResource::toArray` via `groupBy('weapon_type')`. The frontend trusts the API value but the mock recomputes it from the items array.

---

### `profile_info` shape (page-1 only)

```jsonc
{
  "region": "Asia Pacific",
  "region_icon": "/img/icons/server_icon.svg",
  "profile_image": "/img/images/profile_img.jpg",
  "profile_stats": [
    { "icon": "/img/icons/profile_icon01.svg", "value": "48" },
    { "icon": "/img/icons/profile_icon02.svg", "value": "25" },
    { "icon": "/img/icons/profile_icon03.svg", "value": "8500" }
  ],
  "inventory_value": {
    "label": "Inventory value",
    "value": "~35800 VP",
    "icon": "/img/icons/valorant.svg"
  },
  "ranks": [                                  // exactly 3 cards in the HTML
    { "image": "/img/icons/profile_rank01.png", "title": "Gold 1",   "label": "Current rank - V26 ACT II" },
    { "image": "/img/icons/profile_rank02.png", "title": "Silver 3", "label": "Previous act rank - V25 ACT V" },
    { "image": "/img/icons/profile_rank03.png", "title": "Gold 2",   "label": "Maximum rank - V25 ACT II" }
  ],
  "features": [                                // 1-N chips
    { "icon": "mail",  "title": "Mail Access" },
    { "icon": "clock", "title": "Last Active 31.03.2026" },
    { "icon": "phone", "title": "Phone Number Linked", "red": true }
  ]
}
```

`features[].icon` is an enum (`mail`, `clock`, `phone`) — the React component supplies the matching SVG. `red: true` adds the `red_icon` modifier class for the danger-coloured Phone Number chip.

---

## Section grouping

Each Product carries a `section: { slug, label, order }` object that names the catalog group it belongs to. The frontend buckets `/shop/[game]` results by `section.slug`, sorts buckets by `section.order`, and renders each section header from `section.label`. This decouples grouping from `account_type` (necessary for Legends, where all three sections share `account_type='standard'` but split on server region).

| Game | Sections (slug → label, order) | Source HTML |
|---|---|---|
| valorant | `verified` → `VERIFIED` (1)<br>`inactive_exclusive` → `INACTIVE EXCLUSIVE ACCOUNTS` (2) | shop.html |
| fortnite | `nfa_random` → `NFA Random Skins` (1)<br>`nfa_guaranteed` → `NFA Guaranteed Skins` (2)<br>`nfa_inactive` → `NFA Inactive Accounts` (3) | shop-2.html |
| legends | `euw` → `Europe West (EUW)` (1)<br>`tr` → `Turkey (TR)` (2)<br>`las` → `Latin America South (LAS)` (3) | shop-3.html |

Laravel can either store `section` as a JSON column on `products`, or split it into a normalized `sections` table (FK by id). Either works — the wire shape doesn't change.

---

## Detail page layout — driven by account_type

The frontend ships **one** product detail page at `/shop/[game]/[slug]`. It picks the body layout deterministically from `account_type` via `accountTypeToLayout()` in [`lib/api/layout.ts`](../../lib/api/layout.ts). Laravel should mirror this map (e.g. as a `Product::detailLayout()` accessor) if any backend code needs to branch on layout.

| `account_type` | `DetailLayout` | Body component | Source HTML |
|---|---|---|---|
| `inactive_exclusive` | `rich` | `ValorantAgentsBody` — 28 agent tab thumbnails with role icons, big preview pane, plus `profile_info` sidebar | shop-details.html |
| `verified` / `nfa_random` / `nfa_guaranteed` | `simple_two` | `SimpleDescriptionBody` (single image variant) — `description_sections` block, no Swiper | shop-details-2.html |
| `standard` | `simple_three` | `SimpleDescriptionBody` (carousel variant) — same body, but always renders the Swiper thumb carousel even if there's only one image | shop-details-3.html |
| `nfa_inactive` | `fortnite_four` | `FortniteLockerBody` — Account / Locker / Seasons / PVE four-tab layout with `account_level`, `account_stats`, `locker`, `seasons` data | shop-details-4.html |

The header block (title, price, country, categories, guarantee, add-to-cart) is shared across all four layouts. The related-products slider renders **only** on the two simple layouts (`simple_two` + `simple_three`) — `rich` and `fortnite_four` omit it (matches the source HTML).

---

## Image assets

The frontend serves images from `99accs-app/public/img/<bucket>/...`. Asset paths in JSON should start with `/img/`. **Laravel must mirror the same paths**: either store assets on the same CDN domain and serve them under `/img/...`, or expose absolute URLs via a `images_base_url` env that the frontend prepends.

Live folders the mocks reference (these must exist in production storage):

```
/img/valorant/  → exclusive_img_01-08.jpg, skin_img_01-06.png, buddies_img_01-47.png, skin_color_img01-04.jpg, skin_item_01-16.png, skin_item_bg*.png/jpg
/img/fortnite/  → fortnite_img01-08.png, guarnteed_img01-04.png, exclusive_img_01-04.jpg, locker_img_01-31.jpg, shop_details.jpg, seasons-img.jpg, polygon.svg, polygon_01.png, polygon_02.svg, gun_01.svg, gun_02.svg
/img/legends/   → legends_img01-10.jpg
/img/images/    → shop_details_img01.png, tab_img_01-24.png, profile_img.jpg, agent_nav_bg01.jpg, agent_nav_bg_shape01-02.png, agent_nav_img01-02.png, border_left.svg, border_right.svg, title_shape.svg
/img/icons/     → us.png, eu.png, br.png, exclusive_icon_01-08.png, euw_icon01-06.png, profile_icon01-03.svg, profile_rank01-03.png, server_icon.svg, valorant.svg, fortnite.svg, league.svg, shop-details-icon01.png, shop_details_icon01-03.png, agent_tab_icon01-02.svg, guarantee_icon.svg
```

**Validation in `StoreProductRequest`**: every URL string in `images`, `badge_icon`, `country.flag`, `profile_info.profile_image`, `profile_info.ranks[*].image`, `locker.tabs[*].groups[*].items[*].image`, `seasons.history_background`, `account_level` references, etc. should be checked against the storage bucket on save to catch broken paths early.

---

## Suggested Laravel migration

```php
Schema::create('products', function (Blueprint $t) {
    $t->id();
    $t->string('slug')->unique();
    $t->enum('game', ['valorant', 'fortnite', 'legends'])->index();
    $t->enum('account_type', [
        'verified', 'inactive_exclusive',
        'nfa_random', 'nfa_guaranteed', 'nfa_inactive',
        'standard',
    ])->index();
    // Section group on the catalog page. For Valorant + Fortnite, section_slug
    // mirrors account_type 1:1; for Legends it's `euw` / `tr` / `las` (all
    // share account_type='standard'). section_label + section_order are
    // bundled here as a denormalized JSON to keep section UX flexible without
    // a join table — promote to a `sections` table if you need multi-locale.
    $t->string('section_slug', 32)->index();
    $t->string('section_label');
    $t->unsignedTinyInteger('section_order')->default(1);
    $t->string('title');
    $t->decimal('price', 10, 2);
    $t->decimal('price_max', 10, 2)->nullable();
    $t->decimal('old_price', 10, 2)->nullable();
    $t->string('country_code', 8)->index();           // empty string = no chip rendered
    $t->string('country_flag')->nullable();
    $t->string('country_class_modifier', 16)->nullable();  // CSS class override (LAS uses class "ap")
    $t->json('categories');
    $t->json('images');
    $t->boolean('has_gallery')->default(false);       // thumb gallery popup
    $t->unsignedTinyInteger('discount_percent')->nullable();
    $t->string('badge_icon')->nullable();
    $t->enum('region', ['na', 'eu', 'apac', 'latam', 'br'])->nullable()->index();
    $t->string('rank')->nullable()->index();
    $t->json('agents')->nullable();
    $t->json('skins')->nullable();
    $t->json('buddies')->nullable();
    $t->text('description');
    $t->json('specs');
    $t->unsignedInteger('stock')->default(0);

    // Detail-only — nullable, hydrated by admin form or 3rd-party-sync job
    $t->json('agents_detailed')->nullable();
    $t->unsignedSmallInteger('agents_count')->nullable();
    $t->json('profile_info')->nullable();
    $t->json('skin_inventory')->nullable();         // { total, purchased, vp, items[] }
    $t->json('skin_filters')->nullable();           // { rarities[], weapon_types[] }
    $t->json('buddy_inventory')->nullable();        // { total, purchased, vp, items[] }
    $t->json('account_level')->nullable();
    $t->json('account_stats')->nullable();
    $t->json('locker')->nullable();
    $t->json('seasons')->nullable();
    $t->json('description_sections')->nullable();
    $t->unsignedSmallInteger('min_quantity')->nullable();
    $t->string('last_match_label')->nullable();
    $t->json('guarantee')->nullable();

    // Sync metadata for 3rd-party-sourced products
    $t->string('source_provider')->nullable();   // 'riot' | 'epic' | null
    $t->string('external_id')->nullable();
    $t->timestamp('synced_at')->nullable();

    $t->timestamps();

    $t->fullText(['title', 'description']);
    $t->index(['game', 'price']);
});
```

### ProductController

```php
public function index(Request $r)
{
    $q = Product::query()
        ->select([
            'id', 'slug', 'game', 'account_type',
            'section_slug', 'section_label', 'section_order',
            'title', 'price', 'price_max', 'old_price',
            'country_code', 'country_flag', 'country_class_modifier',
            'categories', 'images', 'has_gallery',
            'discount_percent', 'badge_icon',
            'region', 'rank', 'description', 'specs', 'stock', 'created_at',
        ]); // omit heavy JSON columns from the list query

    if ($r->filled('game'))         $q->where('game', $r->string('game'));
    if ($r->filled('account_type')) $q->where('account_type', $r->string('account_type'));
    if ($r->filled('section'))      $q->where('section_slug', $r->string('section'));
    if ($r->filled('country'))      $q->where('country_code', strtoupper($r->string('country')));
    if ($r->filled('region'))       $q->where('region', $r->string('region'));
    if ($r->filled('rank'))         $q->whereRaw('LOWER(rank) = ?', [strtolower($r->string('rank'))]);
    if ($r->filled('min_price'))    $q->where('price', '>=', $r->float('min_price'));
    if ($r->filled('max_price'))    $q->where('price', '<=', $r->float('max_price'));
    if ($r->filled('search')) {
        $term = '%' . $r->string('search') . '%';
        $q->where(fn ($w) => $w->where('title', 'like', $term)->orWhere('description', 'like', $term));
    }
    $q = match ($r->string('sort')->toString()) {
        'price_asc'  => $q->orderBy('price'),
        'price_desc' => $q->orderByDesc('price'),
        'oldest'     => $q->orderBy('created_at'),
        default      => $q->orderByDesc('created_at'),
    };

    return ProductListResource::collection($q->paginate($r->integer('per_page', 24)));
}

public function show(string $slug)
{
    $p = Product::where('slug', $slug)->firstOrFail();
    // Related products only show on the two simple detail layouts (simple_two
    // + simple_three). Exclude the layouts that don't render a related slider
    // in the source HTML (`rich` → inactive_exclusive, `fortnite_four` → nfa_inactive).
    $related = Product::query()
        ->where('game', $p->game)
        ->where('id', '!=', $p->id)
        ->whereNotIn('account_type', ['inactive_exclusive', 'nfa_inactive'])
        ->inRandomOrder()
        ->limit(6)
        ->get();
    return new ProductDetailResource($p->setRelation('related', $related));
}
```

### ProductDetailResource

Use **two** Resource classes — `ProductListResource` (no detail-only fields) and `ProductDetailResource` (everything). Avoid sending heavy JSON columns in list responses.

```php
class ProductDetailResource extends ProductListResource
{
    public function toArray(Request $r): array
    {
        return array_merge(parent::toArray($r), [
            'agents_detailed'      => $this->agents_detailed,
            'agents_count'         => $this->agents_count,
            'profile_info'         => $this->profile_info,
            'skin_inventory'       => $this->skin_inventory,
            'skin_filters'         => $this->skin_filters,
            'buddy_inventory'      => $this->buddy_inventory,
            'account_level'        => $this->account_level,
            'account_stats'        => $this->account_stats,
            'locker'               => $this->locker,
            'seasons'              => $this->seasons,
            'description_sections' => $this->description_sections,
            'min_quantity'         => $this->min_quantity,
            'last_match_label'     => $this->last_match_label,
            'guarantee'            => $this->guarantee,
            'related'              => ProductListResource::collection($this->whenLoaded('related')),
        ]);
    }
}
```

---

## Data sources

The same `products` table is populated by **two distinct flows**:

1. **3rd-party API sync** (Riot for Valorant agent inventories on page-1 layout, Epic for Fortnite locker/seasons/stats on page-4 layout). A scheduled job + on-demand resync endpoint hits the upstream API, transforms its payload into the `agents_detailed` / `account_stats` / `locker` / `seasons` columns, and writes a row. `source_provider`, `external_id`, `synced_at` track the origin.
2. **Custom admin uploads** (pages 2 + 3). An admin form writes `description_sections`, `min_quantity`, single or multi-image galleries, and a hand-typed `description`. These rows have `source_provider = null`.

The frontend doesn't distinguish them — both flows produce the same Product shape; layout selection happens purely from data shape (`agents_detailed?.length > 0` → page 1, etc.). The Laravel admin should validate which fields are required for each layout variant before saving.

---

## Frontend contract (do not break)

TypeScript types live in [`lib/api/types.ts`](../../lib/api/types.ts). Whenever you add/rename a field:

1. Update the matching interface in `types.ts`.
2. Update the JSON mocks in this folder to keep them faithful examples.
3. If you add a new `categories[].icon` enum value, register an icon component in [`components/icons/index.tsx`](../../components/icons/index.tsx) → `CATEGORY_ICONS`.
4. If you add a new `account_stats[].icon` or `seasons.current.stats[].icon` enum value, add the SVG to the matching component in [`components/product/detail/`](../../components/product/detail/) (FortniteAccountStatIcons / FortniteSeasonIcons / FortniteCurrentSeasonIcons).

---

## Build order — suggested phasing

1. **Migration + Eloquent model + factory** for `products`.
2. **Seeder** that imports the four mock JSON files verbatim (44+ products across the three games). Confirms the Laravel layer can round-trip the exact shape the frontend expects.
3. **ProductListResource + ProductController@index** with the documented query parameters. Wire `/api/products` and verify the Next.js shop list pages render against the Laravel API by flipping `NEXT_PUBLIC_API_BASE_URL`.
4. **ProductDetailResource + ProductController@show** including `related`. Verify all four detail layouts (Valorant agents, Fortnite locker, simple description with single image, simple description with carousel).
5. **Admin CRUD** for the custom-upload flow (page 2 + 3 products). Form needs widgets for `description_sections` (paragraph / list / faq), gallery upload, country code, etc.
6. **3rd-party sync job** for `agents_detailed` (Riot) and `locker`/`seasons`/`account_stats`/`account_level` (Epic). Background queue, retry on transient failures.
7. **Cache layer**: 60–300s on `index`, longer on `show` (key by slug). Invalidate on product upsert.

---

## Switch from mock to live Laravel API

```env
# .env.local — currently
NEXT_PUBLIC_API_BASE_URL=http://localhost:3000/api/mock

# .env.production — once Laravel is live
NEXT_PUBLIC_API_BASE_URL=https://api.99accs.com/api
```

After the live API is verified, the mock route at `app/api/mock/products/route.ts` and the JSON files in this folder can be deleted (or kept as Postman fixtures).
