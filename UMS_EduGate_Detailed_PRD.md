# UMS EduGate — Product Requirements Document (PRD)
**Version:** 1.0  
**Product:** University Management System for Education Consultancy  
**Target stack:** HTML5, CSS3, JavaScript, Bootstrap 5, PHP 8+, MySQL 8+  
**Deployment assumption:** XAMPP/local Apache-PHP-MySQL first; production hosting later  
**Primary purpose:** Convert the supplied UMS EduGate workbook and its workflows into a maintainable, database-backed web application.

---

# 0. Instructions for the AI developer

You are the lead full-stack developer. Build this project in **15 controlled phases**. Do not attempt to generate the entire system in one response.

## Mandatory anti-hallucination rules
1. **Work only on the current phase.** Do not start the next phase until the current phase passes its acceptance checklist and the user approves continuation.
2. Before coding each phase, inspect the existing project files and database schema. If no project exists, create the structure defined below.
3. Never invent a requirement, field, route, database column, sample university fact, API, or completed test. If a decision is necessary but unspecified, use the conservative default in this PRD and document it.
4. Do not claim that code was executed, a test passed, or a page works unless you actually ran/tested it. If the environment cannot run it, state “not runtime-tested.”
5. At the end of each phase, report: (a) files created/changed, (b) features completed, (c) exact test steps and observed results, (d) known issues, (e) next phase proposal.
6. Keep a `PROJECT_STATUS.md` file updated after every phase with phase status, implemented routes, tables, migrations, tests, known issues, and next phase.
7. Keep a `DECISIONS.md` file for decisions not explicit in this PRD. Do not silently change a previous decision.
8. Do not replace working code wholesale when a targeted change is sufficient. Do not delete user data, existing files, or working features without explicit approval.
9. Never use fake data as if it were real. Any seed/demo data must be clearly labeled `SAMPLE` or `DEMO`, and the UI must visibly mark it.
10. Use prepared statements for every database query involving values. Do not concatenate user input into SQL.
11. Use PHP for server-side logic. JavaScript is for UI behavior and client-side convenience only; all validation and authorization must be repeated server-side.
12. Do not add React, Vue, Node.js, Laravel, Composer packages, or a different framework unless explicitly approved. The requested stack is HTML, CSS, JavaScript, Bootstrap, PHP and MySQL.
13. Use Bootstrap 5, vanilla JavaScript, semantic HTML, and a clean responsive layout. Avoid unnecessary animations, huge dependencies, and excessive colors.
14. Do not hardcode real passwords, secrets, database credentials, or private keys in public-facing source files. For local development, use a private config file excluded from version control. This requirement is mandatory even if the user says security is not a concern.
15. If the workbook and this PRD conflict, do not guess. Explain the conflict and ask a concise question, unless this PRD explicitly defines the authoritative behavior.

## Required response format at the end of each phase
- **Phase status:** PASS / PARTIAL / BLOCKED
- **Files changed**
- **Implementation summary**
- **Database changes**
- **Tests performed and actual results**
- **Known limitations**
- **Next phase (do not start until approved)**

---

# 1. Product overview

UMS EduGate is an internal information-management tool for an education consultancy. Staff use it to maintain and search country, university, campus, intake, application-round, program, academic-requirement, language-requirement, fee, scholarship, document, visa, source and country-specific requirement records.

The supplied workbook is a **reference for workflow and data structure**, not a verified live source of university facts. It contains demo/sample university data. Do not present its sample values as real admission advice.

## Product goals
- Replace spreadsheet-dependent operations with a browser-based PHP/MySQL application.
- Centralize university and program data in a relational database.
- Allow authorized staff to create, view, edit, archive and search records.
- Support detailed program filtering by destination, degree, major, intake, language-test scores, GPA, tuition and scholarship.
- Show source URLs and verification dates so staff can assess data freshness.
- Provide dashboard metrics, deadline summaries, printable university profiles, and audit history.
- Preserve relationships between universities and dependent records.
- Make the system easy to maintain by a beginner-to-intermediate developer.

## Out of scope for the first release
- Student portal or student self-registration.
- Online applications submitted to universities.
- Automated scraping of university websites.
- Automatic visa eligibility decisions or legal advice.
- Payment processing.
- Email/SMS/WhatsApp sending.
- AI-generated admissions decisions.
- Third-party ranking API integration.
- Mobile native apps.
- Multi-tenant SaaS architecture.

---

# 2. User roles and permissions

For the initial version, implement a simple staff login with these roles:

1. **Admin**
   - Full access to all modules.
   - Manage staff users and role assignments.
   - Manage settings and dropdown/reference lists.
   - View audit log and perform archive/restore operations.
