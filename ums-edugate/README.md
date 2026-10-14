# UMS EduGate (PHP/MySQL)

University Management System for an education consultancy — being rebuilt from a
Google Sheets/Apps Script prototype (`repo_src/UMS_Edugate-main/`) into a
maintainable PHP 8 + MySQL application, following `UMS_EduGate_Detailed_PRD.md`
in 15 controlled phases.

**Current state: Phase 1 — project foundation only.** No business modules,
no database schema yet. See `PROJECT_STATUS.md`.

## Requirements
- PHP **8.1+** with `pdo_mysql` (verified locally on PHP 8.2.34)
- MySQL **8.0+** (official Oracle MySQL Community Server — target engine; MariaDB not used)
- Apache (XAMPP) or the PHP built-in server for development

## Quick start (XAMPP / Apache)
1. Copy this folder to `C:\xampp\htdocs\ums-edugate` (or `/opt/lampp/htdocs/ums-edugate`).
2. `copy config\config.example.php config\config.local.php` and set your DB credentials
   (XAMPP default: host `127.0.0.1`, port `3306`, user `root`, empty password).
   **Never commit `config.local.php`** — it is git-ignored.
3. Point Apache's DocumentRoot to `ums-edugate/public/` (recommended), e.g. a vhost:
   ```apache
   DocumentRoot "C:/xampp/htdocs/ums-edugate/public"
   <Directory "C:/xampp/htdocs/ums-edugate/public">
     AllowOverride All
     Require all granted
   </Directory>
   ```
   This keeps `config/`, `database/`, `storage/` and `app/` outside the web root.
   `public/.htaccess` rewrites all requests to the front controller.
4. Open `http://localhost/ums-edugate/` (or your vhost). You should see the
   Phase 1 foundation page with live environment status.

## Quick start (no Apache — PHP built-in server)
```bash
cd ums-edugate
php -S localhost:8000 -t public public/index.php
```
Then open `http://localhost:8000/`.

## Project layout
```
app/          Controllers, Services, Repositories, Middleware, Validators, Views
config/       config.example.php (committed), config.local.php (private, ignored), database.php (PDO abstraction)
database/     migrations/ and seeds/ (added in Phase 2)
public/       index.php front controller, .htaccess, assets/  ← the only web root
routes/       web.php route table
storage/logs/ runtime logs (ignored)
tests/        smoke tests (see tests/smoke.php)
```

## Security notes
- Private configuration lives in `config/config.local.php`, excluded from Git and
  outside the web root.
- The old Apps Script prototype committed real credentials (`Code.gs`, `config.js`).
  Those must be rotated and removed from history — tracked as a known issue in
  `PROJECT_STATUS.md`; they are **not** reused anywhere in this codebase.

## Phase plan
Phases 1–15 are defined in `UMS_EduGate_Detailed_PRD.md` §11. Work only on the
approved current phase; update `PROJECT_STATUS.md` and `DECISIONS.md` at each step.
