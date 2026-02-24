# dcForm Project Read Guide (Roman Urdu)

## 1) Project ka High-Level Flow
- `index.php`:
  Root landing controller. Agar admin logged-in ho to dashboard pe bhejta hai, warna login page.
- `form.php`:
  Main single public form file (ab sirf yahi canonical form hai).
- `form-access.php`:
  Token based public access page. User bina login unique token link se form access karta hai.
- `thank-you.php`:
  Payment/submission ke baad confirmation page.

## 2) Admin Module (Kya hai, kyun hai, kya karta hai)
- `admin/login.php`:
  Admin authentication entry point.
- `admin/logout.php`:
  Session logout.
- `admin/dashboard.php`:
  Stats cards (payment, form sent, travelers, groups, solo, etc).
- `admin/users.php`:
  Travelers reports table.
- `admin/form-links.php`:
  Country-based form links (Canada/Vietnam/UK).
- `admin/email.php`:
  Traveler ko unique link email send karna + logs store.
- `admin/settings.php`:
  Admin credentials update.
- `admin/includes/bootstrap.php`:
  Common helpers, DB bootstrap, sanitization, token/form-number functions.
- `admin/includes/auth.php`:
  Login/session guard logic.
- `admin/includes/layout.php`:
  Header/sidebar/footer reusable layout.
- `assets/css/admin.css`:
  Admin UI styling.

## 3) Backend Core
- `backend/config.php`:
  Sabse important config file: DB, APP_URL, SMTP, constants, PDO, CSRF helpers.
- `backend/mailer.php`:
  PHPMailer SMTP helper (`sendSmtpMail`).
- `backend/send_email.php`:
  Application/payment email templates aur sending logic.
- `backend/validate.php`:
  Server-side field/step validation rules.
- `backend/payment.php`, `backend/payment_verify.php`:
  Payment init aur verify flow.

## 4) AJAX Endpoints (Form inko call karta hai)
- `backend/ajax/save_step_contact.php`
- `backend/ajax/save_step_personal.php`
- `backend/ajax/save_step_passport.php`
- `backend/ajax/save_step_residential.php`
- `backend/ajax/save_step_background.php`
- `backend/ajax/save_step_declaration.php`
- `backend/ajax/get_states.php`
- `backend/ajax/get_cities.php`
- `backend/ajax/get_lookups.php`
- `backend/ajax/get_traveller.php`

Maqsad: step-by-step save/fetch without page reload.

## 5) Frontend Files
- `assets/js/form.js`:
  Step navigation, AJAX save flow, toasts, loader, traveler switching.
- `assets/js/validator1.js`:
  Client-side validation.
- `assets/css/style.css`:
  Main form styling.

## 6) Database Schema Files
- `backend/db_schema.sql`:
  Core tables + seed data.
- `backend/cities_schema.sql`:
  Cities table + city seed data.
- `backend/admin_schema.sql`:
  Admin users, token links, email logs.

## 7) Important Link Mapping
- `index.php` -> `admin/login.php` ya `admin/dashboard.php`
- `admin/*.php` -> `admin/includes/*.php` -> `backend/config.php`
- `admin/email.php` -> `backend/mailer.php` (SMTP)
- `form.php` -> `assets/js/form.js` + `assets/js/validator1.js`
- `assets/js/form.js` -> `backend/ajax/*.php`
- `form-access.php` -> token validate -> `form.php`

## 8) Production pe move karne ke liye kya change hoga

### 8.1 `backend/config.php` me changes
- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
- `APP_URL` = live domain (example: `https://yourdomain.com`)
- SMTP settings:
  - `SMTP_HOST`
  - `SMTP_PORT`
  - `SMTP_SECURE` (`tls`/`ssl`)
  - `SMTP_USERNAME`
  - `SMTP_PASSWORD`
- `ADMIN_EMAIL`, `FROM_EMAIL`, `FROM_NAME`
- Payment keys (agar live payment chahiye): Razorpay live keys

### 8.2 DB import
Production DB me import karo:
1. `backend/db_schema.sql`
2. `backend/cities_schema.sql`
3. `backend/admin_schema.sql`