2. **Editor / Counselor**
   - View and search records.
   - Create and edit country, university, campus, intake, round, program, requirement, fee, scholarship, visa and source records.
   - Cannot manage staff accounts or change global system settings.
3. **Read-only**
   - View dashboard, searches, university profiles, sources and printable views.
   - Cannot create, edit, archive or delete records.

If implementing role-based access would block an early phase, build the login and route guards first, then progressively apply permissions in Phase 5. Do not leave write routes unprotected in the final system.

---

# 3. Technology and architecture

## Required stack
- HTML5
- CSS3
- Bootstrap 5
- Vanilla JavaScript (ES6+)
- PHP 8.1 or later, using PDO
- MySQL 8.0 or compatible MariaDB
- Apache through XAMPP for local development

## Architecture
Use a small, understandable PHP application with separation of concerns:
- **Views:** HTML templates rendered by PHP.
- **Controllers:** handle HTTP requests and coordinate application logic.
- **Services:** business rules, search/filter logic, audit logging.
- **Repositories/models:** PDO queries and database access.
- **Middleware/helpers:** authentication, authorization, CSRF, validation and shared utilities.
- **Assets:** CSS, JavaScript, icons if locally available.
- **Migrations/seeds:** repeatable schema setup and clearly labeled demo data.

Do not place the entire application in one PHP file. Avoid overengineering: no mandatory framework, ORM or build pipeline.

## Suggested project structure
```text
ums-edugate/
├── app/
│   ├── Controllers/
│   ├── Services/
│   ├── Repositories/
│   ├── Middleware/
│   ├── Validators/
│   └── Views/
│       ├── layouts/
│       ├── auth/
│       ├── dashboard/
│       ├── countries/
│       ├── universities/
│       ├── campuses/
│       ├── intakes/
│       ├── application_rounds/
│       ├── programs/
│       ├── requirements/
│       ├── fees/
│       ├── scholarships/
│       ├── documents/
│       ├── visas/
│       ├── sources/
│       ├── reports/
│       └── audit/
├── config/
│   ├── config.example.php
│   └── database.php
├── database/
│   ├── migrations/
│   └── seeds/
├── public/
│   ├── index.php
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── img/
│   └── .htaccess
├── routes/
│   └── web.php
├── storage/
│   └── logs/
├── tests/
├── .gitignore
├── README.md
├── PROJECT_STATUS.md
└── DECISIONS.md
```

For simple XAMPP deployment, Apache's document root should point to `public/` where possible. If that is impractical locally, document a safe alternative; do not expose `config/`, `database/`, `storage/` or private logs to the browser.

---

# 4. Design and user experience requirements

## Visual direction
- Professional education-consultancy dashboard.
- Clean, restrained palette: dark navy/slate text, light neutral backgrounds, one primary indigo/blue accent, green for success, amber for warnings, red for destructive/error states.
- Bootstrap 5 grid and components.
- Consistent spacing, typography, form labels and button hierarchy.
- Responsive desktop, tablet and mobile layouts.
- Clear focus states and keyboard-accessible controls.
- Avoid large gradients, distracting animations, or overdecorated cards.
- Use icons only when the icon library is included and loaded correctly; otherwise use text labels.
- Every table must have loading, empty, error and populated states.
- Destructive actions must use a confirmation dialog.
- Forms must show field-level validation errors and preserve entered values after validation failures.
- Use pagination rather than rendering thousands of rows at once.
- Include breadcrumbs and a consistent top bar/sidebar navigation.
- Show “Sample/Demo Data” badges on demo records.

## Global layout
- Login page.
- Authenticated application shell: sidebar/top navigation, page title, breadcrumbs, main content.
- Flash messages for success, validation failure and system errors.
- Search filters should be clearly separated from results.
- Tables should support sorting where useful and pagination.
- Detail pages should show related records in tabs or clearly labeled sections.
- Provide a print-friendly profile view without sidebar/buttons.

---

# 5. Functional modules

## 5.1 Dashboard
Show:
- Total active countries.
- Total active universities.
- Total active programs.
- Total active scholarships.
- Upcoming application deadlines.
- Recently updated records.
- Records that need verification / are stale.
- Quick links to add a university, program, fee or scholarship.

Metrics must be computed from the database and clearly define which statuses are counted. Do not hardcode KPI values.

## 5.2 Countries
Fields:
- Country ID (internal primary key; readable unique code may be generated).
- Country name.
- Region.
- Default currency.
- Official language(s).
- Active flag.
- Notes.
- Status.
- Source URL.
- Last checked date.
- Data flag (e.g. SAMPLE, NEEDS_VERIFICATION, VERIFIED).
- Created/updated timestamps and user references.

Features: list, search, view, create, edit, archive/restore, source/verification metadata.

