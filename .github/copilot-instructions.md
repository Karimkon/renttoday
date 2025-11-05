## Project snapshot for AI assistants

This is a Laravel 12 application (PHP >= 8.2). The repository uses the standard Laravel layout with a few project-specific services and third-party integrations. Use this file as your primary short reference before making code changes.

Key places to read first
- `app/Services/` — contains important service classes: `PesapalService.php`, `EgoSmsService.php`, `RentReminderService.php`, `FinancialReportService.php`. These encapsulate external integrations and business rules.
- `app/Models/` — domain models (Tenant, Apartment, Payment, Invoice). Check relationships there before changing queries.
- `app/Http/Controllers/` — controllers wire services to routes. Example: `Admin/LandlordController.php` (current working file).
- `routes/web.php` — top-level routing for web endpoints.
- `config/pesapal.php` and `config/services.php` — config keys used for external APIs (Pesapal, EgoSMS). Respect env-driven configuration.
- `database/migrations/` — schema history; migrations include tenant, apartment, payments and invoice tables.

Developer workflows and exact commands
- Install PHP deps: `composer install` (project expects Composer scripts to initialize `.env` and sqlite in some flows). If you prefer manual: copy `.env.example` → `.env`, set DB settings, then `php artisan key:generate`.
- Frontend tooling: `npm install` then `npm run dev` (uses Vite). There is a composer `dev` script that runs a concurrently-managed dev environment:
  - `composer run dev` — boots `php artisan serve`, `php artisan queue:listen`, `php artisan pail`, and `npm run dev` concurrently (useful during local development).
- Tests: `composer test` (runs `php artisan test`). You can also run PHPUnit directly: `vendor/bin/phpunit`.
- Migrations: `php artisan migrate` (ensure `.env` DB is configured). The composer post-create hook may create `database/database.sqlite` and run migrations automatically on first install.
- Queues/background: This project uses `php artisan queue:listen` in dev; background jobs may be present — inspect `app/Console/Commands` and `app/Jobs` (if present).

Integration and runtime notes (concrete)
- Pesapal (payments): `app/Services/PesapalService.php` calls `config('pesapal.*')` and hits `https://pay.pesapal.com/v3`. The service logs raw response bodies — take care with sensitive data in logs. Methods: `getAccessToken()`, `submitOrder(array $orderData)`, `getTransactionStatus($orderTrackingId)`.
- EgoSMS (SMS): `app/Services/EgoSmsService.php` posts JSON to `https://www.egosms.co/api/v1/json/`. Phone numbers are normalized to Uganda format (`256...`) by `formatPhoneNumber()`; many callers expect that format. Config keys: `EGOSMS_USERNAME`, `EGOSMS_PASSWORD`, and `EGOSMS_SENDER_ID` in `config/services.php`.
- Rent reminders: `app/Services/RentReminderService.php` demonstrates how tenancy data is queried and filtered (uses `Tenant::whereHas('apartment')->with(...)` then filters payments for current month). Use it as canonical example for tenant/payment queries and logging/sanitization patterns.

Project-specific conventions
- Service location: put cross-cutting integrations in `app/Services/`. Inject services via constructors (type-hinted DI) rather than creating static/factory calls.
- Logging: services sanitize user-identifying data before logging (see `sanitizeForLog()` and `maskPhoneNumber()` in `RentReminderService` and `EgoSmsService`). Follow that pattern when adding logs that contain PII.
- SMS message rules: messages are truncated to 160 chars using `truncateMessage()` in `RentReminderService`. Keep this limit and sanitize content for SMS compatibility.
- Config-by-env: secrets are never hard-coded; use `config(...)` wrappers and store in `.env` using established keys (`PESAPAL_*`, `EGOSMS_*`).

What to look for when changing payments or notifications
- When changing payment flows, read `PesapalService::submitOrder()` and follow existing logging and error handling (exceptions are raised on token failures). Look for webhook handlers (search for `Pesapal` in controllers or routes) before adding duplicate handlers.
- When altering SMS flows, reuse `EgoSmsService` and prefer `sendBulkSms()` for batch sends. Do not bypass phone normalization.

Quick examples (copyable snippets)
- Get Pesapal token (pattern used across project):

  $pesapal = app(\App\Services\PesapalService::class);
  $token = $pesapal->getAccessToken();

- Send a tenant reminder (use DI where possible):

  $reminder = app(\App\Services\RentReminderService::class);
  $reminder->sendRentReminderToTenant($tenant);

Files to inspect when debugging common issues
- `storage/logs/laravel.log` — main application logs
- `config/*.php` — ensure keys are set correctly
- `database/migrations/*.php` — schema drift or missing columns
- `phpunit.xml` / `tests/` — test configuration and existing tests

What not to change lightly
- The SMS formatting and logging sanitization helpers. They are applied broadly and tie into privacy practices.
- Pesapal authentication sequences — tests and live systems depend on their token flow.

If something is unclear
- Ask for the intended runtime (local using XAMPP/Apache vs `php artisan serve`) and whether you have access to API credentials (Pesapal, EgoSMS). If credentials are not available, mock external calls using `Http::fake()` in tests.

Next step for me: I added this file. Tell me if you want more details (examples from specific controllers, CI steps, or expanded test guidance) and I will iterate.
