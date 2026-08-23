# Local Development Setup

## Requirements

- PHP 8.4+
- Composer 2.10+
- Node 22+
- SQLite for local development, with MySQL or PostgreSQL planned for production

## First Run

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm run build
```

On Windows PowerShell, create the SQLite file with:

```powershell
New-Item -ItemType File -Path .\database\database.sqlite
```

## Quality Commands

```bash
composer format:test
composer analyse
composer test
npm run build
php artisan siwes:health-check --json
composer audit
npm audit
```

## Development Server

```bash
composer run dev
```

This starts the Laravel server, queue listener, Reverb realtime server, log tailing, and Vite dev server together.

## Demo Login Credentials

Run `php artisan migrate --seed` or `php artisan db:seed --class=RoleAndPermissionSeeder` to create these local demo accounts.

| Portal | URL | Email | Password |
| --- | --- | --- | --- |
| Super Admin | `/login/admin` | `superadmin@coousiwes.test` | `password` |
| Admin | `/login/admin` | `admin@coousiwes.test` | `password` |
| Supervisor | `/login/supervisor` | `supervisor@coousiwes.test` | `password` |
| Student | `/login/student` | `student@coousiwes.test` | `password` |

OTP is enabled for these demo accounts. In local/testing mode, the OTP challenge accepts the seeded test code `123456`.

## Local Backup Check

For SQLite development databases, create a timestamped backup with:

```bash
php artisan siwes:backup-sqlite
```

Production backup and rollback procedures are documented in `PRODUCTION_RUNBOOK.md`.