## 5.3 Universities
Fields:
- University ID.
- Country relationship.
- University name.
- City.
- University type.
- Official website.
- International admissions website.
- Logo URL (optional).
- Ranking (optional; store ranking source/year separately if later required).
- Accreditation.
- Notes.
- Status.
- Source URL.
- Last checked.
- Data flag.
- Created/updated timestamps and user references.

Features: list, filters, detail profile, create/edit/archive, related campus/intake/program/fee/scholarship/requirements/source views.

## 5.4 Campuses
Fields:
- Campus ID.
- University relationship.
- Campus name.
- City.
- Address.
- Notes.
- Status.
- Source URL and last checked.
- Data flag and audit metadata.

Features: filter by university/country; create/edit/archive; protect against invalid parent references.

## 5.5 Intakes and application rounds
Intake fields:
- Intake ID, university, intake name, term, year, intake type, applicable degree, campus, rolling admission, notes, status and source metadata.

Application-round fields:
- Round ID, intake, university, application round, application start/end, interview start/end, result date, deposit deadline, enrollment deadline, notes, status and source metadata.

Validation:
- End dates cannot be earlier than corresponding start dates.
- Dates must be stored consistently in MySQL DATE or DATETIME fields.
- Do not assume that every university uses the same intake calendar.
- Show upcoming, closing soon, passed and unknown deadline states.
- Do not fabricate a date when no verified date exists.

## 5.6 Programs
Fields:
- Program ID.
- University relationship.
- Campus (optional).
- Degree level.
- Faculty.
- Department.
- Program name.
- Major/field.
- Duration.
- Study mode.
- Language of instruction.
- Intakes offered.
- Notes.
- Status and source metadata.

Features: CRUD, filters, detail page and links to academic/language requirements, fees, scholarships and documents.

## 5.7 Academic requirements
Fields:
- Requirement ID.
- University.
- Program (specific program or explicitly defined “ALL programs” scope).
- Degree level.
- Minimum GPA.
- GPA scale.
- Previous degree required.
- Required background.
- Minimum percentage.
- Work experience.
- Entrance exam (GRE/GMAT/SAT).
- Portfolio/interview.
- Other requirements.
- Status and source metadata.

Business rule: “ALL programs” must be represented as an explicit scope, not as an invalid Program ID string that breaks a foreign key. If the program-specific record and university-wide default both exist, show both scopes clearly and define precedence: program-specific rule first, general rule as fallback. Never silently merge contradictory requirements.

## 5.8 Language requirements
Fields:
- Language requirement ID.
- University.
- Program (specific or all-program scope).
- Degree level.
- Test (IELTS, TOEFL iBT, PTE, TOPIK, other).
- Minimum overall score.
- Sub-score requirements.
- Waiver condition.
- Notes.
- Status and source metadata.

Validation:
- Numeric scores must be valid for the selected test.
- Do not assume IELTS 0.5 increments for every test.
- Allow multiple test rows per program.
- Waiver conditions must be displayed as text and must not be treated as automatic approval.
- Keep test score scales and sub-score fields understandable.

## 5.9 Fees
Fields:
- Fee ID.
- University.
- Program (specific or all-program scope).
- Degree level.
- Fee type.
- Amount.
- Currency.
- Fee period (semester/year/program/application/one-time/other).
- Notes.
- Status and source metadata.

Validation:
- Amount must be numeric and non-negative unless a specifically documented exception exists.
- Currency must use a consistent currency code.
- Display fee period and currency with every amount.
- Do not add fees in different currencies or periods without explicit conversion and normalization rules.
- Do not calculate a total cost of attendance unless the required components and periods are known.

## 5.10 Scholarships and tiers
Scholarship fields:
- Scholarship ID.
- University.
- Program or all-program scope.
- Degree level.
- Scholarship name.
- Scholarship type.
- Award mode (percentage, fixed amount, tuition waiver, stipend, other).
- Eligibility.
- Minimum GPA.
- Scholarship percentage.
- Amount/currency.
- Duration.
- Renewal requirement.
- Notes.
- Status and source metadata.

Tier fields:
- Tier ID, scholarship relationship, tier name, minimum GPA, IELTS, TOEFL iBT, PTE, TOPIK, other test/score, percentage, amount/currency, notes, status and source metadata.

Validation:
- Percentage must be 0–100.
- Fixed amount must be non-negative and include currency.
- Clearly distinguish “up to X%” from guaranteed awards.
- Show eligibility, renewal conditions and source together.
- Avoid implying that meeting one score guarantees a scholarship unless the official source explicitly says so.

## 5.11 Document requirements
Fields:
- Document requirement ID.
- University.
- Program or all-program scope.
- Degree level.
- Document type.
- Requirement level (mandatory, optional, conditional).
- Specification.
- Notes.
- Status and source metadata.

Features: program-specific and university-wide document checklist; filter by degree/program; clear mandatory/conditional labels.

