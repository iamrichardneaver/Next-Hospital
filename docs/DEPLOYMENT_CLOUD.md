# Cloud Deployment Checklist (Linux)

Deploy the Laravel backend from XAMPP/Windows to a Linux cloud server (domain root or subdirectory, HTTPS).

## Critical: never upload from Windows/XAMPP

These directories and files **must be generated on the Linux server**. Uploading them from local dev causes hardcoded `C:\xampp`, `C:\inetpub`, or `/Applications/XAMPP` paths and broken autoloaders.

| Do **not** upload from Windows | Generate on server instead |
|--------------------------------|----------------------------|
| `vendor/` | `composer install --no-dev --optimize-autoloader` |
| `bootstrap/cache/*.php` | Deleted before install; recreated by `config:cache`, `package:discover` |
| `.env` | Copy `.env.example` on server and edit production values |
| `storage/logs/*`, compiled views/sessions | Created at runtime |
| `node_modules/`, `public/build/` | `npm ci && npm run build` (if frontend assets are built) |
| `public/storage` (symlink or folder from Windows) | `php artisan storage:link` |

### Upload these source files

- `app/`, `bootstrap/app.php`, `bootstrap/providers.php`, `config/`, `database/`, `public/` (except `public/storage` symlink), `resources/`, `routes/`, `scripts/`, `composer.json`, `composer.lock`, `.env.example`, `.htaccess` files
- `storage/app/` user uploads and branding you need to migrate (optional; often synced separately)
- Empty `storage/` skeleton (framework subdirs with `.gitignore` only)

### One-command deploy on the server

From the backend project root on Linux/Plesk:

```bash
chmod +x scripts/deploy-linux.sh
./scripts/deploy-linux.sh
```

The script runs: clear `bootstrap/cache/*.php` → `composer install --no-dev` → `key:generate` (if needed) → `storage:link` → `migrate --force` → `config:cache` / `route:cache` / `view:cache` → `deploy:check`.

Verify deployment safety anytime:

```bash
php artisan deploy:check
```

## 1. Server requirements

- PHP 8.1+ with extensions: **gd**, mbstring, xml, curl, zip, mysql/pdo_mysql, fileinfo
- Composer, MySQL/MariaDB
- Web server (Apache/Nginx) pointing document root to `backend/public`

Verify GD:

```bash
php -m | grep -i gd
```

## 2. Environment (`.env`)

Create `.env` on the server from `.env.example`. **Do not copy your Windows `.env` wholesale** — especially `DB_SOCKET`, `APP_URL`, and `APP_KEY`.

| Variable | Cloud example | Notes |
|----------|---------------|-------|
| `APP_ENV` | `production` | Not `local` on live servers |
| `APP_DEBUG` | `false` | |
| `APP_KEY` | *(generated)* | Run `php artisan key:generate` on server |
| `APP_URL` | `https://hospital.example.com` | **Must match the browser URL** (include `/public` or subdirectory path if used) |
| `ASSET_URL` | *(optional)* | CDN or alternate asset host; defaults to `APP_URL` |
| `FORCE_HTTPS` | `true` | When TLS terminates at load balancer |
| `TRUSTED_PROXIES` | `*` or proxy IPs | Required for correct HTTPS detection behind LB |
| `DB_SOCKET` | *(empty)* | Leave empty on Linux/Plesk; use `DB_HOST` TCP |
| `FILESYSTEM_DISK` | `local` | Default; S3 optional via `config/filesystems.php` |

After editing `.env`:

```bash
php artisan config:clear
php artisan cache:clear
```

## 3. Storage symlink (Linux)

Uploaded files live in `storage/app/public`. The web server serves them via `public/storage`.

```bash
php artisan storage:link
```

On Windows/XAMPP a junction may be created automatically in local dev. On Linux you **must** run `storage:link` once per deploy.

