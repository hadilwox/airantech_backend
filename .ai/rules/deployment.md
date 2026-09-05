---
paths:
  - 'database/seeders/DatabaseSeeder.php'
  - 'app/Providers/AppServiceProvider.php'
  - 'bootstrap/app.php'
  - 'routes/api.php'
---

# Deployment / Production Safety

## No known default admin credentials in production
`DatabaseSeeder` only creates the `admin@academy.test` / `password` account when `app()->environment(['local', 'testing'])`. Production admin accounts are created with `php artisan admin:create` (`app/Console/Commands/CreateAdminCommand.php`), which prompts for name/email/password and never hardcodes a value. Never remove the environment guard or add another hardcoded production credential.

## Rate limiters
`login` (6/min per email+ip), `register` (10/hour per ip) and `api` (120/min per user-or-ip, applied to all `/api/*` routes via `throttleApi()` in `bootstrap/app.php`) are defined in `AppServiceProvider::boot()`. Keep new public/abuse-sensitive routes behind an appropriate named limiter instead of leaving them unthrottled.

## Cross-subdomain Sanctum cookies
Production frontend (`app.airanacademy.ir`) and backend (`api.airanacademy.ir`) are same-registrable-domain subdomains, so `SESSION_SAME_SITE=lax` is correct and sufficient — do not switch to `none` unless the frontend moves to a genuinely different domain. `SESSION_DOMAIN=.airanacademy.ir` (leading dot) shares the cookie across both subdomains; `SANCTUM_STATEFUL_DOMAINS` must list the frontend's domain (`app.airanacademy.ir`), not the API's own domain.