## 5.12 Visa information
Fields:
- Visa ID.
- Country.
- Visa type.
- Financial proof.
- Processing time.
- Work rights.
- Official visa URL.
- Notes.
- Status and source metadata.

Disclaimer: show that visa rules change and users must confirm with the relevant government/embassy source. Do not present the tool as legal advice or guarantee visa approval.

## 5.13 Country-specific requirements
Fields:
- Requirement ID.
- Country.
- University (optional; null means country-wide).
- Degree level.
- Category.
- Requirement.
- Details.
- Mandatory flag.
- Notes.
- Status and source metadata.

Features: filter by country, university, degree and category; make the scope visible.

## 5.14 Sources and verification
Fields:
- Source ID.
- Related university/country/record where applicable.
- Source type.
- Title.
- URL.
- Last checked date.
- Checked by.
- Notes.
- Status and data flag.

Important: choose one canonical source URL and one canonical verification date in the MySQL design. Do not create ambiguous duplicate columns such as `URL`, `Source URL`, `Last Checked` and `Last Checked On` without a documented distinction. Support multiple sources per record where necessary.

Verification statuses:
- `NEEDS_VERIFICATION`
- `VERIFIED`
- `STALE`
- `SOURCE_UNAVAILABLE`
- `SAMPLE`

Only mark a record `VERIFIED` when a staff member has checked a reliable source and recorded the date and source. A URL existing alone does not prove verification.

## 5.15 Audit log
Record:
- Timestamp.
- User.
- Action.
- Entity/table.
- Record ID.
- Relevant old/new values (JSON text is acceptable if safely encoded).
- Optional request metadata where appropriate.

Audit log is append-only from the application UI. Ordinary users must not be able to edit or delete audit entries. Do not log passwords, session tokens or other secrets.

---

# 6. MySQL database design requirements

Create normalized relational tables. Use InnoDB, `utf8mb4`, primary keys, foreign keys, indexes and appropriate `NOT NULL` constraints.

Minimum tables:
1. `users`
2. `countries`
3. `universities`
4. `campuses`
5. `intakes`
6. `application_rounds`
7. `programs`
8. `academic_requirements`
9. `language_requirements`
10. `fees`
11. `scholarships`
12. `scholarship_tiers`
13. `document_requirements`
14. `visa_information`
15. `country_specific_requirements`
16. `sources`
17. `audit_logs`
18. `settings` or reference tables where appropriate

## Relationship rules
- One country has many universities.
- One university has many campuses, intakes, programs and related requirements.
- One intake can have many application rounds.
- One program can have multiple academic requirements, language requirements, fee rows, scholarship links and document requirements.
- One scholarship can have multiple tiers.
- A source can be linked to a country, university or specific data record. Use a clear linking design; do not create fragile polymorphic relationships without documenting their constraints.
- Foreign-key deletion behavior must be intentional. Prefer archive/soft-delete for business records rather than cascading accidental data loss.
- Use unique constraints for stable identifiers and prevent obvious duplicate country names/university names within the appropriate scope.

## All-program scope
Do not put the literal string `ALL` into a numeric foreign-key column. Use either:
- a nullable program foreign key plus an explicit `scope_type` (`PROGRAM` or `UNIVERSITY_ALL`), or
- a separate scope field that has a check constraint and validated application logic.

Choose one approach, document it, and use it consistently for academic requirements, language requirements, fees, scholarships and documents.

## IDs
Use internal integer primary keys for joins. Add readable public codes only if useful. Do not make display names the primary key.

## Timestamps
Use `created_at`, `updated_at`, `created_by`, `updated_by` where applicable. Store dates consistently. Document timezone assumptions.

## Database deliverables
- `schema.sql` or migration files.
- Seed script with only clearly marked demo data.
- ER diagram in Mermaid or equivalent text form.
- Database setup instructions.
- No real credentials in committed SQL files.

---

# 7. Search and filtering behavior

## University search
Filters:
- Country
- University name (partial match)
- City
- University type
- Degree level/program availability
- Intake term/year
- Active/status
- Verification status

## Program search
Filters:
- Country
- University
- Campus
- Degree level
- Faculty/department
- Program name/major
- Intake term/year
- Study mode
- Language of instruction
- Language test type and minimum score
- GPA threshold where data is comparable
- Tuition minimum/maximum
- Currency and fee period
- Scholarship availability/type
- Verification status

## Filter rules
- Multiple filters combine with AND unless a field explicitly allows multi-select OR behavior.
- Empty filters are ignored.
- “Clear filters” restores default state.
- Sort order must be whitelisted server-side.
- Use pagination and show total result count.
- Persist filters in query parameters where practical so a search can be bookmarked.
- Validate all numeric ranges server-side.
- Do not compare GPA values from different scales as if they were directly comparable.
- Do not compare tuition values across different currencies/periods without normalization.
- If a required data field is missing, label the result “Not provided” rather than treating it as zero or as a failed requirement.
- Do not return unbounded results. Set a reasonable page size (default 25; maximum 100).
- Use indexes for frequently filtered foreign keys and status columns.

