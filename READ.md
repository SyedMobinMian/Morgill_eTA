# dcForm Project Read Guide (Roman Urdu)

## 1) High-Level Flow
- `index.php`: Admin entry gate (login/dashboard redirect).
- `form.php`: Public form entry (generic).
- `form-access.php`: Token-based public access (no login).
- `thank-you.php`: Payment/submission confirmation.

## 2) New Folder Structure (Clean)
- `admin/`: Admin panel (UI + logic)
- `admin/actions/`: Action handlers (future extension)
- `admin/includes/`: Shared admin layout/auth/bootstrap
- `assets/`: CSS/JS/images
- `core/`: System backbone (config/bootstrap/db/helpers/mailer)
- `core/schemas/`: SQL schemas & seeds
- `includes/`: Public UI components (header/footer/navbar)
- `modules/ajax/`: All AJAX endpoints
- `modules/forms/`: Public form views/logic
- `modules/payments/`: Payment + documents logic
- `storage/`: Generated documents (protected + gitignored)
- `vendor/`: Composer dependencies

## 3) Admin Module
- `admin/login.php`: Login
- `admin/dashboard.php`: Stats
- `admin/users.php`: Travellers & reports
- `admin/email.php`: Token link send + logs
- `admin/documents.php`: Documents list
- `admin/download.php`: Secure download
- `admin/settings.php`: Credentials update
- `admin/includes/bootstrap.php`: Admin helpers + DB bootstrap
- `admin/includes/auth.php`: Session guard + roles

## 4) Core System (Backbone)
- `core/config.php`: Env, constants, DB, CSRF helpers
- `core/bootstrap.php`: Starts session + loads config
- `core/database.php`: DB helper
- `core/functions.php`: Global helpers
- `core/mailer.php`: SMTP wrapper (PHPMailer)

## 5) Public Form Flow
- `form.php` -> `modules/forms/form.php` -> `assets/js/form.js`
- Country selection via query string:
  `form.php?country=Canada`, `form.php?country=Vietnam`, `form.php?country=UK`
- `assets/js/form.js` -> `modules/ajax/*` (step saves + lookups)
- Confirm -> `modules/ajax/confirm_submission.php` (email + PDF)

## 6) Payments
- `assets/js/form.js` -> `modules/payments/payment.php`
- Gateway -> `modules/payments/payment_verify.php`
- Documents generated in `storage/receipts` + `storage/forms`

## 7) Token Access Links
- Admin sends link: `form-access.php?token=...`
- Token page: `modules/forms/form-access.php`
- Opens main form by country

## 8) Config Change for Production
Edit `core/config.php` via `.env`:
- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
- `APP_URL` (optional: auto-detected if not set)
- SMTP: `SMTP_HOST`, `SMTP_PORT`, `SMTP_SECURE`, `SMTP_USERNAME`, `SMTP_PASSWORD`
- `ADMIN_EMAIL`, `FROM_EMAIL`, `FROM_NAME`
- Payment keys: `RAZORPAY_KEY_ID`, `RAZORPAY_KEY_SECRET`

## 9) Database Import (Production)
Import in order:
1. `core/schemas/db_schema.sql`
2. `core/schemas/cities_schema.sql`
3. `core/schemas/admin_schema.sql`

## 10) Dependencies
- Run `composer install` (PHPMailer)
- Ensure `pdo_mysql` + `openssl` enabled

## 11) Security Notes
- Admin seed password is from `.env` (`ADMIN_SEED_PASSWORD`).
- HTTPS recommended in production.
- `storage/` is blocked via `.htaccess` and gitignored.

## 12) Link Map (Quick)
- Public: `form.php` -> `modules/forms/form.php`
- Admin: `admin/*.php` -> `admin/includes/*` -> `core/bootstrap.php`
- Email: `admin/email.php` -> `core/mailer.php`
- Payment: `modules/payments/*`
- AJAX: `modules/ajax/*`
