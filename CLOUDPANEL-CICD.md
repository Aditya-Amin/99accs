# CloudPanel + GitHub CI/CD — Same Domain Setup

**Server:** 5.161.41.209 | **User:** user99accs  
**Domain:** `backup.99accs.com` (both frontend + API)  
**Stack:** Laravel 10 (PHP 8.1) + Next.js 16 (Node 20)

---

## Architecture

```
https://backup.99accs.com
        │
        Nginx port 443 (CloudPanel manages TLS + vhost)
        │
        ├── /api/*     ─┐
        ├── /admin/*    ├─→ Varnish → Nginx port 8080 → PHP-FPM → Laravel
        ├── /storage/* ─┘                               (root: cms-backend/public)
        │
        └── /*              → PM2 port 3000 → Next.js (direct, bypasses Varnish)
```

```
/home/user99accs/htdocs/backup.99accs.com/
  cms-backend/       ← Laravel (monorepo subfolder)
  99accs-app/        ← Next.js (monorepo subfolder)
```

One domain, one site, one git clone, two processes.

---

## Part 1 — CloudPanel: Create ONE PHP Site

CloudPanel → **+ Add Site** → **Create PHP Site**

| Field | Value |
|---|---|
| Domain Name | `backup.99accs.com` |
| PHP Version | `8.1` |

This creates the htdocs folder and sets up the default PHP nginx vhost.

> Do **not** create a Node.js site — you'll add the Next.js proxy block manually into the PHP site's vhost.

---

## Part 2 — CloudPanel Root Directory

CloudPanel → `backup.99accs.com` → **Settings** tab → **Root Directory**:

Change from: `backup.99accs.com/public`  
Change to: `backup.99accs.com/cms-backend/public`

This makes CloudPanel's `{{root}}` placeholder resolve to Laravel's `public/` folder in the PHP-FPM block (port 8080).

---

## Part 3 — Custom Nginx Vhost

CloudPanel uses a **two-server-block** architecture:
- **Port 443** — TLS termination, routes to Varnish cache or directly to 8080
- **Port 8080** — actual PHP-FPM server (never hit directly by the browser)

CloudPanel → `backup.99accs.com` → **Vhost** tab → replace the entire contents with the config below.  
Keep all `{{placeholders}}` exactly as shown — CloudPanel fills them in automatically.

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

  # Laravel — API, Filament admin, storage, livewire → Varnish → 8080 → PHP-FPM
  location ~ ^/(api|admin|storage|livewire|filament|_ignition)(/|$) {
    {{varnish_proxy_pass}}
    proxy_set_header Host $http_host;
    proxy_set_header X-Forwarded-Host $http_host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_hide_header X-Varnish;
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

  location ~* ^.+\.(css|js|jpg|jpeg|gif|png|ico|gz|svg|svgz|ttf|otf|woff|woff2|eot|mp4|ogg|ogv|webm|webp|zip|swf|map)$ {
    add_header Access-Control-Allow-Origin "*";
    expires max;
    access_log off;
  }

  if (-f $request_filename) {
    break;
  }
}
```

**What changed vs the CloudPanel default:**
- The original single `location /` (pointing to Varnish) is replaced with two blocks:
  - Regex block for Laravel paths — keeps `{{varnish_proxy_pass}}` (Varnish → 8080 → PHP-FPM)
  - Catch-all `location /` — proxies directly to Next.js PM2 on port 3000
- The static assets `location ~*` block is removed from the 443 server — keeping it would cause Next.js images (`/img/`) and JS chunks (`/_next/`) to 404 since they don't exist on disk at `{{root}}`
- The 8080 server block is **unchanged** — it handles Laravel only, with its own static asset caching

After editing click **Save** — CloudPanel reloads nginx automatically.

Verify the config is valid:
```bash
sudo nginx -t
```

---

## Part 4 — First Server Setup

SSH in:
```bash
ssh user99accs@5.161.41.209
```

### 4.1 Generate GitHub Actions deploy key (server → GitHub Actions can SSH in)

```bash
ssh-keygen -t ed25519 -C "github-actions" -f ~/.ssh/github_deploy -N ""
cat ~/.ssh/github_deploy.pub >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys

# Copy this entire output to GitHub Secret SSH_PRIVATE_KEY
cat ~/.ssh/github_deploy
```

### 4.2 Generate server pull key (server → GitHub can pull code)

```bash
ssh-keygen -t ed25519 -C "server-pull" -f ~/.ssh/github_pull -N ""
cat ~/.ssh/github_pull.pub   # paste this to GitHub → Settings → Deploy keys
```

Add to `~/.ssh/config`:
```bash
cat >> ~/.ssh/config << 'EOF'
Host github.com
  IdentityFile ~/.ssh/github_pull
  StrictHostKeyChecking no
EOF
```

### 4.3 Clone the monorepo (once)

```bash
cd /home/user99accs/htdocs/backup.99accs.com
git clone git@github.com:YOUR_ORG/YOUR_REPO.git .
```

This puts `cms-backend/`, `99accs-app/`, and `.github/` all in the htdocs root.

---

## Part 5 — Laravel Setup

```bash
cd /home/user99accs/htdocs/backup.99accs.com/cms-backend

composer install --no-dev --optimize-autoloader

# Create .env — edit with your DB, mail, Stripe credentials
cp .env.example .env
nano .env

