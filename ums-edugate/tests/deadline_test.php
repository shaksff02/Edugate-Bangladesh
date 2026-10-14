<?php
/** Phase 6 unit test: Deadline::classify boundaries (no DB needed). */
declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/app/Core/Deadline.php';

$pass = 0; $fail = 0;
function check(string $name, bool $ok) {
    global $pass, $fail;
    if ($ok) { $pass++; echo "PASS  $name\n"; }
    else     { $fail++; echo "FAIL  $name\n"; }
}

$now = new DateTimeImmutable('2026-10-14 12:00:00');

$d = Deadline::classify(null, $now);
check('null deadline -> Unknown', $d['label'] === 'Unknown' && $d['days'] === null);

$d = Deadline::classify('', $now);
check('empty deadline -> Unknown', $d['label'] === 'Unknown');

$d = Deadline::classify('not-a-date', $now);
check('malformed deadline -> Unknown', $d['label'] === 'Unknown');

$d = Deadline::classify('2026-10-13 12:00:00', $now);
check('past -> Passed', $d['label'] === 'Passed' && $d['days'] === -1);

$d = Deadline::classify('2026-10-28 12:00:00', $now); // exactly 14 days
check('exactly 14 days -> Closing soon', $d['label'] === 'Closing soon' && $d['days'] === 14);

$d = Deadline::classify('2026-10-29 12:00:00', $now); // 15 days
check('15 days -> Upcoming', $d['label'] === 'Upcoming' && $d['days'] === 15);

$d = Deadline::classify('2026-10-14 12:01:00', $now); // 1 minute ahead
check('1 minute ahead -> Closing soon', $d['label'] === 'Closing soon');

$d = Deadline::classify('2026-10-20 09:30', $now); // Y-m-d H:i format accepted
check('short datetime format parsed', $d['label'] === 'Closing soon' && $d['days'] === 5);

echo "\n$pass passed, $fail failed\n";
exit($fail === 0 ? 0 : 1);
