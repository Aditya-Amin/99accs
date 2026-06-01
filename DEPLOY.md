# Deployment Guide

Production topology:

```
Browser ──► Vercel (Next.js storefront, BFF)  ──► Hostinger (Laravel API + Filament admin)
            https://99accs.com                     https://api.99accs.com
                                                         │
                                                    MySQL (Hostinger)
```

- **Storefront / BFF** → Vercel (`99accs-app/`)
- **API + Admin dashboard** → Hostinger shared hosting (`cms-backend/`), served from a subdomain whose document root is the Laravel `public/` folder
- The Next **server** calls the Laravel API (server-to-server). The browser only talks to Vercel, so cross-origin CORS is largely a non-issue — but webhooks and media URLs do hit Laravel directly.

> Replace `api.99accs.com` (API) and `99accs.com` (storefront) with your real domains throughout.

---

## Part A — Laravel API on Hostinger (shared hosting)

Hostinger shared plans give you: hPanel, MySQL, selectable PHP version, free SSL, cron jobs, and **SSH** (Premium/Business tiers). SSH + Composer make this far easier — use it if available.

### A1. Create the database
hPanel → **Databases → MySQL**: create a database + user, grant all privileges. Note the **host** (often `localhost`), DB name, user, password.

### A2. Set the PHP version
hPanel → **Advanced → PHP Configuration**: select **PHP 8.2 or 8.3**. Enable extensions: `pdo_mysql`, `mbstring`, `openssl`, `bcmath`, `ctype`, `fileinfo`, `gd`, `zip`.

### A3. Point a subdomain at Laravel's `public/`
This is the key step on shared hosting — Laravel must be served from `public/`, never the project root.

1. hPanel → **Domains → Subdomains**: create `api.99accs.com`.
2. Set its **document root** to the Laravel public folder, e.g. `domains/api.99accs.com/cms-backend/public`. (Hostinger lets you set a custom document root per subdomain — use it instead of dumping files in `public_html`.)
3. Upload the project to `…/cms-backend` (the folder whose `public/` you just pointed at).

**Uploading the code** (pick one):
- **SSH + Git (preferred):** `git clone <repo> cms-backend` then `cd cms-backend`.
- **File Manager / SFTP:** upload everything **except** `vendor/`, `node_modules/`, `.env`, `.git/`.

### A4. Install dependencies (SSH)
```bash
cd ~/domains/api.99accs.com/cms-backend
composer install --no-dev --optimize-autoloader
```
> No SSH? Run `composer install` locally and upload the resulting `vendor/` folder via SFTP.

### A5. Production `.env`
Create `cms-backend/.env` (never commit it). Minimum production values:

```dotenv
APP_NAME="99accs"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.99accs.com
FRONTEND_URL=https://99accs.com        # builds password-reset links → must be the Vercel URL

DB_CONNECTION=mysql
DB_HOST=localhost                       # use the host hPanel shows
DB_PORT=3306
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Real email (Hostinger Email / SMTP) so resets & alerts actually send.
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=no-reply@99accs.com
MAIL_PASSWORD=your_mailbox_password
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=no-reply@99accs.com
MAIL_FROM_NAME="99accs"

# Shared hosting can't keep a daemon alive — run the queue from cron (see A8).
QUEUE_CONNECTION=database
SESSION_DRIVER=file
CACHE_DRIVER=file
FILESYSTEM_DISK=public
LOG_LEVEL=error

# Payments (fill from Stripe / Cryptomus dashboards)
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
CRYPTOMUS_MERCHANT_ID=
CRYPTOMUS_PAYMENT_KEY=
CRYPTOMUS_CALLBACK_URL=https://api.99accs.com/api/webhooks/cryptomus
```

> 🔑 **`APP_KEY` is critical.** Payment-gateway credentials are encrypted with it. Generate it **once** (next step) and never change it on a live DB, or those secrets become undecryptable.

### A6. Initialize the app (SSH)
```bash
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --class=PaymentGatewaySeeder --force   # gateway rows (required)
# Optionally import your real catalog via the WordPress importer in the admin UI
# instead of seeding demo products.

php artisan storage:link        # if symlinks are blocked on your plan, see note below
php artisan filament:assets

# Production caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create the first admin
php artisan make:filament-user
```

> **`storage:link` blocked?** Some shared hosts disallow symlinks. Either create the link via hPanel File Manager, or set the `public` disk to write directly under the web root. Verify uploaded images resolve at `https://api.99accs.com/storage/...`.

### A7. Permissions & SSL
- Make `storage/` and `bootstrap/cache/` writable (755/775 as your host requires).
- hPanel → **SSL**: issue/enable the free Let's Encrypt certificate for `api.99accs.com`. Force HTTPS.
- Because you're behind Hostinger's proxy, Laravel already trusts proxy headers via the default `TrustProxies` middleware (`*`). HTTPS URL generation will work once `APP_URL` is `https://…`.

