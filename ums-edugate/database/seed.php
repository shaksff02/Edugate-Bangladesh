<?php
/**
 * UMS EduGate — demo seed script (Phase 2)
 *
 * Usage:
 *   php database/seed.php            Insert/refresh SAMPLE demo data.
 *   php database/seed.php --status   Show current row counts only.
 *
 * Guarantees (PRD Phase 2 acceptance):
 *   - Every seeded record carries is_sample = 1 and confidence_level =
 *     'UNVERIFIED' — no fake data is ever presented as verified.
 *   - Idempotent: uses INSERT ... ON DUPLICATE KEY UPDATE on the natural
 *     unique keys, so re-running never creates duplicates or corrupts
 *     the schema. Records edited by a real user (is_sample flipped to 0)
 *     are NOT overwritten back to sample values.
 *   - No credentials in this file; connection via config/config.local.php.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

$pdo = Database::connection();

// Make sure migrations have been applied before seeding.
try {
    $pdo->query('SELECT 1 FROM countries LIMIT 1');
} catch (PDOException $e) {
    fwrite(STDERR, "Schema not found. Run `php database/migrate.php` first.\n");
    exit(1);
}

$statusMode = in_array('--status', $argv ?? [], true);

$tables = [
    'users', 'countries', 'sources', 'universities', 'campuses', 'intakes',
    'application_rounds', 'programs', 'academic_requirements', 'language_requirements',
    'fees', 'scholarships', 'scholarship_tiers', 'document_requirements',
    'visa_information', 'country_specific_requirements', 'settings', 'audit_logs',
];

if ($statusMode) {
    foreach ($tables as $t) {
        $n = (int) $pdo->query("SELECT COUNT(*) FROM {$t}")->fetchColumn();
        printf("  %-30s %d row(s)\n", $t, $n);
    }
    exit(0);
}

/*
|--------------------------------------------------------------------------
| Demo dataset — clearly fictional university-admissions examples.
| All rows are SAMPLE (is_sample=1, UNVERIFIED). They exist to exercise
| the UI in later phases and must be replaced with real researched data.
|--------------------------------------------------------------------------
*/

