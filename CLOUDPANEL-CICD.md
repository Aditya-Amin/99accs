# CloudPanel + GitHub CI/CD — Same Domain Setup

**Server:** 5.161.41.209 | **User:** user99accs  
**Domain:** `backup.99accs.com`  
**Stack:** Laravel 10 (PHP 8.1) + Next.js 16 (Node 20)

---

## Architecture

```
https://backup.99accs.com
        │
        Nginx :443
        │
        ├── /admin, /filament, /livewire, /_ignition  → direct :8080 → PHP-FPM → Laravel
        ├── /api/v1, /api/webhooks, /storage, /css, /js → Varnish → :8080 → PHP-FPM → Laravel
        └── /*  (incl. /api/auth, /api/account, /api/checkout BFF) → PM2 :3000 → Next.js
```

```
/home/user99accs/htdocs/backup.99accs.com/
  cms-backend/       ← Laravel
  99accs-app/        ← Next.js
  .github/           ← CI/CD workflows
```

---

## Quick Setup Checklist

- [ ] Part 1 — Create PHP site in CloudPanel
- [ ] Part 2 — Set root directory to `cms-backend/public`
- [ ] Part 3 — Paste custom Nginx vhost
- [ ] Part 4 — SSH keys (deploy key + pull key)
- [ ] Part 5 — Clone repo on server (sparse checkout)
- [ ] Part 6 — Laravel `.env` + first-run commands
- [ ] Part 7 — Install Node/PM2, build Next.js
- [ ] Part 8 — GitHub Secrets
- [ ] Part 9 — SSL certificate
- [ ] Part 10 — Supervisor (queue worker)
- [ ] Part 11 — Cron job

---

## Part 1 — Create PHP Site in CloudPanel

CloudPanel → **+ Add Site** → **Create PHP Site**

| Field | Value |
|---|---|
| Domain Name | `backup.99accs.com` |
| PHP Version | `8.1` |

Do **not** create a Node.js site.

---

## Part 2 — Root Directory

CloudPanel → `backup.99accs.com` → **Settings** → **Root Directory**

Change from: `backup.99accs.com/public`  
Change to: `backup.99accs.com/cms-backend/public`

---

## Part 3 — Nginx Vhost (Final Working Config)

CloudPanel → `backup.99accs.com` → **Vhost** tab → replace entire contents:

```nginx
server {
  listen 80;
  listen [::]:80;
  listen 443 ssl http2;
  listen [::]:443 ssl http2;
  {{ssl_certificate_key}}
  {{ssl_certificate}}
  server_name backup.99accs.com;
  {{root}}

  {{nginx_access_log}}
  {{nginx_error_log}}

  if ($scheme != "https") {
    rewrite ^ https://$host$uri permanent;
  }

  location ~ /.well-known {
    auth_basic off;
    allow all;
  }

  {{settings}}

  # Admin, Filament, Livewire — bypass Varnish, direct to PHP-FPM (POST must not go through Varnish)
  location ~ ^/(admin|filament|livewire|_ignition)(/|$) {
    proxy_pass http://127.0.0.1:8080;
    proxy_set_header Host $http_host;
    proxy_set_header X-Forwarded-Host $http_host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_redirect off;
    proxy_connect_timeout      720;
    proxy_send_timeout         720;
    proxy_read_timeout         720;
  }

  # Laravel API (v1 + webhooks), storage, Filament static assets — through Varnish (GET caching).
  # IMPORTANT: only /api/v1 and /api/webhooks belong to Laravel. The other /api/*
  # paths (/api/auth, /api/account, /api/checkout) are Next.js BFF route handlers
  # and MUST fall through to the Next.js block below — otherwise Laravel 404s them
  # (e.g. GET /api/auth/me → 404). Do not broaden this back to ^/(api|...).
  location ~ ^/(api/v1|api/webhooks|storage|css|js)(/|$) {
    {{varnish_proxy_pass}}
    proxy_set_header Host $http_host;
    proxy_set_header X-Forwarded-Host $http_host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_hide_header X-Varnish;
    proxy_redirect off;
    proxy_connect_timeout      720;
    proxy_send_timeout         720;
    proxy_read_timeout         720;
  }

  # Next.js — all other routes including /_next/static and /img/
  location / {
    proxy_pass http://127.0.0.1:3000;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $http_host;
    proxy_set_header X-Forwarded-Host $http_host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_redirect off;
    proxy_max_temp_file_size 0;
    proxy_connect_timeout      720;
    proxy_send_timeout         720;
    proxy_read_timeout         720;
    proxy_buffer_size          128k;
    proxy_buffers              4 256k;
    proxy_busy_buffers_size    256k;
    proxy_temp_file_write_size 256k;
  }

  if (-f $request_filename) {
    break;
  }
}

server {
  listen 8080;
  listen [::]:8080;
  server_name backup.99accs.com;
  {{root}}

  try_files $uri $uri/ /index.php?$args;
  index index.php index.html;

  location ~ \.php$ {
    include fastcgi_params;
    fastcgi_intercept_errors on;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    try_files $uri =404;
    fastcgi_read_timeout 3600;
    fastcgi_send_timeout 3600;
    fastcgi_param HTTPS "on";
    fastcgi_param SERVER_PORT 443;
    fastcgi_pass 127.0.0.1:{{php_fpm_port}};
    fastcgi_param PHP_VALUE "{{php_settings}}";
  }

  # Livewire JS is dynamically served by PHP — must fall through to index.php
  location ~ ^/livewire/ {
    try_files $uri /index.php?$args;
  }

  # Static assets — try disk first, fall through to PHP for dynamic assets (e.g. livewire.min.js)
  location ~* ^.+\.(css|js|jpg|jpeg|gif|png|ico|gz|svg|svgz|ttf|otf|woff|woff2|eot|mp4|ogg|ogv|webm|webp|zip|swf|map)$ {
    try_files $uri /index.php?$args;
    add_header Access-Control-Allow-Origin "*";
    expires max;
    access_log off;
  }

  if (-f $request_filename) {
    break;
  }
}
```