---

# 8. Authentication, validation and application security

Security is not the product's primary business feature, but baseline safe coding is mandatory.

- Use PHP sessions with secure cookie settings appropriate to the environment.
- Store password hashes with `password_hash()` and verify with `password_verify()`.
- Use PDO prepared statements for all user-supplied values.
- Escape output using `htmlspecialchars()` by default.
- Add CSRF tokens to state-changing forms.
- Enforce role permissions on the server for every create/update/archive action.
- Validate URLs, numbers, dates, enum values and foreign keys on the server.
- Do not trust JavaScript-only validation.
- Keep database credentials in a private config file/environment variable, not in public web files.
- Exclude private config, logs, dumps and secrets from version control.
- Do not display stack traces or SQL errors to users; write useful diagnostic details to a protected log.
- Use POST for state changes and redirect-after-POST where appropriate.
- Add a confirmation step for archive/restore actions.
- Do not permanently delete records by default.
- Do not log passwords, CSRF tokens or session IDs.
- Include a short security section in README.

---

# 9. Non-functional requirements

## Performance
- Common list/search pages should feel responsive on a typical local XAMPP environment.
- Use server-side pagination.
- Add indexes based on actual queries.
- Avoid N+1 query patterns on profile pages.
- Do not load every database row into JavaScript.
- Add caching only if measurements show it is useful; correctness comes first.

## Reliability
- Validate data before writing.
- Use transactions for multi-table operations.
- Show user-friendly errors and preserve form values after validation errors.
- Avoid partial saves when a workflow requires multiple related writes.
- Log failures without exposing private information.

## Accessibility
- Proper labels for every form control.
- Keyboard-operable navigation and modal controls.
- Visible focus states.
- Sufficient contrast.
- Do not use color alone to convey verification or error status.

## Maintainability
- Keep functions focused and reasonably small.
- Use consistent naming conventions.
- Comment business rules, not every obvious line.
- Keep SQL schema/migrations versioned.
- Keep `README.md`, `PROJECT_STATUS.md` and `DECISIONS.md` up to date.
- Avoid duplicate business logic across multiple pages.

---

# 10. Required pages and routes

Use clean route names; exact routing implementation may use a small PHP front controller.

Public/auth:
- `GET /login`
- `POST /login`
- `POST /logout`

Dashboard:
- `GET /dashboard`

Countries:
- `GET /countries`
- `GET /countries/create`
- `POST /countries`
- `GET /countries/{id}`
- `GET /countries/{id}/edit`
- `POST /countries/{id}/update`
- `POST /countries/{id}/archive`
- `POST /countries/{id}/restore`

Universities:
- `GET /universities`
- `GET /universities/create`
- `POST /universities`
- `GET /universities/{id}`
- `GET /universities/{id}/edit`
- `POST /universities/{id}/update`
- `POST /universities/{id}/archive`
- `POST /universities/{id}/restore`

Equivalent list/create/detail/edit/update/archive routes for campuses, intakes, application rounds, programs, requirements, fees, scholarships, document requirements, visa information and country-specific requirements.

Sources:
- `GET /sources`
- `GET /sources/create`
- `POST /sources`
- `GET /sources/{id}/edit`
- `POST /sources/{id}/update`

Search:
- `GET /search/universities`
- `GET /search/programs`

Reports:
- `GET /reports/deadlines`
- `GET /reports/verification`
- `GET /universities/{id}/print`

Administration:
- `GET /admin/users`
- `GET /admin/settings`
- `GET /admin/audit-logs`

Do not implement every route in Phase 1. Add routes module-by-module according to the phase plan. All unsupported routes should return a deliberate 404/403, not a blank page.

---

# 11. The 15-phase implementation plan

## Phase 1 — Repository inspection and project foundation
**Goal:** Create a stable starting point without implementing business modules yet.

Tasks:
- Inspect all existing files if continuing from an existing project.
- Create the folder structure.
- Create `README.md`, `PROJECT_STATUS.md`, `DECISIONS.md` and `.gitignore`.
- Add configuration example and database connection abstraction.
- Create a basic PHP front controller and 404 page.
- Confirm PHP/MySQL versions and document XAMPP setup.
- Do not copy the Google Apps Script into PHP blindly; translate business rules intentionally.

Acceptance:
- Homepage/front controller loads in local environment.
- Database connection success/failure is handled clearly.
- Private config is not web-accessible.
- Project setup steps are documented.
- No business data or feature is falsely claimed complete.

