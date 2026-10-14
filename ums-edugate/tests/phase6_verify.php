<?php
/** Phase 6 end-to-end verification against live MySQL (no HTTP layer). */
declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/app/Core/Auth.php';
require_once BASE_PATH . '/app/Core/Session.php';
require_once BASE_PATH . '/app/Repositories/CalendarRepository.php';

$pass = 0; $fail = 0;
function check(string $name, bool $ok) {
    global $pass, $fail;
    if ($ok) { $pass++; echo "PASS  $name\n"; } else { $fail++; echo "FAIL  $name\n"; }
}

$pdo = Database::connection();
$_SESSION = []; // fake session for Audit::log safety
// Auth::id() may need a logged-in user; audit allows null actor. Ensure no fatal:
if (!isset($_SESSION['user_id'])) { $_SESSION['user_id'] = null; }

$repo = new CalendarRepository($pdo);

$uni = $repo->activeUniversities();
check('has at least one active university', count($uni) > 0);
$uid = (int) $uni[0]['id'];

// cleanup from previous runs
$pdo->exec('DELETE FROM application_rounds WHERE name LIKE "P6V%"');
$pdo->exec('DELETE FROM intakes WHERE name LIKE "P6V%"');
$pdo->exec('DELETE FROM campuses WHERE name LIKE "P6V%"');

// Campus CRUD
$cid = $repo->createCampus(['university_id' => $uid, 'name' => 'P6V Campus', 'city' => 'Testville', 'address' => 'x', 'is_main' => 1, 'confidence_level' => 'MEDIUM']);
$c = $repo->findCampus($cid);
check('campus created + found', $c !== null && $c['name'] === 'P6V Campus' && (int) $c['is_main'] === 1);
$repo->updateCampus($cid, ['university_id' => $uid, 'name' => 'P6V Campus2', 'city' => 'Other', 'address' => '', 'is_main' => 0, 'confidence_level' => 'HIGH']);
$c = $repo->findCampus($cid);
check('campus updated', $c['name'] === 'P6V Campus2' && $c['confidence_level'] === 'HIGH');
$r = $repo->archiveCampus($cid);
check('campus archived', $r['ok'] === true && $repo->findCampus($cid)['archived_at'] !== null);
$r = $repo->restoreCampus($cid);
check('campus restored', $r['ok'] === true && $repo->findCampus($cid)['archived_at'] === null);

// Intake CRUD
$iid = $repo->createIntake(['university_id' => $uid, 'name' => 'P6V Fall', 'intake_year' => 2027, 'starts_on' => '2027-08-01', 'ends_on' => '2027-12-31', 'confidence_level' => 'LOW']);
$i = $repo->findIntake($iid);
check('intake created', $i !== null && (int) $i['intake_year'] === 2027);

// Round CRUD + deadline labels
$ridUp = $repo->createRound(['intake_id' => $iid, 'name' => 'P6V Up', 'opens_at' => '2026-10-01 00:00:00', 'closes_at' => date('Y-m-d H:i:s', strtotime('+40 days')), 'result_date' => null, 'confidence_level' => 'UNVERIFIED']);
$ridSoon = $repo->createRound(['intake_id' => $iid, 'name' => 'P6V Soon', 'opens_at' => null, 'closes_at' => date('Y-m-d H:i:s', strtotime('+5 days')), 'result_date' => null, 'confidence_level' => 'UNVERIFIED']);
$ridPassed = $repo->createRound(['intake_id' => $iid, 'name' => 'P6V Passed', 'opens_at' => null, 'closes_at' => date('Y-m-d H:i:s', strtotime('-2 days')), 'result_date' => null, 'confidence_level' => 'UNVERIFIED']);
$ridUnknown = $repo->createRound(['intake_id' => $iid, 'name' => 'P6V Unknown', 'opens_at' => null, 'closes_at' => null, 'result_date' => null, 'confidence_level' => 'UNVERIFIED']);

$rep = $repo->deadlineReport();
$names = array_map(static fn($x) => $x['name'], $rep['upcoming']);
check('report: upcoming bucket', in_array('P6V Up', $names, true));
check('report: closing_soon bucket', in_array('P6V Soon', array_map(static fn($x) => $x['name'], $rep['closing_soon']), true));
check('report: passed bucket', in_array('P6V Passed', array_map(static fn($x) => $x['name'], $rep['passed']), true));
check('report: unknown bucket (null deadline never guessed)', in_array('P6V Unknown', array_map(static fn($x) => $x['name'], $rep['unknown']), true));

// Archive guard: intake has active rounds -> cannot archive
$r = $repo->archiveIntake($iid);
check('intake archive blocked while active rounds exist', $r['ok'] === false);

// Archive all rounds, then intake can be archived
foreach ([$ridUp, $ridSoon, $ridPassed, $ridUnknown] as $rid) { $repo->archiveRound($rid); }
$r = $repo->archiveIntake($iid);
check('intake archivable after rounds archived', $r['ok'] === true);

// Restore chain back and cleanup
$repo->restoreIntake($iid);
$repo->restoreCampus($cid);

// Parent-child integrity: bad FK rejected by findIntake-independent code path is controller-level; verify repo throws on missing campus update
try { $repo->updateCampus(999999, ['university_id' => $uid, 'name' => 'x', 'city' => '', 'address' => '', 'is_main' => 0, 'confidence_level' => 'LOW']); check('update missing campus throws', false); }
catch (RuntimeException $e) { check('update missing campus throws', true); }

// cleanup
$pdo->exec('DELETE FROM application_rounds WHERE name LIKE "P6V%"');
$pdo->exec('DELETE FROM intakes WHERE name LIKE "P6V%"');
$pdo->exec('DELETE FROM campuses WHERE name LIKE "P6V%"');
check('cleanup complete', (int) $pdo->query('SELECT COUNT(*) FROM campuses WHERE name LIKE "P6V%"')->fetchColumn() === 0);

echo "\n$pass passed, $fail failed\n";
exit($fail === 0 ? 0 : 1);
