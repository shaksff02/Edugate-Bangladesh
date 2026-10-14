<?php
/**
 * UMS EduGate — programme repository (Phase 7: Programs and program detail).
 *
 * Tables: programs (+ FKs to universities/campuses; source metadata columns
 * are populated in Phase 11). Parent-child integrity enforced in code on
 * every write, soft-delete semantics identical to Phases 5–6. Prepared
 * statements only; Audit::log on CREATE/UPDATE/ARCHIVE/RESTORE.
 */

declare(strict_types=1);

require_once BASE_PATH . '/app/Core/Auth.php'; // Auth + Audit classes

final class ProgramRepository
{
    public const CONFIDENCE_LEVELS = ['UNVERIFIED', 'LOW', 'MEDIUM', 'HIGH'];

    public const LEVELS = ['DIPLOMA', 'BACHELOR', 'MASTER', 'PHD', 'FOUNDATION', 'A_LEVEL', 'OTHER'];

    public const MODES = ['ONCAMPUS' => 'On-campus', 'ONLINE' => 'Online', 'HYBRID' => 'Hybrid'];

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

    /** Active campuses of one university (for the campus select box). */
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

    public function universityCountryMap(): array
    {
        $rows = $this->pdo->query(
            'SELECT u.id, u.country_id FROM universities u WHERE u.archived_at IS NULL'
        )->fetchAll(PDO::FETCH_ASSOC);
        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['id']] = (int) $r['country_id'];
        }
        return $map;
    }

    public function activeCountries(): array
    {
        return $this->pdo
            ->query('SELECT id, name FROM countries WHERE archived_at IS NULL ORDER BY name ASC')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ------------------------------------------------------------------ */
    /* Programs                                                            */
    /* ------------------------------------------------------------------ */

    /**
     * Combined filters per PRD: country, university, campus, degree(level),
     * major(field), mode, language + free-text search + status.
     */
    public function programsList(array $filters, int $page): array
    {
        $where  = [];
        $params = [];

        $status = $filters['status'] ?? 'active';
        if ($status === 'active') {
            $where[] = 'p.archived_at IS NULL';
        } elseif ($status === 'archived') {
            $where[] = 'p.archived_at IS NOT NULL';
        }

        if (!empty($filters['q'])) {
            $where[] = '(p.name LIKE :q OR p.field LIKE :q2)';
            $params[':q']  = '%' . $filters['q'] . '%';
            $params[':q2'] = '%' . $filters['q'] . '%';
        }
        if (!empty($filters['country_id'])) {
            $where[] = 'u.country_id = :cid';
            $params[':cid'] = (int) $filters['country_id'];
        }
        if (!empty($filters['university_id'])) {
            $where[] = 'p.university_id = :uid';
            $params[':uid'] = (int) $filters['university_id'];
        }
        if (!empty($filters['campus_id'])) {
            $where[] = 'p.campus_id = :cpid';
            $params[':cpid'] = (int) $filters['campus_id'];
        }
        if (!empty($filters['level']) && in_array($filters['level'], self::LEVELS, true)) {
            $where[] = 'p.level = :lvl';
            $params[':lvl'] = $filters['level'];
        }
        if (!empty($filters['major'])) {
            $where[] = 'p.field LIKE :mj';
            $params[':mj'] = '%' . $filters['major'] . '%';
        }
        if (!empty($filters['mode']) && array_key_exists($filters['mode'], self::MODES)) {
            $where[] = 'p.study_mode = :mode';
            $params[':mode'] = $filters['mode'];
        }
        if (!empty($filters['language'])) {
            $where[] = 'p.language_of_instruction LIKE :lng';
            $params[':lng'] = '%' . $filters['language'] . '%';
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT p.*, u.name AS university_name, cp.name AS campus_name, c.name AS country_name
                FROM programs p
                JOIN universities u ON u.id = p.university_id
                JOIN countries c   ON c.id = u.country_id
                LEFT JOIN campuses cp ON cp.id = p.campus_id
                {$whereSql}";

        return $this->paginate($sql, 'u.name ASC, p.name ASC', $params, $page);
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

    public function findProgram(int $id): ?array
    {
        $st = $this->pdo->prepare(
            'SELECT p.*, u.name AS university_name, u.archived_at AS university_archived_at,
                    cp.name AS campus_name, c.name AS country_name, c.id AS country_id
             FROM programs p
             JOIN universities u ON u.id = p.university_id
             JOIN countries c    ON c.id = u.country_id
             LEFT JOIN campuses cp ON cp.id = p.campus_id
             WHERE p.id = ?'
        );
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function createProgram(array $d): int
    {
        $st = $this->pdo->prepare(
            'INSERT INTO programs
                (university_id, campus_id, name, level, field, duration_months, credits,
                 language_of_instruction, study_mode, description, confidence_level, created_by, updated_by)
             VALUES
                (:university_id, :campus_id, :name, :level, :field, :duration_months, :credits,
                 :language_of_instruction, :study_mode, :description, :confidence_level, :au, :au2)'
        );
        $this->bind($st, $d, [
            'university_id', 'campus_id', 'name', 'level', 'field', 'duration_months',
            'credits', 'language_of_instruction', 'study_mode', 'description', 'confidence_level',
        ]);
        $st->execute();
        $id = (int) $this->pdo->lastInsertId();
        Audit::log(Auth::id(), 'CREATE', 'programs', $id);
        return $id;
    }

    public function updateProgram(int $id, array $d): void
    {
        if ($this->findProgram($id) === null) {
            throw new RuntimeException('Program not found.');
        }
        $st = $this->pdo->prepare(
            'UPDATE programs SET university_id=:university_id, campus_id=:campus_id, name=:name,
             level=:level, field=:field, duration_months=:duration_months, credits=:credits,
             language_of_instruction=:language_of_instruction, study_mode=:study_mode, description=:description,
             confidence_level=:confidence_level, updated_by=:au WHERE id=:id'
        );
        $this->bind($st, $d, [
            'university_id', 'campus_id', 'name', 'level', 'field', 'duration_months',
            'credits', 'language_of_instruction', 'study_mode', 'description', 'confidence_level',
        ]);
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();
        Audit::log(Auth::id(), 'UPDATE', 'programs', $id);
    }

    public function archiveProgram(int $id): array
    {
        $row = $this->findProgram($id);
        if ($row === null) {
            return ['ok' => false, 'message' => 'Program not found.'];
        }
        if ($row['archived_at'] !== null) {
            return ['ok' => false, 'message' => 'This program is already archived.'];
        }
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM academic_requirements WHERE program_id = ? AND archived_at IS NULL');
        $st->execute([$id]);
        if ((int) $st->fetchColumn() > 0) {
            return ['ok' => false, 'message' => 'Archive this program\'s academic requirements first.'];
        }
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM language_requirements WHERE program_id = ? AND archived_at IS NULL');
        $st->execute([$id]);
        if ((int) $st->fetchColumn() > 0) {
            return ['ok' => false, 'message' => 'Archive this program\'s language requirements first.'];
        }
        $this->archive('programs', $id);
        Audit::log(Auth::id(), 'ARCHIVE', 'programs', $id);
        return ['ok' => true, 'message' => 'Program archived.'];
    }

    public function restoreProgram(int $id): array
    {
        $st = $this->pdo->prepare('SELECT archived_at FROM programs WHERE id = ?');
        $st->execute([$id]);
        $v = $st->fetchColumn();
        if ($v === false) {
            return ['ok' => false, 'message' => 'Program not found.'];
        }
        if ($v === null) {
            return ['ok' => false, 'message' => 'This program is not archived.'];
        }
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM universities WHERE id = (SELECT university_id FROM programs WHERE id = ?) AND archived_at IS NULL');
        $st->execute([$id]);
        if ((int) $st->fetchColumn() === 0) {
            return ['ok' => false, 'message' => 'Its university is archived; restore the university first.'];
        }
        $st = $this->pdo->prepare('UPDATE programs SET archived_at = NULL, updated_by = ? WHERE id = ?');
        $st->execute([Auth::id(), $id]);
        Audit::log(Auth::id(), 'RESTORE', 'programs', $id);
        return ['ok' => true, 'message' => 'Program restored.'];
    }

    /** Related-data counts for the detail page tabs/placeholders. */
    public function relatedCounts(int $programId): array
    {
        $out = [];
        foreach (['academic_requirements', 'language_requirements'] as $t) {
            $st = $this->pdo->prepare("SELECT COUNT(*) FROM {$t} WHERE program_id = ? AND archived_at IS NULL");
            $st->execute([$programId]);
            $out[$t] = (int) $st->fetchColumn();
        }
        foreach (['fees', 'scholarships', 'document_requirements'] as $t) {
            $st = $this->pdo->prepare("SELECT COUNT(*) FROM {$t} WHERE program_id = ? AND archived_at IS NULL");
            $st->execute([$programId]);
            $out[$t] = (int) $st->fetchColumn();
        }
        return $out;
    }

    /** University-wide fallback rules shown under a program's own rules. */
    public function universityFallbackRequirements(int $universityId): array
    {
        $st = $this->pdo->prepare(
            "SELECT * FROM academic_requirements
             WHERE university_id = ? AND scope_type = 'UNIVERSITY_ALL' AND program_id IS NULL
               AND archived_at IS NULL
             ORDER BY id ASC"
        );
        $st->execute([$universityId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
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
        if (stripos($st->queryString, ':au2') !== false) {
            $st->bindValue(':au2', Auth::id());
        }
    }

    private function archive(string $table, int $id): void
    {
        $st = $this->pdo->prepare("UPDATE {$table} SET archived_at = NOW(), updated_by = ? WHERE id = ?");
        $st->execute([Auth::id(), $id]);
    }
}