// ---- sources -------------------------------------------------------------
$sources = [
    ['Demo University Portal (fictional)', 'https://example-university.invalid/about', 'WEBSITE'],
    ['Demo Country Education Board (fictional)', 'https://example-board.invalid/admissions', 'WEBSITE'],
    ['Phone enquiry — demo line', null, 'PHONE'],
];
$srcId = [];
$ins = $pdo->prepare(
    'INSERT INTO sources (name, url, source_type, reliability, is_sample)
     VALUES (?, ?, ?, \'UNRATED\', 1)
     ON DUPLICATE KEY UPDATE source_type = VALUES(source_type), is_sample = IF(is_sample = 0, 0, 1)'
);
foreach ($sources as [$name, $url, $type]) {
    $ins->execute([$name, $url, $type]);
    $row = $pdo->prepare('SELECT id FROM sources WHERE name = ? AND (url = ? OR (url IS NULL AND ? IS NULL))');
    $row->execute([$name, $url, $url]);
    $srcId[$name] = (int) $row->fetchColumn();
}
echo '  sources: ' . count($sources) . " handled\n";

// ---- users ---------------------------------------------------------------
// Seed accounts use password_hash() — NEVER plaintext. These are throwaway
// demo credentials documented in README; change/disable them before any
// deployment that is reachable beyond localhost.
$users = [
    ['Demo Admin',  'admin@example.invalid',  'DEMO-ADMIN-CHANGE-ME', 'ADMIN'],
    ['Demo Editor', 'editor@example.invalid', 'DEMO-EDITOR-CHANGE-ME', 'EDITOR'],
    ['Demo Viewer', 'viewer@example.invalid', 'DEMO-VIEWER-CHANGE-ME', 'READ_ONLY'],
];
$ins = $pdo->prepare(
    'INSERT INTO users (name, email, password_hash, role, is_active)
     VALUES (?, ?, ?, ?, 1)
     ON DUPLICATE KEY UPDATE name = VALUES(name), role = VALUES(role)'
);
foreach ($users as [$name, $email, $plain, $role]) {
    $ins->execute([$name, $email, password_hash($plain, PASSWORD_DEFAULT), $role]);
}
echo '  users: ' . count($users) . " handled\n";

// ---- countries -----------------------------------------------------------
$countries = [
    ['United Kingdom', 'GB', 'GBR', 'Europe', 'GBP'],
    ['Australia',      'AU', 'AUS', 'Oceania', 'AUD'],
    ['Malaysia',       'MY', 'MYS', 'Asia', 'MYR'],
    ['Canada',         'CA', 'CAN', 'North America', 'CAD'],
];
$ins = $pdo->prepare(
    'INSERT INTO countries (name, iso2, iso3, region, currency_code, source_id, confidence_level, is_sample)
     VALUES (?, ?, ?, ?, ?, ?, \'UNVERIFIED\', 1)
     ON DUPLICATE KEY UPDATE iso2 = VALUES(iso2), iso3 = VALUES(iso3), region = VALUES(region),
                             currency_code = VALUES(currency_code), is_sample = IF(is_sample = 0, 0, 1)'
);
$cId = [];
foreach ($countries as [$name, $iso2, $iso3, $region, $cur]) {
    $ins->execute([$name, $iso2, $iso3, $region, $cur, $srcId['Demo Country Education Board (fictional)']]);
    $cId[$name] = (int) $pdo->query("SELECT id FROM countries WHERE name = " . $pdo->quote($name))->fetchColumn();
}
echo '  countries: ' . count($countries) . " handled\n";

// ---- universities --------------------------------------------------------
$universities = [
    ['United Kingdom', 'Example College of London (demo)', 'https://example-college-london.invalid', 'London', 'PRIVATE'],
    ['Australia',      'Southern Example University (demo)', 'https://southern-example.invalid', 'Melbourne', 'PUBLIC'],
    ['Malaysia',       'Kuala Lumpur Example Institute (demo)', 'https://kl-example.invalid', 'Kuala Lumpur', 'GOVERNMENT_AIDED'],
    ['Canada',         'Northern Example University (demo)', 'https://northern-example.invalid', 'Toronto', 'PUBLIC'],
];
$uNameSrc = $srcId['Demo University Portal (fictional)'];
$ins = $pdo->prepare(
    'INSERT INTO universities (country_id, name, website, city, type, source_id, confidence_level, is_sample)
     VALUES (?, ?, ?, ?, ?, ?, \'UNVERIFIED\', 1)
     ON DUPLICATE KEY UPDATE website = VALUES(website), city = VALUES(city), type = VALUES(type),
                             is_sample = IF(is_sample = 0, 0, 1)'
);
$uId = [];
// Re-fetch by natural key after each upsert (LAST_INSERT_ID is unreliable
// when ON DUPLICATE KEY UPDATE turns the insert into an update).
foreach ($universities as [$country, $name, $web, $city, $type]) {
    $ins->execute([$cId[$country], $name, $web, $city, $type, $uNameSrc]);
    $st = $pdo->prepare('SELECT id FROM universities WHERE country_id = ? AND name = ?');
    $st->execute([$cId[$country], $name]);
    $uId[$name] = (int) $st->fetchColumn();
}
echo '  universities: ' . count($universities) . " handled\n";

// ---- campuses ------------------------------------------------------------
$campuses = [
    ['Example College of London (demo)', 'Main Campus — Bloomsbury', 'London', 1],
    ['Example College of London (demo)', 'Docklands Campus', 'London', 0],
    ['Southern Example University (demo)', 'Parkville Main', 'Melbourne', 1],
    ['Kuala Lumpur Example Institute (demo)', 'Main Campus KL', 'Kuala Lumpur', 1],
    ['Northern Example University (demo)', 'Downtown Toronto', 'Toronto', 1],
];
$ins = $pdo->prepare(
    'INSERT INTO campuses (university_id, name, city, is_main, source_id, confidence_level, is_sample)
     VALUES (?, ?, ?, ?, ?, \'UNVERIFIED\', 1)
     ON DUPLICATE KEY UPDATE city = VALUES(city), is_main = VALUES(is_main)'
);
foreach ($campuses as [$uni, $name, $city, $main]) {
    $ins->execute([$uId[$uni], $name, $city, $main, $uNameSrc]);
}
echo '  campuses: ' . count($campuses) . " handled\n";

// ---- intakes + application rounds -----------------------------------------
$intakes = [
    ['Example College of London (demo)', 'September 2026', 2026, '2026-09-14', null],
    ['Southern Example University (demo)', 'Semester 1 2026', 2026, '2026-02-24', '2026-06-20'],
    ['Kuala Lumpur Example Institute (demo)', 'March Intake 2026', 2026, '2026-03-02', null],
    ['Northern Example University (demo)', 'Fall 2026', 2026, '2026-09-01', null],
];
$ins = $pdo->prepare(
    'INSERT INTO intakes (university_id, name, intake_year, starts_on, ends_on, source_id, confidence_level, is_sample)
     VALUES (?, ?, ?, ?, ?, ?, \'UNVERIFIED\', 1)
     ON DUPLICATE KEY UPDATE intake_year = VALUES(intake_year), starts_on = VALUES(starts_on), ends_on = VALUES(ends_on)'
);
$iId = [];
foreach ($intakes as [$uni, $name, $year, $start, $end]) {
    $ins->execute([$uId[$uni], $name, $year, $start, $end, $uNameSrc]);
    $st = $pdo->prepare('SELECT id FROM intakes WHERE university_id = ? AND name = ?');
    $st->execute([$uId[$uni], $name]);
    $iId[$uni . '|' . $name] = (int) $st->fetchColumn();
}
$rounds = [
    ['Example College of London (demo)', 'September 2026', 'Round 1', '2026-03-01 00:00:00', '2026-05-31 23:59:59', '2026-07-15'],
    ['Example College of London (demo)', 'September 2026', 'Round 2', '2026-06-01 00:00:00', '2026-07-31 23:59:59', '2026-08-20'],
    ['Southern Example University (demo)', 'Semester 1 2026', 'Round 1', '2025-10-01 00:00:00', '2025-12-15 23:59:59', '2026-01-20'],
    ['Northern Example University (demo)', 'Fall 2026', 'Round 1', '2026-01-15 00:00:00', '2026-03-15 23:59:59', '2026-04-30'],
];
$ins = $pdo->prepare(
    'INSERT INTO application_rounds (intake_id, name, opens_at, closes_at, result_date, source_id, confidence_level, is_sample)
     VALUES (?, ?, ?, ?, ?, ?, \'UNVERIFIED\', 1)
     ON DUPLICATE KEY UPDATE opens_at = VALUES(opens_at), closes_at = VALUES(closes_at), result_date = VALUES(result_date)'
);
foreach ($rounds as [$uni, $intake, $name, $open, $close, $result]) {
    $ins->execute([$iId[$uni . '|' . $intake], $name, $open, $close, $result, $uNameSrc]);
}
echo '  intakes: ' . count($intakes) . ', application_rounds: ' . count($rounds) . " handled\n";

// ---- programs --------------------------------------------------------------
$programs = [
    ['Example College of London (demo)', 'Main Campus — Bloomsbury', 'BSc Business Management', 'BACHELOR', 'Business', 36, 'English'],
    ['Example College of London (demo)', 'Main Campus — Bloomsbury', 'MSc Data Science', 'MASTER', 'Computing', 12, 'English'],
    ['Southern Example University (demo)', 'Parkville Main', 'Bachelor of Engineering (Honours)', 'BACHELOR', 'Engineering', 48, 'English'],
    ['Kuala Lumpur Example Institute (demo)', 'Main Campus KL', 'Diploma in Accounting', 'DIPLOMA', 'Business', 24, 'English'],
    ['Northern Example University (demo)', 'Downtown Toronto', 'Master of Computer Science', 'MASTER', 'Computing', 24, 'English'],
];
$ins = $pdo->prepare(
    'INSERT INTO programs (university_id, campus_id, name, level, field, duration_months, language_of_instruction, source_id, confidence_level, is_sample)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'UNVERIFIED\', 1)
     ON DUPLICATE KEY UPDATE field = VALUES(field), duration_months = VALUES(duration_months),
                             language_of_instruction = VALUES(language_of_instruction)'
);
$pId = [];
foreach ($programs as [$uni, $campus, $name, $level, $field, $months, $lang]) {
    $st = $pdo->prepare('SELECT id FROM campuses WHERE university_id = ? AND name = ?');
    $st->execute([$uId[$uni], $campus]);
    $campusId = $st->fetchColumn();
    $ins->execute([$uId[$uni], $campusId ?: null, $name, $level, $field, $months, $lang, $uNameSrc]);
    $st = $pdo->prepare('SELECT id FROM programs WHERE university_id = ? AND name = ? AND level = ?');
    $st->execute([$uId[$uni], $name, $level]);
    $pId[$uni . '|' . $name] = (int) $st->fetchColumn();
}
echo '  programs: ' . count($programs) . " handled\n";

// Helper for the five scope tables: resolve program id or null for UNIVERSITY_ALL.
function scope_ids(PDO $pdo, array $uId, array $pId, string $uni, ?string $prog): array {
    if ($prog === null) {
        return [$uId[$uni], null, 'UNIVERSITY_ALL'];
    }
    return [$uId[$uni], $pId[$uni . '|' . $prog], 'PROGRAM'];
}

// ---- academic_requirements --------------------------------------------------
$areq = [
    ['Example College of London (demo)', 'BSc Business Management', 'Two A-levels or equivalent plus GCSE Maths at grade C/4.', 2.5, 65.0],
    ['Example College of London (demo)', null, 'All undergraduate applicants need completed secondary education evidence.', null, 60.0],
    ['Southern Example University (demo)', 'Bachelor of Engineering (Honours)', 'Year 12 Maths and Physics, minimum 70% average.', 3.0, 70.0],
    ['Northern Example University (demo)', 'Master of Computer Science', 'Relevant bachelor degree with minimum GPA 3.0/4.0.', 3.0, null],
];
$ins = $pdo->prepare(
    'INSERT INTO academic_requirements (university_id, program_id, scope_type, requirement_text, min_gpa, min_percentage, source_id, confidence_level, is_sample)
     VALUES (?, ?, ?, ?, ?, ?, ?, \'UNVERIFIED\', 1)
     ON DUPLICATE KEY UPDATE requirement_text = VALUES(requirement_text), min_gpa = VALUES(min_gpa), min_percentage = VALUES(min_percentage)'
);
$n = 0;
foreach ($areq as [$uni, $prog, $text, $gpa, $pct]) {
    [$uid, $pid, $scope] = scope_ids($pdo, $uId, $pId, $uni, $prog);
    // Program-scoped rows get a deterministic anchor via text+program; emulate ODKU with explicit check.
    $st = $pdo->prepare('SELECT id FROM academic_requirements WHERE university_id = ? AND (program_id <=> ?) AND requirement_text = ?');
    $st->execute([$uid, $pid, $text]);
    if ($st->fetchColumn()) { $n++; continue; }
    $ins->bindValue(1, $uid); $ins->bindValue(2, $pid); $ins->bindValue(3, $scope);
    $ins->bindValue(4, $text); $ins->bindValue(5, $gpa); $ins->bindValue(6, $pct);
    $ins->bindValue(7, $uNameSrc);
    $ins->execute();
    $n++;
}
echo "  academic_requirements: {$n} handled\n";

// ---- language_requirements ---------------------------------------------------
$lreq = [
    ['Example College of London (demo)', null, 'IELTS', 6.5, 6.0],
    ['Example College of London (demo)', 'MSc Data Science', 'TOEFL_IBT', 90.0, 20.0],
    ['Southern Example University (demo)', null, 'IELTS', 6.5, 6.0],
    ['Kuala Lumpur Example Institute (demo)', 'Diploma in Accounting', 'IELTS', 5.5, 5.0],
    ['Northern Example University (demo)', 'Master of Computer Science', 'IELTS', 7.0, 6.5],
];
$ins = $pdo->prepare(
    'INSERT INTO language_requirements (university_id, program_id, scope_type, test_name, min_overall, min_each_band, validity_months, source_id, confidence_level, is_sample)
     VALUES (?, ?, ?, ?, ?, ?, 24, ?, \'UNVERIFIED\', 1)
     ON DUPLICATE KEY UPDATE min_overall = VALUES(min_overall), min_each_band = VALUES(min_each_band)'
);
$n = 0;
foreach ($lreq as [$uni, $prog, $test, $overall, $band]) {
    [$uid, $pid, $scope] = scope_ids($pdo, $uId, $pId, $uni, $prog);
    $ins->execute([$uid, $pid, $scope, $test, $overall, $band, $uNameSrc]);
    $n++;
}
echo "  language_requirements: {$n} handled\n";

// ---- fees ---------------------------------------------------------------------
$fees = [
    ['Example College of London (demo)', 'BSc Business Management', 'TUITION_PER_YEAR', 18500, 'GBP', 2026],
    ['Example College of London (demo)', 'MSc Data Science', 'TUITION_TOTAL', 24000, 'GBP', 2026],
    ['Example College of London (demo)', null, 'APPLICATION', 50, 'GBP', 2026],
    ['Southern Example University (demo)', 'Bachelor of Engineering (Honours)', 'TUITION_PER_YEAR', 38000, 'AUD', 2026],
    ['Kuala Lumpur Example Institute (demo)', 'Diploma in Accounting', 'TUITION_TOTAL', 15000, 'MYR', 2026],
    ['Northern Example University (demo)', 'Master of Computer Science', 'TUITION_PER_YEAR', 22000, 'CAD', 2026],
];
$ins = $pdo->prepare(
    'INSERT INTO fees (university_id, program_id, scope_type, fee_type, amount, currency_code, academic_year, source_id, confidence_level, is_sample)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'UNVERIFIED\', 1)'
);
$n = 0;
foreach ($fees as [$uni, $prog, $type, $amount, $cur, $year]) {
    [$uid, $pid, $scope] = scope_ids($pdo, $uId, $pId, $uni, $prog);
    $st = $pdo->prepare('SELECT id FROM fees WHERE university_id = ? AND (program_id <=> ?) AND fee_type = ? AND academic_year = ?');
    $st->execute([$uid, $pid, $type, $year]);
    if ($st->fetchColumn()) { $n++; continue; }
    $ins->execute([$uid, $pid, $scope, $type, $amount, $cur, $year, $uNameSrc]);
    $n++;
}
echo "  fees: {$n} handled\n";

// ---- scholarships + tiers -------------------------------------------------------
$insSch = $pdo->prepare(
    'INSERT INTO scholarships (university_id, program_id, scope_type, name, scholarship_type, coverage_summary, deadline_date, source_id, confidence_level, is_sample)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'UNVERIFIED\', 1)
     ON DUPLICATE KEY UPDATE coverage_summary = VALUES(coverage_summary), deadline_date = VALUES(deadline_date)'
);
$insTier = $pdo->prepare(
    'INSERT INTO scholarship_tiers (scholarship_id, tier_name, coverage_percent, criteria_text, slots_total, source_id, confidence_level, is_sample)
     VALUES (?, ?, ?, ?, ?, ?, \'UNVERIFIED\', 1)
     ON DUPLICATE KEY UPDATE coverage_percent = VALUES(coverage_percent), criteria_text = VALUES(criteria_text), slots_total = VALUES(slots_total)'
);
$sch = [
    ['Example College of London (demo)', null, 'International Excellence Award (demo)', 'MERIT', 'Up to 50% tuition remission', '2026-04-30',
        [['Tier A — 50%', 50.0, 'GPA equivalent ≥ 3.7', 10], ['Tier B — 25%', 25.0, 'GPA equivalent ≥ 3.3', 25]]],
    ['Southern Example University (demo)', 'Bachelor of Engineering (Honours)', 'Regional Access Grant (demo)', 'COUNTRY_SPECIFIC', 'AUD 10,000 per year', '2026-01-31',
        [['Standard grant', null, 'Open to selected partner regions', 40]]],
];
$n = 0;
foreach ($sch as [$uni, $prog, $name, $type, $summary, $deadline, $tiers]) {
    [$uid, $pid, $scope] = scope_ids($pdo, $uId, $pId, $uni, $prog);
    $insSch->execute([$uid, $pid, $scope, $name, $type, $summary, $deadline, $uNameSrc]);
    $st = $pdo->prepare('SELECT id FROM scholarships WHERE university_id = ? AND (program_id <=> ?) AND name = ?');
    $st->execute([$uid, $pid, $name]);
    $schId = (int) $st->fetchColumn();
    foreach ($tiers as [$tname, $pct, $criteria, $slots]) {
        $insTier->execute([$schId, $tname, $pct, $criteria, $slots, $uNameSrc]);
    }
    $n++;
}
echo "  scholarships: {$n} (+ tiers) handled\n";

// ---- document_requirements ---------------------------------------------------------
$docs = [
    ['Example College of London (demo)', null, 'Passport (valid ≥ 6 months)', 1, 'Colour scan, all pages.'],
    ['Example College of London (demo)', null, 'Academic transcripts (attested)', 1, 'Notarised + apostilled copies.'],
    ['Example College of London (demo)', 'MSc Data Science', 'CV / resume', 0, 'One page preferred.'],
    ['Southern Example University (demo)', null, 'IELTS/OET evidence report', 1, 'Test date within 24 months.'],
    ['Kuala Lumpur Example Institute (demo)', null, 'EMGS application receipt', 1, 'Value from official EMGS portal.'],
];
$ins = $pdo->prepare(
    'INSERT INTO document_requirements (university_id, program_id, scope_type, document_name, is_mandatory, format_notes, source_id, confidence_level, is_sample)
     VALUES (?, ?, ?, ?, ?, ?, ?, \'UNVERIFIED\', 1)
     ON DUPLICATE KEY UPDATE is_mandatory = VALUES(is_mandatory), format_notes = VALUES(format_notes)'
);
$n = 0;
foreach ($docs as [$uni, $prog, $doc, $mand, $notes]) {
    [$uid, $pid, $scope] = scope_ids($pdo, $uId, $pId, $uni, $prog);
    $ins->execute([$uid, $pid, $scope, $doc, $mand, $notes, $uNameSrc]);
    $n++;
}
echo "  document_requirements: {$n} handled\n";

// ---- visa_information ---------------------------------------------------------------
$visa = [
    ['United Kingdom', null, 'Student Route visa (demo)', 'COE → online application → biometrics → decision.', 'Show maintenance funds for 9 months.', 15, 0],
    ['Australia', null, 'Subclass 500 (demo)', 'CoE → ImmiAccount application → health check → grant.', 'Approx. AUD 29,710/year living funds.', 30, 0],
    ['Malaysia', null, 'EMGS Student Pass (demo)', 'EMGS tracking → VAL → entry permit.', 'As per institution letter.', 30, 0],
    ['Canada', null, 'Study Permit (demo)', 'LOA → online application → biometrics → pre-arrival medical.', 'CAD 20,635/year (outside Quebec) + first-year tuition.', 45, 0],
];
$ins = $pdo->prepare(
    'INSERT INTO visa_information (country_id, university_id, visa_category, process_summary, financial_requirement, processing_time_days, interview_required, source_id, confidence_level, is_sample)
     VALUES (?, NULL, ?, ?, ?, ?, ?, ?, \'UNVERIFIED\', 1)
     ON DUPLICATE KEY UPDATE process_summary = VALUES(process_summary), financial_requirement = VALUES(financial_requirement),
                             processing_time_days = VALUES(processing_time_days), interview_required = VALUES(interview_required)'
);
$n = 0;
foreach ($visa as [$country, , $cat, $summary, $fin, $days, $interview]) {
    $ins->execute([$cId[$country], $cat, $summary, $fin, $days, $interview, $srcId['Demo Country Education Board (fictional)']]);
    $n++;
}
echo "  visa_information: {$n} handled\n";

// ---- country_specific_requirements ----------------------------------------------------
$csr = [
    ['Bangladesh', 'United Kingdom', null, 'ATAS-style additional checks may apply for select science programs (demo note).'],
    ['Nigeria', 'United Kingdom', null, 'Tuberculosis screening certificate required for stays > 6 months (demo).'],
    ['India', 'Australia', null, 'Genuine Temporary Entrant (GTE) statement reviewed closely for postgraduate courses (demo).'],
];
// Ensure applicant-nationality countries exist as samples too.
$insCountry = $pdo->prepare(
    'INSERT INTO countries (name, confidence_level, is_sample) VALUES (?, \'UNVERIFIED\', 1)
     ON DUPLICATE KEY UPDATE is_sample = IF(is_sample = 0, 0, 1)'
);
foreach (['Bangladesh', 'Nigeria', 'India'] as $extra) {
    $insCountry->execute([$extra]);
    $st = $pdo->prepare('SELECT id FROM countries WHERE name = ?');
    $st->execute([$extra]);
    $cId[$extra] = (int) $st->fetchColumn();
}
$ins = $pdo->prepare(
    'INSERT INTO country_specific_requirements (country_id, university_id, program_id, requirement_text, source_id, confidence_level, is_sample)
     VALUES (?, ?, NULL, ?, ?, \'UNVERIFIED\', 1)'
);
$n = 0;
foreach ($csr as [$applicant, $dest, $uniName, $text]) {
    $uid = $uniName !== null ? $uId[$uniName] : null;
    $st = $pdo->prepare('SELECT id FROM country_specific_requirements WHERE country_id = ? AND (university_id <=> ?) AND requirement_text = ?');
    $st->execute([$cId[$applicant], $uid, $text]);
    if ($st->fetchColumn()) { $n++; continue; }
    $ins->execute([$cId[$applicant], $uid, $text, $srcId['Demo Country Education Board (fictional)']]);
    $n++;
}
echo "  country_specific_requirements: {$n} handled\n";

// ---- settings (reference lists mirroring workbook _Lists) -------------------------------
$settings = [
    ['program_levels', '["DIPLOMA","BACHELOR","MASTER","PHD","FOUNDATION","A_LEVEL","OTHER"]', 'JSON_LIST', 'Program level dropdown'],
    ['language_tests', '["IELTS","TOEFL_IBT","TOEFL_PBT","IELTS_ONLINE","PTE","DUOLINGO","OET","OTHER"]', 'JSON_LIST', 'Accepted English tests'],
    ['fee_types', '["TUITION_PER_YEAR","TUITION_TOTAL","APPLICATION","REGISTRATION","DEPOSIT","LAB","LIBRARY","OTHER"]', 'JSON_LIST', 'Fee categories'],
    ['confidence_levels', '["UNVERIFIED","LOW","MEDIUM","HIGH"]', 'JSON_LIST', 'Verification confidence scale'],
    ['stale_days', '180', 'NUMBER', 'Days after which a record is considered stale (mirrors config default)'],
];
$ins = $pdo->prepare(
    'INSERT INTO settings (setting_key, setting_value, value_type, description)
     VALUES (?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), value_type = VALUES(value_type), description = VALUES(description)'
);
foreach ($settings as [$k, $v, $t, $d]) {
    $ins->execute([$k, $v, $t, $d]);
}
echo '  settings: ' . count($settings) . " handled\n";

// ---- audit log entry describing the seed run itself (no secrets logged) ------------------
$auditUser = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$auditUser->execute(['admin@example.invalid']);
$adminId = (int) $auditUser->fetchColumn() ?: null;
$ins = $pdo->prepare(
    'INSERT INTO audit_logs (actor_user_id, action, entity_type, entity_id, new_values)
     VALUES (?, ?, ?, NULL, ?)'
);
$ins->execute([
    $adminId, 'SEED', 'database',
    json_encode(['script' => 'database/seed.php', 'note' => 'SAMPLE demo data inserted/refreshed', 'at' => gmdate('c')], JSON_UNESCAPED_SLASHES),
]);
echo "  audit_logs: SEED entry added\n";

echo "\nSeed complete. All seeded records are flagged is_sample=1 / UNVERIFIED.\n";
exit(0);
