# NextHospital Backend

Laravel REST API + web admin for the NextHospital management system.

## Requirements

- PHP 8.2+
- MySQL 8+
- Composer 2.x
- Node.js (optional, for Vite assets)

## Quick start

```powershell
cd backend
copy .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan storage:link
```

Configure `.env` with your local database credentials. **Never commit `.env`.**

## Sensitive files (never push to Git)

| Path | Reason |
|------|--------|
| `.env`, `.env.backup` | App keys, DB passwords, API secrets |
| `storage/app/firebase/*.json` | Firebase service account private keys |
| `database/seeders/Generated/` | Production DB snapshots (users, tokens, PHI) |
| `storage/app/public/uploads/` | Patient photos & documents |
| `uploads/` | Runtime user uploads |
| `vendor/` | Install via `composer install` |
| `*.zip` | May bundle secrets or full DB dumps |

See `.gitignore` for the full exclusion list.

## Generated seeders

Reference/lookup seeders under `database/seeders/Generated/` are generated from the live database and excluded from this repo. Regenerate them locally — see `database/seeders/Generated/README.md`.

## License

Proprietary — NextCode Systems / Omanye Clinic.
