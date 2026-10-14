<?php
/**
 * UMS EduGate — calendar repository (Phase 6: Campuses, Intakes & Application Rounds).
 *
 * Parent-child integrity is enforced in code on every write:
 *   campus.university_id -> active university
 *   intake.university_id -> active university
 *   round.intake_id      -> active intake (university derived from the intake)
 * Soft-delete (archive) semantics match Phase 5. Prepared statements only.
 */

declare(strict_types=1);

require_once BASE_PATH . '/app/Core/Auth.php';     // Auth + Audit classes
require_once BASE_PATH . '/app/Core/Deadline.php';

final class CalendarRepository
{
    public const CONFIDENCE_LEVELS = ['UNVERIFIED', 'LOW', 'MEDIUM', 'HIGH'];

    public function __construct(private PDO $pdo)
    {
    }

    /* ------------------------------------------------------------------ */
    /* Lookup helpers (select boxes / parent validation)                   */
    /* ------------------------------------------------------------------ */

    public function activeUniversities(): array
    {
        return $this->pdo
            ->query('SELECT id, name FROM universities WHERE archived_at IS NULL ORDER BY name ASC')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function activeCampuses(?int $universityId = null): array
    {
        if ($universityId !== null) {
            $st = $this->pdo->prepare(
                'SELECT id, name, city FROM campuses
                 WHERE archived_at IS NULL AND university_id = ? ORDER BY name ASC'
            );
            $st->execute([$universityId]);
            return $st->fetchAll(PDO::FETCH_ASSOC);
        }
        return $this->pdo
            ->query('SELECT id, name, city FROM campuses WHERE archived_at IS NULL ORDER BY name ASC')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function activeIntakes(?int $universityId = null): array
    {
        if ($universityId !== null) {
            $st = $this->pdo->prepare(
                'SELECT id, name, intake_year, university_id FROM intakes
                 WHERE archived_at IS NULL AND university_id = ?
                 ORDER BY intake_year DESC, name ASC'
            );
            $st->execute([$universityId]);
            return $st->fetchAll(PDO::FETCH_ASSOC);
        }
        return $this->pdo
            ->query('SELECT id, name, intake_year, university_id FROM intakes
                    WHERE archived_at IS NULL ORDER BY intake_year DESC, name ASC')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /** University an intake belongs to (null when missing/archived). */
    public function intakeUniversityId(int $intakeId): ?int
    {
        $st = $this->pdo->prepare('SELECT university_id FROM intakes WHERE id = ? AND archived_at IS NULL');
        $st->execute([$intakeId]);
        $v = $st->fetchColumn();
        return $v === false ? null : (int) $v;
    }

    /* ------------------------------------------------------------------ */
    /* Generic list/find/archive helpers                                   */
    /* ------------------------------------------------------------------ */

    private function filterWhere(string $search, string $status, string $searchCols, string $alias): array
    {
        $where  = [];
        $params = [];
        if ($status === 'active') {
            $where[] = $alias . '.archived_at IS NULL';
        } elseif ($status === 'archived') {
            $where[] = $alias . '.archived_at IS NOT NULL';
        }
        if ($search !== '') {
            $where[] = "({$searchCols} LIKE :q)";
            $params[':q'] = '%' . $search . '%';
        }
        return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $params];
    }

    private function paginate(string $sql, string $order, array $params, int $page, int $perPage = 15): array
    {
        $countStmt = $this->pdo->prepare('SELECT COUNT(*) FROM (' . $sql . ') AS sub');
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $page    = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset  = ($page - 1) * $perPage;

        $rows = $this->pdo->prepare($sql . " ORDER BY {$order} LIMIT " . $perPage . ' OFFSET ' . $offset);
        $rows->execute($params);
        return [
            'rows'  => $rows->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
            'page'  => $page,
            'per'   => $perPage,
        ];
    }

    /* ----------------------------- Campuses ---------------------------- */

    public function campusesList(string $search, string $status, ?int $universityId, int $page): array
    {
        [$w, $params] = $this->filterWhere($search, $status, 'cp.name', 'cp');
        $where = $w === '' ? [] : [substr($w, strlen('WHERE '))];
        if ($universityId !== null) {
            $where[] = 'cp.university_id = :uid';
            $params[':uid'] = $universityId;
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT cp.*, u.name AS university_name
                FROM campuses cp
                JOIN universities u ON u.id = cp.university_id
                {$whereSql}";
        return $this->paginate($sql, 'u.name ASC, cp.is_main DESC, cp.name ASC', $params, $page);
    }

    public function findCampus(int $id): ?array
    {
        $st = $this->pdo->prepare(
            'SELECT cp.*, u.name AS university_name FROM campuses cp
             JOIN universities u ON u.id = cp.university_id WHERE cp.id = ?'
        );
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function createCampus(array $d): int
    {
        $st = $this->pdo->prepare(
            'INSERT INTO campuses (university_id, name, city, address, is_main, confidence_level, created_by, updated_by)
             VALUES (:university_id, :name, :city, :address, :is_main, :confidence_level, :au, :au2)'
        );
        $this->bind($st, $d, ['university_id', 'name', 'city', 'address', 'is_main', 'confidence_level']);
        $st->execute();
        $id = (int) $this->pdo->lastInsertId();
        Audit::log(Auth::id(), 'CREATE', 'campuses', $id);
        return $id;
    }

    public function updateCampus(int $id, array $d): void
    {
        if ($this->findCampus($id) === null) {
            throw new RuntimeException('Campus not found.');
        }
        $st = $this->pdo->prepare(
            'UPDATE campuses SET university_id=:university_id, name=:name, city=:city, address=:address,
             is_main=:is_main, confidence_level=:confidence_level, updated_by=:au WHERE id=:id'
        );
        $this->bind($st, $d, ['university_id', 'name', 'city', 'address', 'is_main', 'confidence_level']);
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();
        Audit::log(Auth::id(), 'UPDATE', 'campuses', $id);
    }

    public function archiveCampus(int $id): array
    {
        $row = $this->findCampus($id);
        if ($row === null) {
            return ['ok' => false, 'message' => 'Campus not found.'];
        }
        if ($row['archived_at'] !== null) {
            return ['ok' => false, 'message' => 'This campus is already archived.'];
        }
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM programs WHERE campus_id = ? AND archived_at IS NULL');
        $st->execute([$id]);
        if ((int) $st->fetchColumn() > 0) {
            return ['ok' => false, 'message' => 'Campus is referenced by active programmes; archive those first.'];
        }
        $this->archive('campuses', $id);
        Audit::log(Auth::id(), 'ARCHIVE', 'campuses', $id);
        return ['ok' => true, 'message' => 'Campus archived.'];
    }

    public function restoreCampus(int $id): array
    {
        return $this->restoreGeneric('campuses', $id, 'Campus');
    }

    /* ------------------------------ Intakes ----------------------------- */

    public function intakesList(string $search, string $status, ?int $universityId, ?int $year, int $page): array
    {
        [$w, $params] = $this->filterWhere($search, $status, 'i.name', 'i');
        $where = $w === '' ? [] : [substr($w, strlen('WHERE '))];
        if ($universityId !== null) {
            $where[] = 'i.university_id = :uid';
            $params[':uid'] = $universityId;
        }
        if ($year !== null) {
            $where[] = 'i.intake_year = :yr';
            $params[':yr'] = $year;
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT i.*, u.name AS university_name
                FROM intakes i
                JOIN universities u ON u.id = i.university_id
                {$whereSql}";
        return $this->paginate($sql, 'i.intake_year DESC, u.name ASC, i.name ASC', $params, $page);
    }

    public function findIntake(int $id): ?array
    {
        $st = $this->pdo->prepare(
            'SELECT i.*, u.name AS university_name FROM intakes i
             JOIN universities u ON u.id = i.university_id WHERE i.id = ?'
        );
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function createIntake(array $d): int
    {
        $st = $this->pdo->prepare(
            'INSERT INTO intakes (university_id, name, intake_year, starts_on, ends_on, confidence_level, created_by, updated_by)
             VALUES (:university_id, :name, :intake_year, :starts_on, :ends_on, :confidence_level, :au, :au2)'
        );
        $this->bind($st, $d, ['university_id', 'name', 'intake_year', 'starts_on', 'ends_on', 'confidence_level']);
        $st->execute();
        $id = (int) $this->pdo->lastInsertId();
        Audit::log(Auth::id(), 'CREATE', 'intakes', $id);
        return $id;
    }

    public function updateIntake(int $id, array $d): void
    {
        if ($this->findIntake($id) === null) {
            throw new RuntimeException('Intake not found.');
        }
        $st = $this->pdo->prepare(
            'UPDATE intakes SET university_id=:university_id, name=:name, intake_year=:intake_year,
             starts_on=:starts_on, ends_on=:ends_on, confidence_level=:confidence_level, updated_by=:au WHERE id=:id'
        );
        $this->bind($st, $d, ['university_id', 'name', 'intake_year', 'starts_on', 'ends_on', 'confidence_level']);
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();
        Audit::log(Auth::id(), 'UPDATE', 'intakes', $id);
    }

    public function archiveIntake(int $id): array
    {
        $row = $this->findIntake($id);
        if ($row === null) {
            return ['ok' => false, 'message' => 'Intake not found.'];
        }
        if ($row['archived_at'] !== null) {
            return ['ok' => false, 'message' => 'This intake is already archived.'];
        }
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM application_rounds WHERE intake_id = ? AND archived_at IS NULL');
        $st->execute([$id]);
        if ((int) $st->fetchColumn() > 0) {
            return ['ok' => false, 'message' => 'Archive its application rounds first.'];
        }
        $this->archive('intakes', $id);
        Audit::log(Auth::id(), 'ARCHIVE', 'intakes', $id);
        return ['ok' => true, 'message' => 'Intake archived.'];
    }

    public function restoreIntake(int $id): array
    {
        return $this->restoreGeneric('intakes', $id, 'Intake');
    }

    /* -------------------------- Application rounds ---------------------- */

    public function roundsList(string $search, string $status, ?int $universityId, ?int $intakeId, int $page): array
    {
        [$w, $params] = $this->filterWhere($search, $status, 'r.name', 'r');
        $where = $w === '' ? [] : [substr($w, strlen('WHERE '))];
        if ($universityId !== null) {
            $where[] = 'i.university_id = :uid';
            $params[':uid'] = $universityId;
        }
        if ($intakeId !== null) {
            $where[] = 'r.intake_id = :iid';
            $params[':iid'] = $intakeId;
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT r.*, i.name AS intake_name, i.intake_year, i.university_id, u.name AS university_name
                FROM application_rounds r
                JOIN intakes i ON i.id = r.intake_id
                JOIN universities u ON u.id = i.university_id
                {$whereSql}";
        $result = $this->paginate($sql, 'r.closes_at IS NULL, r.closes_at ASC, r.name ASC', $params, $page);
        foreach ($result['rows'] as &$row) {
            $row['deadline'] = Deadline::classify($row['closes_at'] ?? null);
        }
        unset($row);
        return $result;
    }

    public function findRound(int $id): ?array
    {
        $st = $this->pdo->prepare(
            'SELECT r.*, i.name AS intake_name, i.intake_year, i.university_id, u.name AS university_name
             FROM application_rounds r
             JOIN intakes i ON i.id = r.intake_id
             JOIN universities u ON u.id = i.university_id WHERE r.id = ?'
        );
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $row['deadline'] = Deadline::classify($row['closes_at'] ?? null);
        }
        return $row ?: null;
    }

    public function createRound(array $d): int
    {
        $st = $this->pdo->prepare(
            'INSERT INTO application_rounds (intake_id, name, opens_at, closes_at, result_date, confidence_level, created_by, updated_by)
             VALUES (:intake_id, :name, :opens_at, :closes_at, :result_date, :confidence_level, :au, :au2)'
        );
        $this->bind($st, $d, ['intake_id', 'name', 'opens_at', 'closes_at', 'result_date', 'confidence_level']);
        $st->execute();
        $id = (int) $this->pdo->lastInsertId();
        Audit::log(Auth::id(), 'CREATE', 'application_rounds', $id);
        return $id;
    }

    public function updateRound(int $id, array $d): void
    {
        if ($this->findRound($id) === null) {
            throw new RuntimeException('Application round not found.');
        }
        $st = $this->pdo->prepare(
            'UPDATE application_rounds SET intake_id=:intake_id, name=:name, opens_at=:opens_at,
             closes_at=:closes_at, result_date=:result_date, confidence_level=:confidence_level,
             updated_by=:au WHERE id=:id'
        );
        $this->bind($st, $d, ['intake_id', 'name', 'opens_at', 'closes_at', 'result_date', 'confidence_level']);
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();
        Audit::log(Auth::id(), 'UPDATE', 'application_rounds', $id);
    }

    public function archiveRound(int $id): array
    {
        $row = $this->findRound($id);
        if ($row === null) {
            return ['ok' => false, 'message' => 'Application round not found.'];
        }
        if ($row['archived_at'] !== null) {
            return ['ok' => false, 'message' => 'This round is already archived.'];
        }
        $this->archive('application_rounds', $id);
        Audit::log(Auth::id(), 'ARCHIVE', 'application_rounds', $id);
        return ['ok' => true, 'message' => 'Application round archived.'];
    }

    public function restoreRound(int $id): array
    {
        return $this->restoreGeneric('application_rounds', $id, 'Application round');
    }

    /**
     * Deadline report (PRD acceptance: returns expected records for test dates).
     * Buckets over ACTIVE rounds: upcoming | closing_soon | passed | unknown.
     */
    public function deadlineReport(?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable('now');
        $rows = $this->pdo->query(
            'SELECT r.id, r.name, r.closes_at, i.name AS intake_name, i.intake_year, u.name AS university_name
             FROM application_rounds r
             JOIN intakes i ON i.id = r.intake_id
             JOIN universities u ON u.id = i.university_id
             WHERE r.archived_at IS NULL AND i.archived_at IS NULL AND u.archived_at IS NULL
             ORDER BY r.closes_at IS NULL, r.closes_at ASC, r.name ASC'
        )->fetchAll(PDO::FETCH_ASSOC);

        $report = ['upcoming' => [], 'closing_soon' => [], 'passed' => [], 'unknown' => []];
        foreach ($rows as $row) {
            $d = Deadline::classify($row['closes_at'] ?? null, $now);
            $row['deadline'] = $d;
            $key = match ($d['label']) {
                'Upcoming'     => 'upcoming',
                'Closing soon' => 'closing_soon',
                'Passed'       => 'passed',
                default        => 'unknown',
            };
            $report[$key][] = $row;
        }
        return $report;
    }

    /* ------------------------------------------------------------------ */
    /* Internals                                                           */
    /* ------------------------------------------------------------------ */

    private function bind(PDOStatement $st, array $d, array $keys): void
    {
        foreach ($keys as $k) {
            $v = $d[$k] ?? null;
            $st->bindValue(':' . $k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $st->bindValue(':au', Auth::id());
        // :au2 only appears in INSERT statements (created_by + updated_by).
        if (stripos($st->queryString, ':au2') !== false) {
            $st->bindValue(':au2', Auth::id());
        }
    }

    private function archive(string $table, int $id): void
    {
        $st = $this->pdo->prepare("UPDATE {$table} SET archived_at = NOW(), updated_by = ? WHERE id = ?");
        $st->execute([Auth::id(), $id]);
    }

    private function restoreGeneric(string $table, int $id, string $label): array
    {
        $st = $this->pdo->prepare("SELECT archived_at FROM {$table} WHERE id = ?");
        $st->execute([$id]);
        $v = $st->fetchColumn();
        if ($v === false) {
            return ['ok' => false, 'message' => "{$label} not found."];
        }
        if ($v === null) {
            return ['ok' => false, 'message' => "This {$label} is not archived."];
        }
        $st = $this->pdo->prepare("UPDATE {$table} SET archived_at = NULL, updated_by = ? WHERE id = ?");
        $st->execute([Auth::id(), $id]);
        Audit::log(Auth::id(), 'RESTORE', $table, $id);
        return ['ok' => true, 'message' => "{$label} restored."];
    }
}
