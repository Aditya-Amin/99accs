# Home Page — Laravel API Guide

This document is the contract between the Next.js frontend and the Laravel backend for the **home page** (`/`).
The Next.js app currently reads from `app/api/mock/home/route.ts`, which serves the JSON files in this folder. When the Laravel API is built, only `NEXT_PUBLIC_API_BASE_URL` needs to change — no frontend code changes.

---

## Endpoint

```
GET  /api/home
```

- **Public** (no auth required)
- **Cache**: safe to cache aggressively (e.g. 5–10 minutes). Admin edits should invalidate cache.

### Response envelope

Wrap in Laravel's standard Resource shape:

```json
{
  "data": {
    "banner":       { ... },
    "about":        { ... },
    "work":         { ... },
    "features":     { ... },
    "testimonials": { ... },
    "cta":          { ... }
  }
}
```

The 6 keys correspond 1:1 to the 6 JSON files in this folder. Each key is described below with its admin-editable fields and the source-of-truth file.

---

## 1. `banner` — Hero section
**Source:** [`banner.json`](./banner.json)

```json
{
  "background_image": "/img/bg/hero_bg.jpg",
  "subtitle": {
    "icon": "shield_check",
    "text": "Your purchase made on the 99Accs platform are protected by us."
  },
  "heading": {
    "prefix": "Your Trusted ",
    "highlight": "Marketplace",
    "suffix": " for Game Accounts"
  },
  "description": "Discover carefully selected high rank accounts and rare skins.",
  "features": [
    { "id": 1, "icon": "trophy",   "text": "High-Quality Accounts" },
    { "id": 2, "icon": "delivery", "text": "Instant Delivery After Payment" },
    { "id": 3, "icon": "warranty", "text": "Free Warranty and Support" }
  ],
  "categories": [
    { "id": 1, "href": "/shop/valorant", "image": "/img/images/hero_cat01.jpg", "alt": "Valorant" },
    { "id": 2, "href": "/shop/fortnite", "image": "/img/images/hero_cat02.jpg", "alt": "Fortnite" },
    { "id": 3, "href": "/shop/legends",  "image": "/img/images/hero_cat03.jpg", "alt": "League of Legends" }
  ]
}
```

| Field | Type | Editable | Notes |
|---|---|---|---|
| `background_image` | string (URL) | yes | Public path or absolute URL |
| `subtitle.icon` | enum | yes | One of: `shield_check`, `trophy`, `delivery`, `warranty` |
| `subtitle.text` | string | yes | |
| `heading.prefix` / `highlight` / `suffix` | string | yes | `highlight` is rendered inside `<span>` (CSS-styled accent color) |
| `description` | string | yes | |
| `features[]` | array | yes | Each: `{ id, icon, text }`. `icon` enum same as above |
| `categories[]` | array | yes | Each: `{ id, href, image, alt }` — links to game shop pages |

**Suggested admin DB schema:**

```php
// migration: home_banner (single row)
$table->string('background_image');
$table->string('subtitle_icon');
$table->string('subtitle_text');
$table->string('heading_prefix');
$table->string('heading_highlight');
$table->string('heading_suffix');
$table->text('description');
$table->json('features');     // array of { id, icon, text }
$table->json('categories');   // array of { id, href, image, alt }
```

---

## 2. `about` — Mission section
**Source:** [`about.json`](./about.json)

```json
{
  "background_image": "/img/bg/about_bg.png",
  "image": "/img/images/about_img.png",
  "title": "We help you play your way and enjoy your games",
  "paragraphs": [
    "Since 2020, 99access has connected dedicated gamers...",
    "We understand gaming culture...",
    "Your in-game progress shapes you as a player..."
  ],
  "stats": {
    "happy_customers": 48345,
    "accounts_sold": 67234
  }
}
```

| Field | Type | Editable | Notes |
|---|---|---|---|
| `background_image` | string (URL) | yes | |
| `image` | string (URL) | yes | Left-column illustration |
| `title` | string | yes | Section heading |
| `paragraphs[]` | string[] | yes | Variable count — admin can add/remove |
| `stats.happy_customers` | int | yes | Animated counter on frontend |
| `stats.accounts_sold` | int | yes | Animated counter on frontend |

> **Note:** `stats` could be a separate read-only auto-computed value if it's derived from the actual user/order tables. Recommended: store as editable settings for now (marketing copy), upgrade later.

---

## 3. `work` — How it works section
**Source:** [`work.json`](./work.json)

```json
{
  "background_image": "/img/bg/work_bg.png",
  "title": "How does it work?",
  "steps": [
    { "id": 1, "num": "01", "title": "Select an Account",       "text": "Browse..." },
    { "id": 2, "num": "02", "title": "Enter Your Email Address", "text": "Browse..." },
    { "id": 3, "num": "03", "title": "Complete Payment",         "text": "Browse..." },
    { "id": 4, "num": "04", "title": "Instant Delivery",         "text": "Browse..." }
  ],
  "images": [
    "/img/images/work_img_01.png",
    "/img/images/work_img_02.png",
    "/img/images/work_img_03.png",
    "/img/images/work_img_04.png"
  ]
}
```

| Field | Type | Editable | Notes |
|---|---|---|---|
| `background_image` | string (URL) | yes | |
| `title` | string | yes | Section heading |
| `steps[]` | array | yes | Each: `{ id, num, title, text }`. The frontend renders the first step as expanded by default |
| `images[]` | string[] | yes | URL list, rendered as a 2x2 grid (4 images expected) |

---

## 4. `features` — Why-choose-us section
**Source:** [`features.json`](./features.json)

