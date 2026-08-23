# COOU SIWES Production Runbook

## Release Checklist

- Set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://your-domain`.
- Use managed MySQL/PostgreSQL for production and Redis for cache/queue where available.
- Set `SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE=true`, and `SESSION_SAME_SITE=lax`.
- Set `BROADCAST_CONNECTION=reverb` and configure `REVERB_*` plus matching `VITE_REVERB_*` values.
- Configure `KORAPAY_PUBLIC_KEY`, `KORAPAY_SECRET_KEY`, `KORAPAY_WEBHOOK_SECRET`, and live webhook URL.
- Run `composer install --no-dev --optimize-autoloader`.
- Run `npm ci && npm run build`.
- Run `php artisan migrate --force`.
- Run `php artisan optimize`.
- Start workers: `php artisan queue:work --tries=3 --backoff=10`.
- Start realtime server: `php artisan reverb:start`.
- Run `php artisan schedule:run` every minute through cron or the hosting scheduler.

## QA Gates

- `composer validate --strict`
- `php artisan migrate:fresh --seed` on staging
- `vendor/bin/phpstan analyse --memory-limit=1G --no-progress`
- `php artisan test`
- `vendor/bin/pint --test`
- `vendor/bin/rector process --dry-run`
- `npm run build`
- `php scripts/export-phase2-snapshots.php`
- `npx playwright test --reporter=dot`
- `composer audit`
- `npm audit`
- `php artisan siwes:health-check --json`

## Smoke Test Checklist

- Public landing page loads over HTTPS.
- Admin, supervisor, and student login pages load.
- Login and OTP verification work for each role.
- Admin can view students, supervisors, tickets, reports, settings, and control center according to permissions.
- Student can view dashboard, payments, notifications, and feedback only for their own profile.
- Supervisor can view assigned students and submit assessment only for assigned students.
- Korapay checkout initialization works in test/live mode as appropriate.
- Korapay webhook endpoint receives signed events and rejects invalid signatures.
- Notification center shows unread counts and mark-read actions.
- Reverb realtime notifications connect in the browser console without authentication errors.
- CSV exports download with expected records.

## Backup And Restore

- Database backups should run at least daily and before every release.
- For local SQLite validation, run `php artisan siwes:backup-sqlite`.
- For production MySQL/PostgreSQL, use managed automated backups plus a pre-release manual snapshot.
- Test restore in staging before launch: restore the latest backup, run `php artisan migrate --force`, then run `php artisan siwes:health-check --json`.
- Store backups outside the application server and restrict access to operations staff.

## Rollback

- Keep the previous deployment artifact and previous `.env` values.
- Put the app in maintenance mode: `php artisan down --secret=<secret>`.
- Restore the previous artifact and run `php artisan optimize:clear && php artisan optimize`.
- If migrations introduced incompatible schema changes, restore the pre-release database snapshot.
- Restart queue workers and Reverb.
- Run the smoke test checklist before taking the app out of maintenance mode.

## Monitoring

- Watch `storage/logs/laravel.log`.
- Watch failed jobs: `php artisan queue:failed`.
- Watch webhook failures and duplicate event counts in payment reports.
- Run `php artisan siwes:health-check --json` from monitoring at least every five minutes.
- Alert on failed jobs, repeated OTP/login throttling, invalid webhook signatures, queue backlog, and storage write failures.
