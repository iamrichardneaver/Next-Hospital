# Bootstrap cache (server-generated only)

Do **not** commit or upload `*.php` files from this directory.

They are created on the deployment server by:

- `composer install` / `php artisan package:discover` → `packages.php`, `services.php`
- `php artisan config:cache` → `config.php`
- `php artisan route:cache` → `routes-v7.php`

Files generated on Windows/XAMPP contain `C:\xampp` paths and will cause HTTP 500 on Linux.

On the server, run `scripts/deploy-linux.sh` or delete `bootstrap/cache/*.php` and run `composer install --no-dev` before `php artisan config:cache`.
