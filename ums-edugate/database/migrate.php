<?php
/**
 * UMS EduGate — migration runner (Phase 2)
 *
 * Usage:
 *   php database/migrate.php            Apply pending migrations in order.
 *   php database/migrate.php --status   Show applied/pending migrations.
 *
 * Behaviour:
 *   - Ensures the `schema_migrations` tracking table exists first.
 *   - Applies each *.sql file in database/migrations/ exactly once,
 *     in lexicographic filename order (0000_, 0001_, ...).
 *   - Records a SHA-256 checksum; if an applied file later changes on
 *     disk the runner FAILS loudly instead of silently diverging.
 *   - Idempotent: re-running with nothing new is a no-op that exits 0.
 *
 * No credentials here — connection comes from config/config.local.php
 * (see config/config.example.php).
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

const MIGRATIONS_DIR = __DIR__ . '/migrations';

function fail(string $msg): never
{
    fwrite(STDERR, "MIGRATION FAILED: {$msg}\n");
    exit(1);
}

$pdo = Database::connection();

// 1. Ensure tracking table (its own bootstrap file, always safe to re-run).
$bootstrap = file_get_contents(MIGRATIONS_DIR . '/0000_migrations_table.sql');
if ($bootstrap === false) {
    fail('cannot read 0000_migrations_table.sql');
}
$pdo->exec($bootstrap);

// 2. Discover migration files (skip the bootstrap, already applied above).
$files = glob(MIGRATIONS_DIR . '/*.sql') ?: [];
sort($files, SORT_STRING);
$files = array_values(array_filter(
    $files,
    static fn(string $f): bool => basename($f) !== '0000_migrations_table.sql'
));

// 3. Load applied state.
$applied = [];
foreach ($pdo->query('SELECT filename, checksum FROM schema_migrations')->fetchAll() as $row) {
    $applied[$row['filename']] = $row['checksum'];
}

$statusMode = in_array('--status', $argv ?? [], true);
$pending = 0;

foreach ($files as $file) {
    $name = basename($file);
    $checksum = hash('sha256', (string) file_get_contents($file));

    if (isset($applied[$name])) {
        if ($applied[$name] !== $checksum) {
            fail("applied migration '{$name}' has changed on disk (checksum mismatch). "
               . "Add a NEW migration instead of editing history.");
        }
        echo "  applied   {$name}\n";
        continue;
    }

    if ($statusMode) {
        echo "  PENDING   {$name}\n";
        $pending++;
        continue;
    }

    echo "  applying  {$name} ... ";
    $sql = (string) file_get_contents($file);
    // Single-file migrations may contain multiple statements; PDO::exec handles
    // them when emulate-prepares is off and the driver allows multi-statement.
    // Safer: split naively on ";\n" boundaries outside comments is overkill for
    // DDL-only files, so we execute statement-by-statement.
    foreach (split_sql_statements($sql) as $statement) {
        try {
            $pdo->exec($statement);
        } catch (PDOException $e) {
            echo "\n";
            fail("in {$name}: {$e->getMessage()}");
        }
    }
    $ins = $pdo->prepare('INSERT INTO schema_migrations (filename, checksum) VALUES (?, ?)');
    $ins->execute([$name, $checksum]);
    echo "done\n";
    $pending++;
}

if ($statusMode) {
    echo $pending === 0 ? "Database is up to date.\n" : "{$pending} migration(s) pending.\n";
    exit(0);
}

echo $pending === 0
    ? "Nothing to apply — database schema is up to date.\n"
    : "Applied {$pending} migration(s) successfully.\n";
exit(0);

/**
 * Split a DDL SQL file into individual statements.
 * Handles: line comments (-- ...), block comments, single/double quotes.
 * Written for this project's controlled migration files (DDL only).
 *
 * @return string[]
 */
function split_sql_statements(string $sql): array
{
    $statements = [];
    $current = '';
    $len = strlen($sql);
    $inSingle = $inDouble = $inBlock = false;
    $line = '';

    for ($i = 0; $i < $len; $i++) {
        $ch = $sql[$i];
        $next = $i + 1 < $len ? $sql[$i + 1] : '';

        if (!$inSingle && !$inDouble && !$inBlock) {
            if ($ch === '-' && $next === '-') { // line comment
                $j = strpos($sql, "\n", $i);
                $i = $j === false ? $len : $j;
                continue;
            }
            if ($ch === '/' && $next === '*') { $inBlock = true; $i++; continue; }
            if ($ch === "'") { $inSingle = true; }
            elseif ($ch === '"') { $inDouble = true; }
            elseif ($ch === ';') {
                $trimmed = trim($current);
                if ($trimmed !== '') { $statements[] = $trimmed; }
                $current = '';
                continue;
            }
        } elseif ($inBlock && $ch === '*' && $next === '/') { $inBlock = false; $i++; continue; }
        elseif ($inSingle && $ch === "'" && $next !== "\\") { $inSingle = false; }
        elseif ($inDouble && $ch === '"' && $next !== "\\") { $inDouble = false; }

        $current .= $ch;
    }

    $trimmed = trim($current);
    if ($trimmed !== '') {
        $statements[] = $trimmed;
    }
    return $statements;
}