Click **Save** — CloudPanel reloads nginx automatically.

**Why two blocks instead of one:**
- Varnish blocks POST requests → admin/Filament/Livewire must bypass it
- Next.js handles `/*` so Filament's `/css/` and `/js/` assets must be explicitly routed to PHP
- `livewire.min.js` is dynamically served by PHP, not a file on disk — needs `try_files` fallback

---

## Part 4 — SSH Keys (one-time server setup)

SSH in: `ssh user99accs@5.161.41.209`

### 4.1 — GitHub Actions deploy key (GitHub → server)

```bash
ssh-keygen -t ed25519 -C "github-actions" -f ~/.ssh/github_deploy -N ""
cat ~/.ssh/github_deploy.pub >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys

# Copy this to GitHub Secret SSH_PRIVATE_KEY
cat ~/.ssh/github_deploy
```

### 4.2 — Server pull key (server → GitHub)

```bash
ssh-keygen -t ed25519 -C "server-pull" -f ~/.ssh/github_pull -N ""
cat ~/.ssh/github_pull.pub   # paste to GitHub → repo → Settings → Deploy keys
```

```bash
cat >> ~/.ssh/config << 'EOF'
Host github.com
  IdentityFile ~/.ssh/github_pull
  StrictHostKeyChecking no
EOF
```

---

## Part 5 — Clone Repo (sparse checkout)

```bash
cd /home/user99accs/htdocs/backup.99accs.com
git init
git remote add origin git@github.com:Aditya-Amin/99accs.git
git fetch origin
git reset --hard origin/main
git sparse-checkout init --cone
git sparse-checkout set cms-backend 99accs-app .github
git sparse-checkout reapply
```

Only `cms-backend/`, `99accs-app/`, and `.github/` will sync — HTML reference files are excluded.

---

## Part 6 — Laravel First-Run

```bash
cd /home/user99accs/htdocs/backup.99accs.com/cms-backend
cp .env.example .env
nano .env
```

Key `.env` values:
```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://backup.99accs.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=your_db
DB_USERNAME=your_user
DB_PASSWORD=your_password

QUEUE_CONNECTION=database
SESSION_DRIVER=file
CACHE_DRIVER=file
FILESYSTEM_DISK=public
```

```bash
php8.1 artisan key:generate --force
php8.1 artisan migrate --force
php8.1 artisan storage:link
php8.1 artisan filament:assets
php8.1 artisan config:cache
php8.1 artisan route:cache
php8.1 artisan view:cache
chmod -R 775 storage bootstrap/cache
```

Create admin user:
```bash
php8.1 artisan make:filament-user
```

---

## Part 7 — Node.js + PM2 + Next.js First-Run

```bash
# Install nvm (no sudo needed)
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash
source ~/.bashrc
nvm install 20
nvm use 20

# Install PM2
npm install -g pm2

# Create env file
nano /home/user99accs/htdocs/backup.99accs.com/99accs-app/.env.local
```

`.env.local`:
```dotenv
# Public URL — used by the browser (goes through Cloudflare/Varnish).
NEXT_PUBLIC_API_BASE_URL=https://backup.99accs.com/api/v1
NEXT_PUBLIC_USE_MOCK=false

# Server-only URL — used by SSR + BFF route handlers. Points at the local
# PHP-FPM server block (:8080) so server-to-server calls bypass Cloudflare's
# bot challenge and Varnish. Same host, so this is fast and never leaves the box.
API_INTERNAL_BASE_URL=http://127.0.0.1:8080/api/v1
```

> **Why:** Cloudflare's bot challenge ("Just a moment…") returns HTML to
> server-to-server fetches, so SSR can't read the API over the public URL. Since
> Next.js and Laravel share the host, SSR hits `127.0.0.1:8080` directly. The
> `:8080` server block sets `HTTPS "on"`, so Laravel still builds correct https
> URLs. Rebuild (`npm run build`) after adding this — it's read at runtime, but
> rebuild + `pm2 restart` ensures the process picks it up.

