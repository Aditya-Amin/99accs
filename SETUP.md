# Local Development Setup

This repo has two apps:

| Folder | Stack | Local URL |
|---|---|---|
| `cms-backend/` | Laravel 10 + Filament 3 admin, Sanctum API, MySQL | `http://127.0.0.1:8000` |
| `99accs-app/` | Next.js 16 (App Router, Turbopack) storefront | `http://localhost:3000` |

The Next app is a **BFF**: the browser talks to Next route handlers (`/api/auth/*`, `/api/checkout/*`, …), and the Next **server** calls the Laravel API. The browser never holds the Sanctum token — it lives in an httpOnly cookie set by Next.

---

## 0. Prerequisites

| Tool | Version | Notes |
|---|---|---|
| PHP | 8.2+ (8.3 recommended) | with extensions: `pdo_mysql`, `mbstring`, `openssl`, `bcmath`, `ctype`, `fileinfo`, `gd` (image handling), `zip` |
| Composer | 2.x | |
| MySQL | 8.0 (or MariaDB 10.6+) | |
| Node.js | 20.x+ | required by Next 16 |
| npm | 10.x+ | |

> ⚠️ **Host consistency matters.** Browsers treat `localhost` and `127.0.0.1` as *different* hosts (separate cookies, separate Node fetch resolution). Pick one and use it everywhere: this guide uses **`127.0.0.1:8000`** for the API and **`localhost:3000`** for the storefront. If you change one, change all of `APP_URL`, `FRONTEND_URL`, and `NEXT_PUBLIC_API_BASE_URL` to match.

---

## 1. Backend — `cms-backend/`

```bash
cd cms-backend

# 1. Dependencies
composer install

# 2. Environment
cp .env.example .env
php artisan key:generate          # REQUIRED — payment-gateway credentials are
                                  # encrypted with APP_KEY; never rotate it casually.
```

Edit `.env` — the keys that matter locally:

```dotenv
APP_NAME="99accs"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
FRONTEND_URL=http://localhost:3000      # used to build password-reset links

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=99accs
DB_USERNAME=root
DB_PASSWORD=

# Dev: write emails to storage/logs/laravel.log instead of sending them.
MAIL_MAILER=log

# Dev: keep these simple. Jobs (image downloads, WooCommerce imports) use the DB
# queue and need a worker running (see step 6).
QUEUE_CONNECTION=database
SESSION_DRIVER=file
CACHE_DRIVER=file
FILESYSTEM_DISK=public
```

```bash
# 3. Create the database (any MySQL client) — name must match DB_DATABASE
#    e.g.  CREATE DATABASE `99accs` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 4. Migrate + seed
php artisan migrate

# Seed payment gateways (required for checkout) + demo catalog (optional):
php artisan db:seed --class=PaymentGatewaySeeder
php artisan db:seed            # full seed: products, sections, skins, etc. (optional, large)

# 5. Storage symlink (Curator media + uploaded images) and Filament assets
php artisan storage:link
php artisan filament:assets

# 6. Create an admin login for the Filament dashboard (/admin)
php artisan make:filament-user
```

Run it (two terminals):

```bash
# Terminal A — the app
php artisan serve --host=127.0.0.1 --port=8000

# Terminal B — the queue worker (processes product-image downloads & imports)
php artisan queue:work
```

- Admin dashboard: **http://127.0.0.1:8000/admin**
- API base: **http://127.0.0.1:8000/api/v1**

### Reading reset / notification emails locally
With `MAIL_MAILER=log`, password-reset links are written to `cms-backend/storage/logs/laravel.log`. Search it for `reset-password?token=` and open the link in your browser (use the plain `&`, not the HTML-encoded `&amp;`).

### Payment gateways
Gateways are **seeded but inactive**. In **Admin → Settings → Payment Gateways**, fill in credentials and toggle **Active**. A gateway only appears at checkout once it's active *and* has its required keys. Stripe needs `secret_key`; Cryptomus needs `merchant_id` + `payment_key`.

---

## 2. Frontend — `99accs-app/`

```bash
cd 99accs-app
npm install
```

Create **`.env.local`**:

```dotenv
# Point at the Laravel API. Use 127.0.0.1 (NOT localhost) — Node's fetch resolves
# localhost to IPv6 ::1 first, which `php artisan serve` doesn't listen on.
NEXT_PUBLIC_API_BASE_URL=http://127.0.0.1:8000/api/v1

# false = use the real Laravel API. true = serve from local mock JSON (no backend).
NEXT_PUBLIC_USE_MOCK=false

# Optional: server-side base used by the auth/checkout BFF route handlers.
# Defaults to NEXT_PUBLIC_API_BASE_URL if omitted — set it the same.
LARAVEL_API_BASE_URL=http://127.0.0.1:8000/api/v1
```

Run it:

```bash
npm run dev      # http://localhost:3000
```

> `NEXT_PUBLIC_*` vars are inlined at startup — **restart `npm run dev`** after editing `.env.local`.

---

## 3. First-run smoke test

1. Backend up on `127.0.0.1:8000`, queue worker running, frontend on `localhost:3000`.
2. Visit `http://localhost:3000` → home + shop should load real data.
3. Register a new account from the header modal → you should land logged-in with your name in the header.
4. In the admin dashboard, the notification bell should show a "New Customer Signed Up" alert (polls every 10s).
5. Add to cart → checkout as guest → you reach the payment page with the active gateway(s).

---

## 4. Common gotchas

| Symptom | Cause / Fix |
|---|---|
| `Request to …8000… timed out` in Next | API not running, or you used `localhost` instead of `127.0.0.1` in `NEXT_PUBLIC_API_BASE_URL`. |
| Admin notifications never appear | Queue worker not running **and** notifications were queued — they now send synchronously, but run `php artisan queue:work` for image/import jobs. |
| Clicking a notification → login page | Host mismatch: log into `/admin` on the **same host** as `APP_URL`. |
| Product images blank | Run the queue worker — `DownloadProductImages` jobs populate them. |
| `Unexpected token '<'` / stale chunks in Next | `rm -rf .next && npm run dev`, then hard-refresh. |
| Filament login styles missing | `php artisan filament:assets && php artisan storage:link`. |

---

## 5. Useful commands

```bash
# Backend
php artisan migrate:fresh --seed     # rebuild DB from scratch (DESTRUCTIVE)
php artisan optimize:clear           # clear config/route/view/event caches
php artisan queue:work               # process queued jobs
php artisan make:filament-user       # add another admin

# Frontend
npm run build && npm run start       # production build locally
npm run lint
```
