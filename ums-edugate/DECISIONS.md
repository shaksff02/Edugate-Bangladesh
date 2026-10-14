# DECISIONS — UMS EduGate

Decisions taken where the PRD left room. Do not silently change these.

| # | Date | Decision | Rationale |
|---|---|---|---|
| D-01 | 2026-10-14 | New PHP app lives in `ums-edugate/`; legacy Apps Script kept read-only in `repo_src/` as workflow/data reference | PRD §1: workbook is a reference, not to be copied blindly |
| D-02 | 2026-10-14 | Config strategy: committed `config/config.example.php` + private git-ignored `config/config.local.php` merged via `array_replace_recursive` in `config/database.php` | PRD rules 14 & §3: no secrets in source; example file documents every key |
| D-03 | 2026-10-14 | Front controller = plain PHP router reading `routes/web.php`; static files under `public/` served directly; unmatched routes render a styled 404 with HTTP 404 | PRD Phase 1: no framework (rule 12); "deliberate 404, not a blank page" |
| D-04 | 2026-10-14 | View layer = PHP templates with `render_view()` + layout header/footer; all output escaped with `e()` helper | Continues the escaping discipline of the legacy app; minimal tooling |
| D-05 | 2026-10-14 | Bootstrap 5.3 via jsDelivr CDN for now | Simple local start; revisit vendoring for offline use (logged in PROJECT_STATUS) |
| D-06 | 2026-10-14 | Staleness threshold default = 180 days placed in config (`verification.stale_days`) | PRD §5 default unless user chooses otherwise |
| D-07 | 2026-10-14 | DB name `ums_edugate`, charset `utf8mb4`, PDO with `ERRMODE_EXCEPTION`, `EMULATE_PREPARES=false` | PRD rule 10 (real prepared statements); safe Unicode storage |
| D-08 | 2026-10-14 | `Database::testConnection()` connects without selecting a database | Phase 1 acceptance needs clear success/failure handling before the schema exists (Phase 2 creates it) |