```bash
cd /home/user99accs/htdocs/backup.99accs.com/99accs-app
npm ci
npm run build
pm2 start npm --name "99accs" -- start -- -p 3000
pm2 save
pm2 startup   # run the printed command to enable auto-start on reboot
```

---

## Part 8 — GitHub Secrets

GitHub repo → **Settings → Secrets and variables → Actions**

| Secret | Value |
|---|---|
| `SSH_PRIVATE_KEY` | Output of `cat ~/.ssh/github_deploy` (step 4.1) |
| `SERVER_HOST` | `5.161.41.209` |
| `SERVER_USER` | `user99accs` |

---

## Part 9 — SSL

CloudPanel → `backup.99accs.com` → **SSL/TLS** → **Actions → New Let's Encrypt Certificate**

---

## Part 10 — Queue Worker (Supervisor)

```bash
sudo apt-get install -y supervisor
sudo nano /etc/supervisor/conf.d/99accs-worker.conf
```

```ini
[program:99accs-worker]
command=php8.1 /home/user99accs/htdocs/backup.99accs.com/cms-backend/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=user99accs
numprocs=1
redirect_stderr=true
stdout_logfile=/home/user99accs/htdocs/backup.99accs.com/cms-backend/storage/logs/worker.log
```

```bash
sudo supervisorctl reread && sudo supervisorctl update
sudo supervisorctl start 99accs-worker:*
```

---

## Part 11 — Cron Job

CloudPanel → `backup.99accs.com` → **Cron Jobs** → Add:

```
* * * * *   php8.1 /home/user99accs/htdocs/backup.99accs.com/cms-backend/artisan schedule:run >> /dev/null 2>&1
```

---

## CI/CD Workflows

### `.github/workflows/deploy-laravel.yml`
Triggers on push to `cms-backend/**`

```yaml
name: Deploy Laravel

on:
  push:
    branches: [main]
    paths:
      - 'cms-backend/**'

jobs:
  deploy:
    name: SSH Deploy → Laravel
    runs-on: ubuntu-latest
    steps:
      - name: Deploy via SSH
        uses: appleboy/ssh-action@v1.0.3
        with:
          host: ${{ secrets.SERVER_HOST }}
          username: ${{ secrets.SERVER_USER }}
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script: |
            set -e
            cd /home/user99accs/htdocs/backup.99accs.com
            git pull origin main
            cd cms-backend
            php8.1 /usr/local/bin/composer install --no-dev --optimize-autoloader
            php8.1 artisan migrate --force
            php8.1 artisan optimize:clear
            php8.1 artisan config:cache
            php8.1 artisan route:cache
            php8.1 artisan view:cache
            php8.1 artisan filament:assets
            echo "Laravel deployed OK"
```

### `.github/workflows/deploy-nextjs.yml`
Triggers on push to `99accs-app/**`

```yaml
name: Deploy Next.js

on:
  push:
    branches: [main]
    paths:
      - '99accs-app/**'

jobs:
  deploy:
    name: SSH Deploy → Next.js
    runs-on: ubuntu-latest
    steps:
      - name: Deploy via SSH
        uses: appleboy/ssh-action@v1.0.3
        with:
          host: ${{ secrets.SERVER_HOST }}
          username: ${{ secrets.SERVER_USER }}
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script: |
            set -e
            export NVM_DIR="$HOME/.nvm"
            source "$NVM_DIR/nvm.sh"
            cd /home/user99accs/htdocs/backup.99accs.com
            git pull origin main
            cd 99accs-app
            npm ci
            npm run build
            pm2 restart 99accs || pm2 start npm --name "99accs" -- start -- -p 3000
            pm2 save
            echo "Next.js deployed OK"
```

---

## Verify Everything Works

```bash
# Check PM2
pm2 status

# Check nginx config
sudo nginx -t

# Check PHP-FPM
sudo systemctl status php8.1-fpm
```

Test URLs:
- `https://backup.99accs.com` → Next.js frontend
- `https://backup.99accs.com/admin` → Filament dashboard
- `https://backup.99accs.com/api/v1/...` → Laravel API

---

## Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| 502 on `/` | PM2 crashed | `pm2 logs 99accs` |
| 502 on `/api/` | Varnish/PHP-FPM down | `sudo systemctl status varnish php8.1-fpm` |
| 405 on admin login | POST going through Varnish | Verify admin block uses `proxy_pass http://127.0.0.1:8080` (not `{{varnish_proxy_pass}}`) |
| Livewire JS 404 | Static block catching `.js` before PHP | Verify `location ~ ^/livewire/` block is in the 8080 server |
| No CSS on `/admin` | `/css/filament/` routing to Next.js | Verify `css` and `js` are in the Varnish location block |
| CI: `not a git repo` | Repo not cloned on server | Re-run Part 5 clone commands |
| CI: `php version 8.4` | System CLI PHP is 8.4 | Workflow uses `php8.1` explicitly — check workflow file |
| CI: nvm not found | nvm not installed on server | Run Part 7 nvm install on server |

### Cloudflare note
If using Cloudflare, disable **Rocket Loader** (Speed → Optimization → Rocket Loader → Off) — it breaks Alpine.js/Livewire initialization order.