### A8. Cron: scheduler + queue worker
Shared hosting can't run a persistent `queue:work`. Use cron (hPanel → **Advanced → Cron Jobs**):

```cron
# Laravel scheduler — every minute
* * * * * cd ~/domains/api.99accs.com/cms-backend && php artisan schedule:run >> /dev/null 2>&1

# Drain the queue every minute (image downloads, WooCommerce imports, etc.)
* * * * * cd ~/domains/api.99accs.com/cms-backend && php artisan queue:work --stop-when-empty --max-time=55 >> /dev/null 2>&1
```
> Admin notifications now send **synchronously**, so the bell works without the worker — but product-image downloads and imports need it.

### A9. Webhooks
Configure these public endpoints in the provider dashboards:
- **Stripe** → `https://api.99accs.com/api/webhooks/stripe` (set `STRIPE_WEBHOOK_SECRET`)
- **Cryptomus** → `https://api.99accs.com/api/webhooks/cryptomus`

### A10. Redeploying later
```bash
cd ~/domains/api.99accs.com/cms-backend
git pull                       # or re-upload changed files
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan filament:assets
```

---

## Part B — Next.js storefront on Vercel

### B1. Import the project
1. Push the repo to GitHub/GitLab.
2. Vercel → **Add New → Project** → import the repo.
3. **Root Directory: `99accs-app`** (important — it's a monorepo).
4. Framework preset: **Next.js** (auto-detected). Build command `next build`, output auto. Node version **20.x**.

### B2. Environment variables (Project → Settings → Environment Variables)
Set for **Production** (and Preview if you want PR builds against the API):

```dotenv
NEXT_PUBLIC_API_BASE_URL=https://api.99accs.com/api/v1
LARAVEL_API_BASE_URL=https://api.99accs.com/api/v1
NEXT_PUBLIC_USE_MOCK=false
```
> `NEXT_PUBLIC_*` are inlined at build time — after changing them, **redeploy**.

### B3. Domain
Vercel → **Settings → Domains** → add `99accs.com` (and `www`). Update DNS at your registrar/Hostinger as Vercel instructs. Then make sure Laravel's `FRONTEND_URL` equals this domain so reset links point back to the live storefront.

### B4. Deploy
Push to the production branch (e.g. `main`) → Vercel builds and deploys automatically. Every push redeploys.

---

## Part C — Wiring the two together (don't skip)

| Setting | Must equal |
|---|---|
| Laravel `APP_URL` | `https://api.99accs.com` |
| Laravel `FRONTEND_URL` | `https://99accs.com` (your Vercel domain) — drives reset-link host |
| Vercel `NEXT_PUBLIC_API_BASE_URL` / `LARAVEL_API_BASE_URL` | `https://api.99accs.com/api/v1` |
| Cryptomus `CRYPTOMUS_CALLBACK_URL` | `https://api.99accs.com/api/webhooks/cryptomus` |
| DNS | `api.` → Hostinger; apex/`www` → Vercel |

**CORS:** the Next server (not the browser) calls the API, so storefront traffic doesn't need CORS. If you ever call the API directly from the browser, add the storefront origin to `config/cors.php` and redeploy the backend.

---

## Part D — Production checklist

- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `APP_KEY` generated once and backed up (encrypts gateway secrets)
- [ ] HTTPS forced on `api.` subdomain; `APP_URL` uses `https://`
- [ ] `php artisan migrate --force` run; `PaymentGatewaySeeder` run
- [ ] `storage:link` works → media resolves at `/storage/...`
- [ ] `MAIL_MAILER=smtp` and a test reset email actually arrives
- [ ] Cron entries for `schedule:run` and `queue:work --stop-when-empty` active
- [ ] At least one payment gateway configured **and toggled Active** in the admin
- [ ] Webhook URLs registered in Stripe & Cryptomus
- [ ] One Filament admin user created
- [ ] Vercel env vars set + redeployed; storefront loads live API data
- [ ] `FRONTEND_URL` ↔ Vercel domain match (test the full register → reset → login flow)

---

## Notes & limitations

- **Real-time notifications** currently rely on Filament's 10s polling (no websocket infra). For instant push you'd add Laravel **Reverb + Echo**, which needs a long-running process — not viable on shared hosting. Polling is the right choice for Hostinger shared.
- **Heavy imports** (WooCommerce products/orders, image downloads) run through the DB queue. On shared hosting they progress one cron tick at a time; for large catalogs consider running the import from SSH with a temporary `php artisan queue:work` session.
- If you later outgrow shared hosting, a VPS lets you run a real Supervisor-managed `queue:work` daemon and Reverb — the app needs no code changes for that, only infra/env.
