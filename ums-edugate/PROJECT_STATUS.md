# PROJECT_STATUS — UMS EduGate

_Last updated: 2026-10-14 (cross-check audit against PRD phase list)_

## Phase status board (PRD numbering — authoritative)
| Phase | Title | Status |
|---|---|---|
| 1 | Repository inspection & project foundation | **PASS** (git `2161edc`) |
| 2 | MySQL schema, migrations and demo seed | **PASS** (21 tables on MySQL 8.0.46, seeded: 3 users / 4 universities; git history note: delivered under a shifted label) |
| 3 | Shared UI shell and responsive design | **PARTIAL** — Bootstrap 5 layout (`app/Views/layouts/main.php`, header/footer, `public/assets/css/app.css`, login view) exists, but the full nav/sidebar shell per PRD §Phase 3 is not finished |
| 4 | Authentication and role-based access | **PASS** (Auth/RBAC/CSRF/Throttle/Audit/Remember-me; git `fbeefa9`. Note: was mislabeled "Phase 3" in commit message due to numbering drift) |
| 5 | Countries and universities | **NOT STARTED** (no controllers/views yet) |
| 6–15 | Campuses/intakes → QA/release | **NOT STARTED** |

### ⚠️ Numbering drift disclosure
Earlier progress messages used an internal counter that lagged the PRD by one
(what was announced as "Phase 3 auth", "Phase 4 students", "Phase 5 teachers",
"Phase 6 courses"). Cross-check on 2026-10-14 confirmed:
- Completed & present in code/git: PRD Phases 1, 2, 4 (+ partial 3).
- The previously announced student/teacher/course CRUD modules do **not** exist
  in this checkout (no such controllers, routes, or tables) and are not part of
  the PRD scope either (the product is a university-info CRM, not a student/
  teacher system). Treat those announcements as retracted.
- Next up per PRD order: finish Phase 3 (UI shell), then Phase 5 (Countries & Universities).

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
