# Production Deployment — Academy Management System

Two independently deployed apps sharing the root domain `airanacademy.ir`:

| Environment | Frontend | Backend API |
| --- | --- | --- |
| Development | `http://localhost:5173` | `http://localhost:8000` |
| Production  | `https://app.airanacademy.ir` | `https://api.airanacademy.ir` |

Auth is Laravel Sanctum SPA cookie/session auth — no tokens, no localStorage. The
frontend and API are same-registrable-domain subdomains, which is what makes the
cookie-sharing setup below work.

Secrets (`.env`, DB passwords, `APP_KEY`) live only in environment configuration
on the server — never commit them. Both repos' `.gitignore` already exclude `.env`
and `.env.production`.

---

## Backend (`api.airanacademy.ir`)

### Requirements
- PHP 8.3+ with extensions: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `curl` (standard Laravel set — cPanel's PHP selector usually has these on by default).
- Composer 2.
- MySQL 8 (or MariaDB 10.6+) — a dedicated database and a non-root DB user (already the local convention: `academy` / `academy_app`).
- No Docker required; this is a standard shared-hosting-compatible PHP app.
- No persistent Node.js process — Node/npm are build-time only for the *frontend*, not needed to run this backend at all.

### Steps
1. Upload/clone the repository to the server (e.g. `/home/user/airantech_backend`), **outside** the publicly served directory if your host allows it, or ensure the domain's document root points at its `public/` folder either way (see below).
2. `composer install --no-dev --optimize-autoloader`
3. Copy `.env.example` to `.env` and fill in production values (see **Environment variables** below).
4. Generate the app key if `.env` doesn't already have one: `php artisan key:generate`
5. Configure the database in `.env` and confirm connectivity: `php artisan migrate:status`
6. Run migrations: `php artisan migrate --force`
   - Never run `migrate:fresh` or `migrate:fresh --seed` in production — both drop all tables.
   - `RolePermissionSeeder` is safe to (re)run any time — it only upserts roles/permissions: `php artisan db:seed --class=RolePermissionSeeder --force`
   - `DatabaseSeeder`'s default `admin@academy.test` / `password` account is guarded to only seed in `local`/`testing` environments — it will NOT be created in production. Create the real admin with:
     ```
     php artisan admin:create --name="..." --email="admin@airanacademy.ir" --password="..."
     ```
     (omit the options to be prompted interactively instead, so the password never appears in shell history).
7. `php artisan storage:link` — not currently required (no user-facing file uploads exist yet), but harmless and future-proof to run once.
8. Cache configuration for production performance:
   ```
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
   Re-run `config:cache` after any `.env` change — cached config takes priority over `.env` at runtime, which is the #1 cause of "I changed `.env` but nothing happened" in production.
9. Point the `api.airanacademy.ir` subdomain's **document root at this project's `public/` directory** (this is the standard, correct approach — cPanel subdomain creation lets you set an arbitrary document root; do not serve the repo root). `public/.htaccess` (already present, Laravel's default) handles routing all requests through `public/index.php`.
10. Set PHP version for the subdomain (8.3+) via cPanel's "MultiPHP Manager" or equivalent.
11. No cron/queue worker is required today — no jobs are dispatched anywhere in the app. If a future phase adds queued jobs, either run `php artisan queue:work` as a supervised daemon, or set `QUEUE_CONNECTION=sync` if the host can't run persistent processes.
12. Verify: `curl -i https://api.airanacademy.ir/up` should return 200 (Laravel's built-in health route), and `curl -i https://api.airanacademy.ir/sanctum/csrf-cookie` should return a `Set-Cookie` header.

### Environment variables (production values)

```
APP_NAME=airantech
APP_ENV=production
APP_KEY=base64:...            # generate once, keep stable — rotating it invalidates all sessions/encrypted data
APP_DEBUG=false
APP_URL=https://api.airanacademy.ir

APP_LOCALE=fa
APP_FALLBACK_LOCALE=fa

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=<production db name>
DB_USERNAME=<production db user, never root>
DB_PASSWORD=<production db password>

SESSION_DRIVER=database
SESSION_DOMAIN=.airanacademy.ir
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

FRONTEND_URL=https://app.airanacademy.ir
SANCTUM_STATEFUL_DOMAINS=app.airanacademy.ir

QUEUE_CONNECTION=sync
CACHE_STORE=database

MAIL_MAILER=<a real production driver — "log" only writes to a file>
```

Never put real values for the above into a file that gets committed — set them
directly in the server's `.env` (or your host's environment-variable panel).

---

## Frontend (`app.airanacademy.ir`)

### Requirements
- Node.js 20+ and npm — **build time only**. The deployed artifact is static
  HTML/CSS/JS; nothing Node-related needs to run on the production server.