Ensure permissions:

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache
chmod -R a+rX storage/app/public
```

### Plesk: logo/favicon 403 Forbidden

If branding images return **403** at URLs like `/public/storage/branding/logo.png` even after `storage:link` and correct permissions, the cause is usually:

1. **Apache `FollowSymLinks` disabled** or Plesk nginx serving static files and not following the `public/storage` symlink.
2. **`public/storage` is a real directory** (not a symlink) with wrong ownership.
3. **Parent directory not executable** — web user can read the file but cannot traverse `storage/app/public/branding/`.

This project ships a **Laravel fallback route** (`branding.file`) that serves files from `storage/app/public/branding/` when the symlink path is blocked. Branding URLs still look like `/storage/branding/{filename}`.

**One-time server steps (Plesk):**

```bash
cd /path/to/backend
php artisan storage:link
php artisan route:clear
php artisan config:clear
chown -R <plesk-user>:psacln storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache
chmod -R a+rX storage/app/public
```

Verify `public/storage` is a **symlink**, not a folder:

```bash
ls -la public/storage
# should show: storage -> ../storage/app/public
```

If it is a directory, remove it and re-run `php artisan storage:link`.

**Plesk panel (if 403 persists):**

- **Apache & nginx Settings** → disable “Serve static files directly by nginx” *or* add a location that passes `/storage/branding/` to PHP.
- Ensure **PHP** runs as the same user that owns `storage/` (typical Plesk: domain system user).

**Smoke test:**

```bash
curl -I "https://your-domain.com/public/storage/branding/YOUR_LOGO_FILE.png"
# Expect: HTTP/1.1 200 OK, Content-Type: image/png
```

Deployed `.htaccess` rules (in `public/.htaccess`):

- **No `Options` directive.** Plesk's default vhost `AllowOverride` does not include `Options`, so any bare `Options` line (e.g. `Options +FollowSymLinks`) in `.htaccess` triggers `Option FollowSymLinks not allowed here` and a hard 500 on every request. The file is now portable; do not re-add `Options` directives.
- Rewrite `storage/branding/{file}` → `index.php` so Laravel serves the file even when Apache cannot follow the storage symlink.

`storage/app/public/.htaccess` grants read access inside the public disk (visible via the symlink as `public/storage/.htaccess`).

## 4. Post-deploy Artisan

Prefer the deploy script (see top of this doc). Manual equivalent:

```bash
find bootstrap/cache -maxdepth 1 -type f -name '*.php' -delete
composer install --no-dev --optimize-autoloader
php artisan key:generate --force   # first deploy only
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan deploy:check
```

## 5. URLs and branding

- **Web UI logos/favicons**: `BrandingSetting` uses `CrossPlatformService::getBrandingFileUrl()` → route `branding.file` (`GET /storage/branding/{filename}`). Works on Plesk when direct symlink serving returns 403.
- **Mobile/API branding**: `SettingsService::getBrandingSettings()` returns full `logo_url`, `favicon_url`, `mobile_logo_url` using the same branding URL helper.
- **API base URL**: Set **Admin → Settings → API** `mobile_api_url` and `frontend_api_url` to `https://your-domain.com/api` (or include subdirectory path). Defaults derive from `APP_URL`.

## 6. PDF generation

- Invoice/lab/cashier PDFs embed logos via **base64** or **absolute `storage_path()`** — never hardcoded `localhost` URLs.
- **Radiology PDFs** require **PHP GD** for embedded scan images. If GD is missing, a clear error is returned instead of a fatal crash.
- Install: `sudo apt install php-gd` (adjust PHP version), then restart PHP-FPM/Apache.

## 7. Radiology images

Files are stored under `storage/app/public/radiology/…` and served via Laravel routes (`radiology.images.serve`) — no direct Apache path dependency.

## 8. HTTPS behind load balancer

Set in `.env`:

```
FORCE_HTTPS=true
TRUSTED_PROXIES=*
```

`bootstrap/app.php` trusts `X-Forwarded-*` headers so `url()`, `asset()`, and API URLs use `https://`.

## 9. Smoke tests after deploy

- [ ] Sidebar / login page logo loads
- [ ] Settings → branding preview images load
- [ ] Generate invoice PDF (logo visible)
- [ ] Generate radiology PDF with images (GD enabled)
- [ ] Mobile app bootstrap API returns correct `api_url` and branding image URLs
- [ ] Upload patient photo / store item image and confirm display

## 10. XAMPP local (unchanged)

Keep:

```
APP_URL=http://localhost/nexthospital/backend/public
APP_ENV=local
```

Storage junction under `public/storage` is created automatically on Windows local dev.
