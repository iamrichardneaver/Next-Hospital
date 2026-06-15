# Generated seeders (not committed to Git)

This folder holds **database snapshots** generated from a live `nexthospital` MySQL instance. They are intentionally **excluded from Git** because they may contain:

- Staff emails, phone numbers, and password hashes
- API tokens (`personal_access_tokens`)
- Active session data
- Activity logs and operational records

## Regenerate locally

After cloning the repo and applying migrations to your own database:

```powershell
cd backend
C:\xampp\php\php.exe -d memory_limit=512M scripts\generate_schema_from_db.php
```

Then seed a **separate test database** only (never the operational DB):

```powershell
$env:DB_DATABASE='nexthospital_schema_test'
$env:PERMISSIONS_AUTO_SYNC='false'
C:\xampp\php\php.exe artisan migrate --seed
```

See `database/SCHEMA_REGENERATION_REPORT.md` and `.cursor/rules/schema-sync.mdc` in the monorepo for full workflow.