### 8.3 Dependencies
- Server pe `composer install`
- `vendor/` folder available ho (PHPMailer ke liye)

### 8.4 PHP/server checks
- `pdo_mysql` enabled
- `openssl` enabled (SMTP TLS)
- Sessions properly configured

### 8.5 Security checklist
- Default admin password turant change karo (`admin/settings.php`)
- HTTPS mandatory rakho
- SPF, DKIM, DMARC set karo (email delivery better)

### 8.6 Smoke testing after deploy
1. Admin login test
2. Dashboard load test
3. Form step saves test
4. Country -> State -> City dropdown chain test
5. Email send + token link open test
6. Payment verify flow (agar enabled)

## 9) Notes
- Ab single form entry point `form.php` hai (duplicate/copy files remove ki ja chuki hain).
- Residential me state/city chain country selection ke mutabiq work karta hai.


# Sabhi Forms ka live link kese banaun!!
Simple hai. Yeh links APP_URL se bante hain.

Abhi aapka APP_URL localhost hoga, isliye link aa raha hai:
http://localhost/dcForm/...

Online karne ke liye:

config.php kholo
Yeh line change karo:
define('APP_URL', 'https://yourdomain.com');
Agar project subfolder me deployed hai to:

define('APP_URL', 'https://yourdomain.com/dcForm');
Save karo, page refresh karo (Ctrl+F5).
Phir admin forms me links auto online ho jayenge:

https://yourdomain.com/form.php?country=Canada
https://yourdomain.com/form.php?country=Vietnam
https://yourdomain.com/form.php?country=UK
Production pe ye bhi ensure karo:

Domain DNS server pe point ho
SSL active ho (https)
Project files server pe uploaded ho
Database import ho chuki ho

## 10) Complete Structure Breakdown (Detailed Inventory + Link Map + Importance)

### 10.1 Root Level (Project ke main entry points)
- `index.php`
  - Kya karta hai: admin area ka gatekeeper entry.
  - Kis se linked: `admin/login.php`, `admin/dashboard.php`, `admin/includes/auth.php`.
  - Kyun zaruri: bina iske admin flow ka clean start point nahi milta.
- `form.php`
  - Kya karta hai: root wrapper, actual form page ko include karta hai.
  - Kis se linked: `pages/form.php`.
  - Kyun zaruri: purane URLs backward-compatible rehte hain.
- `thank-you.php`
  - Kya karta hai: root wrapper for confirmation page.
  - Kis se linked: `pages/thank-you.php`.
  - Kyun zaruri: payment success redirect break hone se bachti hai.
- `READ.md`
  - Kya karta hai: project documentation.
  - Kis se linked: developer onboarding/maintenance.
  - Kyun zaruri: future dev ko architecture jaldi samajh aata hai.
- `.env.example`
  - Kya karta hai: required env variables ka template.
  - Kis se linked: `backend/config.php`.
  - Kyun zaruri: SMTP, DB, APP_URL, payment config iske baghair unclear rehte hain.
- `composer.json` / `composer.lock`
  - Kya karta hai: PHP dependencies define/lock karte hain.
  - Kis se linked: `vendor/`, `backend/mailer.php`.
  - Kyun zaruri: PHPMailer aur autoload dependency management.
- `.gitignore`
  - Kya karta hai: sensitive/temporary files ko git se door rakhta hai.
  - Kis se linked: repo hygiene.
  - Kyun zaruri: secrets leak aur junk commits se bachao.

### 10.2 `pages/` (Actual public pages ka real code)
- `pages/form.php`
  - Kya contain: complete multi-step public form UI + JS bindings + lookup loading.
  - Kis se linked:
    - `../backend/config.php`
    - `assets/css/style.css`
    - `assets/js/form.js`
    - `assets/js/validator1.js`
    - `backend/ajax/*.php` endpoints (via JS fetch/AJAX)
  - Kyun zaruri: main business data capture flow isi file me hai.