```json
{
  "background_image": "/img/bg/features_bg.png",
  "heading": {
    "prefix": "More than ",
    "user_count": 48345,
    "suffix": " gamers rely on 99accs for their online gaming needs"
  },
  "items": [
    { "id": 1, "title": "Trusted Source",  "icon": "/img/icons/features_icon01.png", "text": "..." },
    { "id": 2, "title": "Global Delivery", "icon": "/img/icons/features_icon02.png", "text": "..." },
    { "id": 3, "title": "24/7 Support",    "icon": "/img/icons/features_icon03.png", "text": "..." },
    { "id": 4, "title": "Secure Warranty", "icon": "/img/icons/features_icon04.png", "text": "..." }
  ]
}
```

| Field | Type | Editable | Notes |
|---|---|---|---|
| `background_image` | string (URL) | yes | |
| `heading.prefix` / `suffix` | string | yes | Wraps the user_count |
| `heading.user_count` | int | yes | Rendered inside `<span>` with thousand-separator on frontend |
| `items[]` | array | yes | Each: `{ id, title, icon, text }`. `icon` is an image URL (PNG/SVG), not an icon name |

> **Note:** `features.heading.user_count` is intentionally separate from `about.stats.happy_customers` — they are different copy slots that may show different numbers.

---

## 5. `testimonials` — Customer reviews carousel
**Source:** [`testimonials.json`](./testimonials.json)

```json
{
  "background_image": "/img/bg/testimonial_bg.png",
  "title": "What our customers are saying",
  "items": [
    {
      "id": 1,
      "title": "Good iam recommended this",
      "text":  "Good iam recommended this. everyone...",
      "author": "Best Films",
      "rating": 5
    }
  ]
}
```

| Field | Type | Editable | Notes |
|---|---|---|---|
| `background_image` | string (URL) | yes | |
| `title` | string | yes | Section heading |
| `items[]` | array | yes | Each: `{ id, title, text, author, rating }` |
| `items[].rating` | int 1–5 | yes | Rendered as N filled stars on frontend |

**Suggested admin DB schema (separate table — list grows over time):**

```php
// migration: testimonials
$table->id();
$table->string('title');
$table->text('text');
$table->string('author');
$table->unsignedTinyInteger('rating'); // 1..5
$table->boolean('is_published')->default(true);
$table->unsignedInteger('sort_order')->default(0);
$table->timestamps();
```

The `/api/home` endpoint should return only published testimonials, ordered by `sort_order ASC, created_at DESC`.

---

## 6. `cta` — Join community section
**Source:** [`cta.json`](./cta.json)

```json
{
  "background_image": "/img/bg/cta_bg.jpg",
  "title_lines": ["JOIN THE", "COMMUNITY"],
  "buttons": [
    { "id": 1, "platform": "telegram", "label": "Join telegram", "url": "https://web.telegram.org/" },
    { "id": 2, "platform": "discord",  "label": "Join Discord",  "url": "https://discord.com/" }
  ]
}
```

| Field | Type | Editable | Notes |
|---|---|---|---|
| `background_image` | string (URL) | yes | |
| `title_lines[]` | string[] | yes | Each line rendered with a `<br />` between (typically 2 lines) |
| `buttons[]` | array | yes | Each: `{ id, platform, label, url }` |
| `buttons[].platform` | enum | yes | One of: `telegram`, `discord`. Determines which inline SVG icon is rendered. New platforms require a frontend update |

> **Note:** `cta` is also rendered on `/support/contact` — the same payload is reused.

---

## Laravel implementation sketch

```php
// routes/api.php
Route::get('/home', [\App\Http\Controllers\HomeController::class, 'index']);

// app/Http/Controllers/HomeController.php
public function index() {
    return response()->json([
        'data' => [
            'banner'       => HomeBannerResource::singleton(),
            'about'        => HomeAboutResource::singleton(),
            'work'         => HomeWorkResource::singleton(),
            'features'     => HomeFeaturesResource::singleton(),
            'testimonials' => [
                'background_image' => setting('home.testimonials.background_image'),
                'title'            => setting('home.testimonials.title'),
                'items'            => TestimonialResource::collection(
                    Testimonial::published()->ordered()->get()
                ),
            ],
            'cta'          => HomeCtaResource::singleton(),
        ],
    ]);
}
```

### Storage strategy
- **Banner / About / Work / Features / CTA** → singleton tables OR a JSON-typed `settings` row (`home_settings.payload`). They each have one row.
- **Testimonials** → separate `testimonials` table, rows manageable via admin.

### Admin dashboard (Filament / Nova / custom)
Group as separate "Home Page" pages:
- Home → Banner
- Home → About
- Home → Work Steps (4 rows, sortable)
- Home → Features (4 rows, sortable)
- Home → Testimonials (CRUD list)
- Home → CTA

### Cache invalidation
On any save inside the "Home Page" admin area: `Cache::forget('home_payload')`.

---

## Frontend contract (do not break)

The TypeScript types that consume this payload live in [`lib/api/types.ts`](../../lib/api/types.ts) — interfaces `HomeData`, `HomeBanner`, `HomeAbout`, `HomeWork`, `HomeFeatures`, `HomeTestimonials`, `HomeCta`.

If a new field is added, **update both** the JSON mocks here and the interfaces in `types.ts`.
If a field is renamed or removed, search for it in the codebase first — components destructure these objects directly.

---

## Switch from mock to live Laravel API

```env
# .env.local — currently
NEXT_PUBLIC_API_BASE_URL=http://localhost:3000/api/mock

# .env.production — once Laravel is live
NEXT_PUBLIC_API_BASE_URL=https://api.99accs.com/api
```

After the live API is verified, the mock route at `app/api/mock/home/route.ts` and the JSON files in this folder can be deleted.
