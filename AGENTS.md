# AGENTS.md

## Cursor Cloud specific instructions

This is a **plain PHP 8 e-commerce + corporate site** ("Timbuktu Farming"). There is no package
manager (no Composer/npm), no build step, and no external database daemon — SQLite is embedded.

### Run the dev server
Serve the repo root with PHP's built-in server (document root = repository root):

```
php -S 127.0.0.1:8000 -t .
```

Then open `http://127.0.0.1:8000/index.php`. The SQLite DB at `data/app.sqlite` is **auto-created,
migrated and seeded on the first request** that touches the DB (see `lib_db.php`) — there are no
migrations to run. `data/` is gitignored.

### Non-obvious: backend logic is OFF by default ("design mode")
`lib_bootstrap.php` defines `APP_FRONTEND_ONLY = true`, which **disables auth, DB writes and
checkout/order creation** so the design can be worked on standalone. To exercise the full
e-commerce flow (signup, login, cart persistence, placing orders) you must run the server with the
constant overridden to `false` — do this without editing tracked code via an `auto_prepend_file`:

```
printf '<?php if(!defined("APP_FRONTEND_ONLY")) define("APP_FRONTEND_ONLY", false);\n' > /tmp/backend_mode.php
php -d auto_prepend_file=/tmp/backend_mode.php -S 127.0.0.1:8000 -t .
```

### Checkout / auth flow gotchas
- Checkout requires a **logged-in account with a verified email**.
- The dev "mailer" does not send real email — it appends messages (including the email
  **verification link**) to `data/mail.log` (see `lib_mail.php`). Grab the verification token from
  there to verify an account during testing.
- `payment_method=bank_transfer` completes an order **without** Stripe. Card/wallet checkout via
  Stripe is optional and only activates when keys are set in `.env` (copy from `.env.example`).
- Known app quirk (not an environment issue): after login the `next` redirect can resolve to
  `/auth/checkout.php` (404); just navigate to `/checkout.php`. The `products.php` catalog listing
  also renders some categories empty, but the homepage shop and `product.php?sku=...` pages work.

### Lint
There is no test suite or linter config. Syntax-check PHP files with the built-in linter:

```
find . -name '*.php' -not -path './vendor/*' -exec php -l {} \;
```

### Required PHP extensions
`pdo_sqlite`, `curl` (Stripe), `intl` (currency formatting; has a graceful fallback), `mbstring`.