- `pages/form-canada.php`, `pages/form-vietnam.php`, `pages/form-uk.php`
  - Kya contain: `FORM_COUNTRY` define karke `pages/form.php` include karte hain.
  - Kis se linked: `pages/form.php`.
  - Kyun zaruri: country-specific entry links maintain karte hain.
- `pages/form-access.php`
  - Kya contain: token validation + traveller lookup + dynamic form link render.
  - Kis se linked:
    - `../backend/config.php`
    - `form_access_tokens` table
    - country map -> `form-canada.php` / `form-vietnam.php` / `form-uk.php`
  - Kyun zaruri: secure, login-less unique access links.
- `pages/thank-you.php`
  - Kya contain: payment success confirmation UI + reference number display.
  - Kis se linked: `backend/payment_verify.php` redirect result.
  - Kyun zaruri: user confirmation + post-payment UX.

### 10.3 `admin/` (Admin panel)
- `admin/login.php`
  - Kya contain: admin login form + credential submit.
  - Kis se linked: `admin/includes/auth.php`, `admin/includes/bootstrap.php`.
  - Kyun zaruri: admin access security entry.
- `admin/logout.php`
  - Kya contain: session logout.
  - Kis se linked: `admin/includes/auth.php`.
  - Kyun zaruri: secure session termination.
- `admin/dashboard.php`
  - Kya contain: stats cards + recent payment docs summary.
  - Kis se linked: DB tables `applications`, `travellers`, `form_access_tokens`, `payment_documents`.
  - Kyun zaruri: operational snapshot.
- `admin/users.php`
  - Kya contain: traveller add/edit/delete + reports + status view.
  - Kis se linked: `applications`, `travellers`, `form_access_tokens`.
  - Kyun zaruri: core admin data management.
- `admin/email.php`
  - Kya contain: traveller select karke secure form-link email send + log store.
  - Kis se linked:
    - `backend/mailer.php`
    - `form_access_tokens`
    - `admin_email_logs`
    - link target `form-access.php` (token URL)
  - Kyun zaruri: user follow-up aur form completion pipeline.
- `admin/form-links.php`
  - Kya contain: country forms ke public URLs display/open.
  - Kis se linked: `APP_URL`, form country entry files.
  - Kyun zaruri: quick operational access.
- `admin/documents.php`
  - Kya contain: payment docs listing + download links.
  - Kis se linked: `payment_documents`, `applications`, `travellers`.
  - Kyun zaruri: receipts/PDF traceability.
- `admin/settings.php`
  - Kya contain: admin username/email/password update.
  - Kis se linked: `admin_users`.
  - Kyun zaruri: account maintenance/security.
- `admin/index.php`
  - Kya contain: auth-aware redirect (`login` ya `dashboard`).
  - Kis se linked: `admin/includes/auth.php`.
  - Kyun zaruri: clean admin base route.
- `admin/includes/bootstrap.php`
  - Kya contain: admin helper functions, table ensure, sanitize, flash, URL builders.
  - Kis se linked: `backend/config.php`.
  - Kyun zaruri: central foundation for admin module.
- `admin/includes/auth.php`
  - Kya contain: currentAdmin, login verify, requireAdmin guard, logout.
  - Kis se linked: session + `admin_users` table.
  - Kyun zaruri: access control.
- `admin/includes/layout.php`
  - Kya contain: reusable admin HTML shell (sidebar/topbar/footer).
  - Kis se linked: `assetUrl('css/admin.css')`.
  - Kyun zaruri: DRY UI structure.

### 10.4 `backend/` (Business logic + DB + integrations)
- `backend/config.php`
  - Kya contain: env loader, constants, PDO singleton, CSRF helpers.
  - Kis se linked: almost poora project.
  - Kyun zaruri: single source of truth for runtime config.
- `backend/mailer.php`
  - Kya contain: centralized SMTP function `sendSmtpMail`.
  - Kis se linked: PHPMailer (`vendor/autoload.php`), env SMTP constants.
  - Kyun zaruri: secure mail send without hardcoded credentials.