## Phase 2 — MySQL schema, migrations and demo seed
**Goal:** Create a normalized database.

Tasks:
- Design the ER diagram.
- Create all core tables and foreign keys.
- Add indexes and unique constraints.
- Implement migrations or a repeatable `schema.sql`.
- Create a separate seed script for demo data.
- Mark every demo record `SAMPLE`.
- Include source and verification fields.
- Implement explicit all-program scope.
- Add schema version tracking.

Acceptance:
- Fresh database can be created from documented steps.
- Foreign keys work.
- Seed can run without creating duplicates or corrupting schema.
- No fake data is presented as verified.
- ER diagram matches the actual schema.

## Phase 3 — Shared UI shell and responsive design
**Goal:** Build the consistent interface.

Tasks:
- Create Bootstrap layout, sidebar, topbar, breadcrumb, page heading and footer.
- Create reusable alert, badge, table, pagination, modal and form components.
- Create responsive login shell and authenticated shell.
- Add empty/loading/error states.
- Add print stylesheet.
- Add navigation placeholders only for modules not yet implemented; mark them “Coming in a later phase” or hide them.

Acceptance:
- Layout works at desktop, tablet and mobile widths.
- Navigation does not contain dead links presented as completed features.
- Form labels and focus states are usable.
- No horizontal overflow on common screens.

## Phase 4 — Authentication and role-based access
**Goal:** Implement safe staff login and session handling.

Tasks:
- Build users table and create first admin using a documented CLI/setup flow.
- Use password hashing and verification.
- Add login/logout and session rotation.
- Add middleware/helpers for Admin, Editor and Read-only roles.
- Protect all authenticated routes.
- Add access-denied page.
- Do not provide public staff self-registration.

Acceptance:
- Valid user can log in and log out.
- Invalid credentials are rejected.
- Read-only user cannot access write endpoints directly.
- Unauthenticated user cannot open private routes.
- Passwords are never stored in plaintext.

## Phase 5 — Countries and universities
**Goal:** Implement the two core master-data modules.

Tasks:
- Country list, search, create, detail, edit, archive and restore.
- University list, search, create, detail, edit, archive and restore.
- Country/university relationship validation.
- Website URL validation.
- Source and verification metadata.
- Pagination and duplicate detection.
- Audit log entries for changes.

Acceptance:
- CRUD workflows work end-to-end.
- Invalid country references are rejected.
- Duplicate policy is enforced and documented.
- Archived records are excluded from default active lists.
- Audit log records create/update/archive actions.

## Phase 6 — Campuses, intakes and application rounds
**Goal:** Implement location and application-calendar data.

Tasks:
- Campus CRUD with university filter.
- Intake CRUD with term, year, type, campus and degree scope.
- Application-round CRUD with all date fields.
- Upcoming/closing-soon/passed/unknown deadline labels.
- Validate start/end dates.
- Link each round to a valid intake and university.

Acceptance:
- Parent-child relationships are enforced.
- Invalid date ranges are rejected.
- Missing deadlines remain unknown, never fabricated.
- Deadline report returns the expected records for test dates.

## Phase 7 — Programs and program detail
**Goal:** Implement the main academic catalog.

Tasks:
- Program CRUD.
- Filters by country, university, campus, degree, major, mode and language.
- Program detail page with related data tabs/placeholders.
- Handle inactive/archived universities correctly.
- Add indexes for search fields.
- Implement program-level source metadata.

Acceptance:
- Programs can be created and edited.
- Search filters combine according to the documented rules.
- Pagination and result count work.
- A program cannot reference a nonexistent university/campus.

## Phase 8 — Academic and language requirements
**Goal:** Store academic eligibility and language-test requirements accurately.

Tasks:
- Academic requirement CRUD.
- Language requirement CRUD.
- Support multiple language tests per program.
- Support explicit program-specific and all-program scope.
- Validate GPA scale and test score format.
- Show program-specific rule first, with university-wide rule as fallback.
- Display missing values as “Not provided”.

Acceptance:
- Contradictory general/specific requirements are not silently merged.
- Test score ranges are validated server-side.
- Multiple tests can be stored for one program.
- Scope is clear on list and detail pages.

## Phase 9 — Fees, scholarships and scholarship tiers
**Goal:** Implement costs and financial awards.

Tasks:
- Fee CRUD with currency and period.
- Scholarship CRUD with award mode, eligibility, amount/percentage and renewal requirements.
- Scholarship tier CRUD with test-score/GPA criteria.
- Validate currency, non-negative amounts and percentage bounds.
- Show “up to” and conditional award language accurately.
- Do not total mixed currencies or fee periods without a documented rule.

Acceptance:
- Fees always display currency and period.
- Scholarship percentages outside 0–100 are rejected.
- Tier parent relationships are enforced.
- Missing fee or scholarship information is not interpreted as zero or “not available”.