### Steps
1. `npm install`
2. Set the production API URL. Either:
   - create `.env.production` (gitignored) with `VITE_API_URL=https://api.airanacademy.ir`, or
   - export `VITE_API_URL=https://api.airanacademy.ir` in the CI/build environment.
   `.env.example` documents this variable; `.env` (committed) stays pointed at `localhost:8000` for local dev and is untouched by a production build that supplies `.env.production` or an env override.
3. `npm run build` — outputs static files to `dist/`.
4. Upload the **contents** of `dist/` (not the folder itself) to `app.airanacademy.ir`'s document root.
5. SPA fallback is required: the app uses Vue Router's history mode, so a direct load of e.g. `https://app.airanacademy.ir/students` must serve `index.html`, not 404.
   - **Apache/cPanel**: `dist/.htaccess` (generated by the build from `public/.htaccess` in this repo) already contains the rewrite rule — just make sure `mod_rewrite` is enabled for the subdomain (standard on cPanel).
   - **Nginx**: no `.htaccess` equivalent is used; add to the site's server block:
     ```
     location / {
         try_files $uri $uri/ /index.html;
     }
     ```
6. HTTPS is mandatory in production — Sanctum's `SESSION_SECURE_COOKIE=true` on the API side means the session cookie is only ever sent over HTTPS, so the frontend must be served over HTTPS too or the browser will never attach it back.
7. Verify: open `https://app.airanacademy.ir`, confirm no requests to `localhost` appear in the Network tab, and that `/sanctum/csrf-cookie` + `/api/login` succeed with cookies visible in the response.
8. Verify authentication end-to-end: log in, refresh the page (session must survive), navigate between protected routes, log out.

---

## Verification run (this pass)

| Check | Result |
| --- | --- |
| `php artisan test --compact` | PASS — 68/68 tests, 174 assertions |
| `vendor/bin/pint --dirty --format agent` | PASS |
| `npm run type-check` | PASS |
| `npm run build` | PASS — no source maps emitted, `.htaccess` correctly bundled into `dist/` |
| `throttle:api` attached to all `/api/*` routes | Verified via `php artisan tinker` — resolves to `[EnsureFrontendRequestsAreStateful, throttle:api, SubstituteBindings]` |
| `throttle:register` on both registration routes | Verified via `php artisan route:list` |
| `admin:create` command | Verified live — creates user, assigns Admin role, idempotent via `updateOrCreate` |

**Not verified in this pass** (the real `airanacademy.ir` domains are not reachable
from this environment): actual production DNS/TLS, cross-subdomain cookie
behavior against the real domains, and the Nginx `try_files` snippet (Nginx isn't
installed here — only documented). Everything above this line is either code/config
inspection, a local test run, or a local build — not a live production check.

---

## Remaining manual steps (hosting-side, cannot be done from this repo)

- Point `api.airanacademy.ir` and `app.airanacademy.ir` DNS at the hosting server.
- Issue/renew TLS certificates for both subdomains (e.g. cPanel AutoSSL, Let's Encrypt).
- Set each subdomain's document root (`public/` for the backend; `dist/` contents for the frontend) and PHP version in cPanel.
- Create the production MySQL database and a non-root user, and put those credentials only in the server's `.env`.
- Run `php artisan admin:create` once, interactively, on the production server.
- Configure a real `MAIL_MAILER` (SMTP or a transactional provider) if/when the app needs to send email — currently `log` only.
- If a future phase adds queued jobs, set up a supervised `queue:work` process (or keep `QUEUE_CONNECTION=sync` if the host can't run daemons).

## Important warnings

- **`APP_DEBUG` must be `false`** in production — leaving it `true` exposes full stack traces, `.env` values, and file paths in error responses.
- **`SESSION_DOMAIN` must have the leading dot** (`.airanacademy.ir`) or the API's session cookie won't be visible where Sanctum expects it during the CSRF/login flow from the frontend's origin.
- **`SANCTUM_STATEFUL_DOMAINS` must list the frontend's domain** (`app.airanacademy.ir`), not the API's own domain — Sanctum uses this list (checked against the request's `Referer`) to decide whether to treat a request as a stateful, cookie-authenticated SPA request.
- **CORS must not use `*`** for `allowed_origins` while `supports_credentials` is `true` — browsers reject that combination outright, and `config/cors.php` here is already scoped to the single `FRONTEND_URL` value, which must be the exact `https://app.airanacademy.ir` origin (no trailing slash).
- **Document root must be `public/`** for the backend — pointing a subdomain at the repo root exposes `.env`, `app/`, `vendor/`, etc. directly.
- **SPA fallback must be configured** on the frontend host or every deep link will 404 on refresh — this repo ships the Apache rule in `dist/.htaccess`; Nginx hosts must add the `try_files` block manually (documented above).
- **Never re-introduce a hardcoded production admin password** — `DatabaseSeeder` is intentionally guarded to skip the default admin outside `local`/`testing`; use `admin:create`.
- **Rotating `APP_KEY` invalidates all active sessions and any encrypted data** — treat it as a one-time value per environment, back it up securely.