php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --class=PaymentGatewaySeeder --force
php artisan storage:link
php artisan filament:assets
php artisan config:cache
php artisan route:cache
php artisan view:cache

chmod -R 775 storage bootstrap/cache
```

**`.env` key values for same-domain setup:**
```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://backup.99accs.com
FRONTEND_URL=https://backup.99accs.com

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

---

## Part 6 — Next.js Setup + PM2

```bash
cd /home/user99accs/htdocs/backup.99accs.com/99accs-app

# Create env file
nano .env.local
```

**`.env.local` for same-domain:**
```dotenv
# Client-side (browser) calls — same domain, so no CORS issue
NEXT_PUBLIC_API_BASE_URL=https://backup.99accs.com/api/v1

# Server-side (SSR) calls — use local address to avoid the Nginx round-trip
LARAVEL_API_BASE_URL=http://127.0.0.1/api/v1

NEXT_PUBLIC_USE_MOCK=false
```

Install, build, start:
```bash
npm ci
npm run build

npm install -g pm2
pm2 start npm --name "99accs" -- start -- -p 3000
pm2 save
pm2 startup   # run the printed command to enable auto-start on reboot
```

---

## Part 7 — Queue Worker (Supervisor)

```bash
sudo apt-get install -y supervisor
sudo nano /etc/supervisor/conf.d/99accs-worker.conf
```

```ini
[program:99accs-worker]
command=php /home/user99accs/htdocs/backup.99accs.com/cms-backend/artisan queue:work --sleep=3 --tries=3 --max-time=3600
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

Allow password-free restart from GitHub Actions:
```bash
sudo visudo -f /etc/sudoers.d/user99accs
# Add this line:
user99accs ALL=(ALL) NOPASSWD: /usr/bin/supervisorctl
```

---

## Part 8 — CloudPanel Cron Job

CloudPanel → `backup.99accs.com` → **Cron Jobs** → Add:

```
* * * * *   php /home/user99accs/htdocs/backup.99accs.com/cms-backend/artisan schedule:run >> /dev/null 2>&1
```

---

## Part 9 — SSL

CloudPanel → `backup.99accs.com` → **SSL/TLS** → **Actions → New Let's Encrypt Certificate**

The cert paths used in the vhost above will be auto-populated after this step.

---

## Part 10 — GitHub Secrets

GitHub repo → **Settings → Secrets and variables → Actions**

| Secret | Value |
|---|---|
| `SSH_PRIVATE_KEY` | Output of `cat ~/.ssh/github_deploy` (from step 4.1) |
| `SERVER_HOST` | `5.161.41.209` |
| `SERVER_USER` | `user99accs` |

---

## Part 11 — GitHub Actions Workflows

Create these files in the repo. Both deploy to the **same server, same directory** — path filters make only the relevant job run.

### `.github/workflows/deploy-laravel.yml`

```yaml
name: Deploy Laravel

on:
  push:
    branches: [main]
    paths:
      - 'cms-backend/**'

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - name: SSH Deploy
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
            composer install --no-dev --optimize-autoloader
            php artisan migrate --force
            php artisan optimize:clear
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            php artisan filament:assets

            sudo supervisorctl restart 99accs-worker:*
            echo "Laravel deployed OK"
```

### `.github/workflows/deploy-nextjs.yml`

```yaml
name: Deploy Next.js

on:
  push:
    branches: [main]
    paths:
      - '99accs-app/**'

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - name: SSH Deploy
        uses: appleboy/ssh-action@v1.0.3
        with:
          host: ${{ secrets.SERVER_HOST }}
          username: ${{ secrets.SERVER_USER }}
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script: |
            set -e
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

## Part 12 — Test the Setup

Push the workflow files and test each path:

```bash
# Test Laravel is working
curl https://backup.99accs.com/api/v1/health   # or any API route

# Test Filament admin
open https://backup.99accs.com/admin

# Test Next.js frontend
open https://backup.99accs.com

# Test media file
open https://backup.99accs.com/storage/...
```

Check PM2 and worker:
```bash
pm2 status
sudo supervisorctl status
```

Check nginx config is valid:
```bash
sudo nginx -t
```

---

## Troubleshooting

**502 Bad Gateway on `/`**  
PM2 crashed or not running. Check: `pm2 logs 99accs`

**502 Bad Gateway on `/api/`**  
Varnish or PHP-FPM is down. Check:
```bash
sudo systemctl status varnish
sudo systemctl status php8.1-fpm
```
The `{{php_fpm_port}}` placeholder is resolved by CloudPanel — no manual port needed.

**404 on `/admin/`**  
The Laravel location regex didn't match. Verify the vhost `location ~ ^/(api|admin|...` block saved correctly.  
Run: `sudo nginx -t` to validate syntax.

**`git pull` fails in CI**  
The server's `~/.ssh/github_pull` key isn't added to GitHub Deploy keys.  
Re-add it: GitHub → repo → Settings → Deploy keys → Add deploy key.

**Storage files 404**  
Run: `php artisan storage:link` inside `cms-backend/`.

**Next.js SSR calls to Laravel fail**  
Use `LARAVEL_API_BASE_URL=https://backup.99accs.com/api/v1` — this always works since the request goes through Nginx on the same server and routes to PHP-FPM correctly.  
The `http://127.0.0.1` shortcut skips TLS but still hits port 80 → Varnish → 8080 → PHP-FPM, which is fine too. Either works.