## Phase 10 — Document, visa and country-specific requirements
**Goal:** Complete the requirements catalog.

Tasks:
- Document requirement CRUD and checklist display.
- Visa information CRUD with official URL.
- Country-specific requirements CRUD.
- Filter by country, university, degree and category.
- Show mandatory/optional/conditional status.
- Display visa disclaimer and official source links.

Acceptance:
- Required fields and parent relationships are validated.
- Country-wide requirements are distinguishable from university-specific ones.
- External URLs are safely rendered.
- Visa pages do not claim guaranteed approval or replace official advice.

## Phase 11 — Sources, verification and stale-data workflow
**Goal:** Make data provenance visible and maintainable.

Tasks:
- Source CRUD and linking.
- Support multiple sources per relevant record.
- Add verification status, last-checked date and checked-by.
- Add report for missing source, missing checked date, stale records and sample records.
- Define a configurable staleness threshold; default 180 days unless the user chooses another value.
- Do not automatically mark data verified simply because a URL exists.
- Ensure source schema has one canonical URL and verification date per source record.

Acceptance:
- Staff can identify unverified/stale/sample records.
- Verification report matches fixture data.
- Marking a record verified requires source and check date.
- Source history is preserved where appropriate.

## Phase 12 — Dashboard, search and filters
**Goal:** Build the real operational dashboard and combined search.

Tasks:
- Calculate dashboard KPIs from database queries.
- Add upcoming deadlines, recently updated records and verification alerts.
- Complete university search and program search.
- Implement all filters described in Section 7.
- Add sorting whitelist, pagination and clear filters.
- Use parameterized queries and indexes.
- Show currency/period and verification state in search results.

Acceptance:
- KPI values match known fixture data.
- Filters combine correctly.
- Sorting cannot accept arbitrary SQL input.
- Pagination is stable and total result count is correct.
- Missing information is shown explicitly.

## Phase 13 — University profile, print view and reports
**Goal:** Create useful profile and reporting workflows.

Tasks:
- University profile with overview, campuses, intakes, programs, fees, scholarships, requirements, documents, visa notes and sources.
- Print-friendly profile with no navigation controls.
- Deadline report.
- Verification/stale-data report.
- Export current filtered results to CSV only if included in the approved scope; protect against spreadsheet formula injection.
- Do not implement PDF generation using an unapproved external library; browser print-to-PDF is acceptable for the first release.

Acceptance:
- Print preview is readable and avoids clipping key fields.
- Reports use real database data.
- Empty report states are clear.
- CSV export, if implemented, respects filters and safe cell escaping.

## Phase 14 — Audit log, admin settings and usability refinement
**Goal:** Finish administration and improve consistency.

Tasks:
- Audit-log list with filters by user/action/date/entity.
- Admin user management and role changes.
- Settings/reference-list management where approved.
- Consistent server-side validation and error messages.
- Add confirmation dialogs, empty states, success alerts and preserved form input.
- Review keyboard accessibility and responsive behavior.
- Add a basic error log policy.

Acceptance:
- Only Admin can manage users and global settings.
- Audit log is read-only through the UI.
- All write actions are logged.
- No secret values appear in logs.
- Main workflows are usable on desktop and mobile.

## Phase 15 — Full QA, bug fixing, documentation and release
**Goal:** Verify the complete application and prepare a reproducible handoff.

Tasks:
- Run a clean install from the README.
- Test every create/edit/archive/restore flow.
- Test role permissions directly through HTTP requests, not only by hiding buttons.
- Test SQL-injection-shaped input, invalid IDs, invalid dates, invalid URLs, missing required values and CSRF failure.
- Test relationships and delete/archive behavior.
- Test search combinations, sorting, pagination and no-result cases.
- Test stale/source reports and deadline boundaries.
- Test print view and CSV export if implemented.
- Check browser console and PHP logs.
- Remove unused files and debug output.
- Update README, PROJECT_STATUS and DECISIONS.
- List any untested items honestly.

Acceptance:
- All critical workflows pass.
- Fresh installation succeeds using documented steps.
- No known critical bug remains unresolved.
- No credentials or real secrets are included in source control.
- Final release notes list features, limitations and test evidence.

---

# 12. Test strategy

Use a small repeatable test dataset with clearly labeled records, for example:
- 2 sample countries.
- 3 sample universities.
- 2 campuses.
- 3 intakes with different deadline states.
- 5 programs across multiple degree levels.
- Multiple language tests for one program.
- Program-specific and university-wide requirements.
- Fees in different currencies/periods to test display behavior.
- One scholarship with multiple tiers.
- Records with verified, stale, missing-source and sample statuses.

These are test fixtures only, not factual university recommendations.