- `backend/send_email.php`
  - Kya contain: form submitted / payment confirmation email templates + logging helper.
  - Kis se linked: `backend/mailer.php`, `system_email_logs`.
  - Kyun zaruri: automated email communication.
- `backend/payment.php`
  - Kya contain: payment initialization.
  - Kis se linked: payment provider credentials.
  - Kyun zaruri: payment start point.
- `backend/payment_verify.php`
  - Kya contain: payment verification + final redirect to thank-you.
  - Kis se linked: DB updates + `thank-you.php?ref=...`.
  - Kyun zaruri: payment completion trust chain.
- `backend/validate.php`
  - Kya contain: server-side validation rules.
  - Kis se linked: form save endpoints.
  - Kyun zaruri: data integrity + tampering protection.
- `backend/documents.php`
  - Kya contain: document generation/storage helper flows.
  - Kis se linked: payment/document tables + filesystem paths.
  - Kyun zaruri: receipt/pdf lifecycle.
- `backend/ajax/*.php`
  - Kya contain:
    - `save_step_*`: step-wise data save
    - `get_states.php`, `get_cities.php`, `get_cities_by_country.php`: dependent dropdown APIs
    - `get_lookups.php`: lookup data
    - `get_traveller.php`: traveller fetch
  - Kis se linked: `assets/js/form.js`.
  - Kyun zaruri: without reload progressive form UX.
- `backend/*.sql` (`db_schema.sql`, `cities_schema.sql`, `admin_schema.sql`)
  - Kya contain: schema + seed setup.
  - Kis se linked: DB bootstrap.
  - Kyun zaruri: reproducible environment setup.

### 10.5 `assets/` (Shared frontend resources)
- `assets/css/style.css`
  - Kya contain: main public form styling.
  - Kis se linked: `pages/form.php`.
  - Kyun zaruri: form UI render.
- `assets/css/style1.css`
  - Kya contain: alternate/legacy styling file.
  - Kis se linked: currently active references minimal/none.
  - Kyun zaruri: backward/experimental styling fallback.
- `assets/css/admin.css`
  - Kya contain: admin panel styling.
  - Kis se linked: `admin/login.php`, `admin/includes/layout.php`.
  - Kyun zaruri: admin UI consistency.
- `assets/js/form.js`
  - Kya contain: stepper flow, AJAX submits, lookup chaining, UX controls.
  - Kis se linked: `backend/ajax/*.php`.
  - Kyun zaruri: dynamic form behavior.
- `assets/js/validator1.js`
  - Kya contain: client-side validation helpers.
  - Kis se linked: `pages/form.php`.
  - Kyun zaruri: user-side validation feedback.
- `assets/img/logo/logo.webp`
  - Kya contain: branding logo.
  - Kis se linked: public form navbar/logo spot.
  - Kyun zaruri: branding identity.

### 10.6 `vendor/` (Composer dependencies)
- Kya contain: third-party libraries (including PHPMailer) + autoload files.
- Kis se linked: `backend/mailer.php`.
- Kyun zaruri: mail transport and library ecosystem.

### 10.7 Current Dependency Chain (One-line view)
- Public form flow:
  - `form.php` -> `pages/form.php` -> `assets/js/form.js` -> `backend/ajax/*.php` -> DB
- Admin auth flow:
  - `admin/login.php` -> `admin/includes/auth.php` -> `admin/includes/bootstrap.php` -> `backend/config.php` -> DB
- Admin email flow:
  - `admin/email.php` -> `backend/mailer.php` -> SMTP
- Payment flow:
  - `assets/js/form.js` (or checkout trigger) -> `backend/payment.php` -> gateway -> `backend/payment_verify.php` -> `thank-you.php` -> `pages/thank-you.php`

### 10.8 Zaroori Operational Note (Current State)
- Is waqt root me `form-access.php` wrapper maujood nahi hai, jabke `admin/email.php` generated link `.../form-access.php?token=...` use karta hai.
- Is wajah se token links break ho sakte hain jab tak root wrapper restore na ho ya `admin/email.php` link `pages/form-access.php` par switch na kiya jaye.
