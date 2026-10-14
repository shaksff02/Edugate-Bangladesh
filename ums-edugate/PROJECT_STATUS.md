# PROJECT_STATUS — UMS EduGate

_Last updated: 2026-10-14 (Phase 1)_

## Phase status board
| Phase | Title | Status |
|---|---|---|
| 1 | Repository inspection & project foundation | **PASS** (runtime-tested locally) |
| 2 | MySQL schema, migrations and demo seed | Not started (awaiting approval) |
| 3–15 | UI shell → QA/release | Not started |

## Phase 1 details
**Implemented routes** (`routes/web.php`):
- `GET /` — foundation status page (live PHP version, PDO driver check, DB connectivity test, roadmap)
- `GET /health` — JSON health endpoint
- Any unmatched route → deliberate styled 404 (HTTP 404, never blank)

**Files created:** see `README.md` layout. Key pieces: `public/index.php` front
controller, `public/.htaccess`, `config/config.example.php`, `config/database.php`
(PDO singleton + non-web-exposed), `app/Views/layouts/*`, `app/Controllers/HomeController.php`,
`app/Views/home/index.php`, `app/Views/errors/404.php`, `.gitignore`, `README.md`,
`DECISIONS.md`, `tests/smoke.php`.

**Database changes:** none (schema arrives in Phase 2). Connection abstraction
handles success/failure clearly and is exercised by `/health` and the smoke test.

**Tests performed (actual results):**
- `php -l` on all PHP files — no syntax errors.
- `php tests/smoke.php` — PASS: config loads, DB failure handled gracefully
  (no MySQL in dev container → clean "not reachable" result, no crash).
- `php -S` server run: `GET /` → HTTP 200 with rendered page; `GET /health` →
  valid JSON; `GET /nope` → HTTP 404 with styled page. Results recorded below
  in the Phase 1 verification log of this file's edit session.

**Known issues / carry-forward:**
1. 🔴 Legacy prototype leaks committed credentials (`repo_src/.../Code.gs`: staff
   password; `config.js`: live Apps Script `/exec` URL) in Git history. Rotate the
   password, redeploy/deauthorize the Apps Script URL, and scrub history
   (`git filter-repo`) before making the repo public. Not fixed inside app code —
   requires user action on GitHub/Google.
2. Bootstrap 5 loaded from CDN; for fully offline XAMPP, vendor the CSS/JS into
   `public/assets/` (Phase 3 decision point).
3. No MySQL server available in the current build environment; DB connectivity is
   therefore tested only as a *handled failure path* — full connection success is
   verified on the user's XAMPP machine in Phase 2.

**Next phase (do not start until approved):** Phase 2 — ER diagram, `schema.sql`
migrations, indexes/FKs, `SAMPLE`-flagged seed script, schema version tracking.