## Critical test cases
1. Unauthenticated user requests `/dashboard` → redirected to login.
2. Read-only user submits a direct POST to create a country → denied server-side.
3. Duplicate country or university → handled by documented duplicate policy.
4. Program points to nonexistent university → rejected.
5. Application end date before start date → rejected.
6. Invalid scholarship percentage (e.g. 150) → rejected.
7. Negative fee amount → rejected.
8. Unsupported sort key → safe fallback or validation error.
9. Empty search filters → paginated default results.
10. Multiple filters → expected AND/OR behavior.
11. Program-specific requirement conflicts with university-wide default → both scopes are displayed and precedence is explicit.
12. Missing tuition → shown as “Not provided,” not zero.
13. Missing source or check date → shown in verification report.
14. Stale verification date → marked stale based on configured threshold.
15. Archive university → dependent-record behavior follows documented policy.
16. CSRF token missing/invalid → write action rejected.
17. SQL-like text in search input → treated as data, not executable SQL.
18. Print view → no sidebar/action buttons in print output.
19. Page size above maximum → capped or rejected.
20. Failed database connection → friendly error, details only in protected log.

---

# 13. Data migration from the supplied workbook

The workbook is a reference and may contain sample values. Migration must be explicit, not automatic without review.

## Migration procedure
1. Inspect the workbook headers and map them to MySQL fields.
2. Create a mapping document from each workbook sheet to a table.
3. Normalize country/university names and map source IDs to database IDs.
4. Convert “ALL” program scope into the explicit scope model.
5. Normalize date, currency and numeric fields.
6. Import only approved rows.
7. Mark sample rows as `SAMPLE`; never silently upgrade them to verified.
8. Store source URL, checked date and checked-by consistently.
9. Run duplicate and orphan-reference checks after import.
10. Generate an import report: imported, skipped, duplicate, invalid, needs verification.
11. Keep the original workbook unchanged.
12. Do not import the old audit log as if it were generated by the new system unless the user explicitly requests historical migration.

## Workbook sheet-to-table mapping
- `02_Countries` → `countries`
- `03_Universities` → `universities`
- `04_Campuses` → `campuses`
- `05_Intakes` → `intakes`
- `05b_Application_Rounds` → `application_rounds`
- `06_Programs` → `programs`
- `07_Academic_Requirements` → `academic_requirements`
- `08_Language_Requirements` → `language_requirements`
- `09_Fees` → `fees`
- `10_Scholarships` → `scholarships`
- `11_Scholarship_Tiers` → `scholarship_tiers`
- `12_Document_Requirements` → `document_requirements`
- `13_Visa_Information` → `visa_information`
- `14_Sources` → `sources`
- `15_Audit_Log` → `audit_logs` only if historical import is approved
- `16_Country_Specific_Requirement` → `country_specific_requirements`
- `01_Settings` and `_Lists` → settings/reference tables as appropriate
- `HOME`, search, data-entry and print sheets → UI/workflow reference, not business tables

---

# 14. Definition of Done

The product is considered ready for first release only when:
- All 15 phases are completed or any exclusions are explicitly approved.
- A clean installation can be performed from the README.
- The database schema and ER diagram are up to date.
- All primary modules have working list/detail/create/edit/archive workflows as applicable.
- Search, pagination, filtering and sorting work correctly.
- Source and verification metadata are visible.
- Dashboard metrics are database-driven.
- Roles are enforced on the server.
- No sample/demo record is misrepresented as verified real data.
- Critical test cases pass or are explicitly documented as blocked.
- No known critical issue is hidden.
- The final handoff includes project files, schema/migrations, seed instructions, setup guide, test report, and known limitations.

---

# 15. Master prompt to start implementation

Copy this prompt into the coding AI together with the project files:

> You are building UMS EduGate using HTML, CSS, Bootstrap 5, vanilla JavaScript, PHP 8+ and MySQL 8+/MariaDB. Follow the attached PRD exactly. The PRD defines the product scope and 15 phases. Do not generate the whole application at once.
>
> Start with **Phase 1 only**. First inspect the files and report what exists. Then implement only the Phase 1 deliverables. Do not begin Phase 2 until I explicitly approve.
>
> Anti-hallucination rules:
> - Do not invent features, facts, fields, routes or test results.
> - Do not claim that code works unless you executed the relevant test.
> - Keep PROJECT_STATUS.md and DECISIONS.md updated.
> - If a requirement is ambiguous, identify it instead of guessing.
> - Use PDO prepared statements, server-side validation and role checks in the relevant phases.
> - Do not add a different framework or stack.
> - Clearly label all demo data.
> - Keep changes targeted and explain every file created/changed.
>
> At the end of the phase, provide the required status report and exact manual test steps. Stop and wait for my approval.

---
