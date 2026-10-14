<?php
/**
 * UMS EduGate — master-data repository (Phase 5: Countries & Universities).
 *
 * All queries use prepared statements. Soft-delete (archive) semantics:
 * nothing is hard-deleted from the UI; archived rows are hidden by default
 * and can be restored. Duplicate-key (1062) errors map to friendly messages.
 */

declare(strict_types=1);

require_once BASE_PATH . '/app/Core/Auth.php'; // Auth + Audit classes

final class MasterDataRepository
{
    public const UNI_TYPES = ['PUBLIC', 'PRIVATE', 'GOVERNMENT_AIDED', 'INTERNATIONAL_BRANCH', 'OTHER'];
    public const CONFIDENCE_LEVELS = ['UNVERIFIED', 'LOW', 'MEDIUM', 'HIGH'];

    public function __construct(private PDO $pdo)
    {
    }

    /* ------------------------------------------------------------------ */
    /* Shared helpers                                                      */
    /* ------------------------------------------------------------------ */

    /** Build WHERE fragment for search/status filters on a table alias. */
    private function filterSql(string $search, string $status): array
    {
        $where  = [];
        $params = [];
        // status: active (default) | archived | all
        if ($status === 'active') {
            $where[] = 'archived_at IS NULL';
        } elseif ($status === 'archived') {
            $where[] = 'archived_at IS NOT NULL';
        }
        if ($search !== '') {
            $where[] = '(name LIKE :q OR COALESCE(region, city, \'\') LIKE :q)';
            $params[':q'] = '%' . $search . '%';
        }
        return [($where ? 'WHERE ' . implode(' AND ', $where) : ''), $params];
    }

    private function paginate(string $sql, array $params, int $page, int $perPage): array
    {
        $countStmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM (' . $sql . ') AS sub'
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $page    = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset  = ($page - 1) * $perPage;

        $rows = $this->pdo->prepare($sql . ' ORDER BY name ASC LIMIT ' . $perPage . ' OFFSET ' . $offset);
        $rows->execute($params);
        return [
            'rows'  => $rows->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
            'page'  => $page,
            'per'   => $perPage,
        ];
    }

    /** Translate a duplicate-key SQLSTATE into a friendly field error. */
    private function friendlyDuplicate(Throwable $e, string $entity): ?string
    {
        if (($e instanceof PDOException && $e->getCode() === '23000')
            || str_contains($e->getMessage() ?? '', 'Duplicate entry')) {
            return match (true) {
                str_contains($e->getMessage(), 'uq_countries_iso2')
                    => 'That ISO-2 code is already used by another country.',
                str_contains($e->getMessage(), 'uq_countries_name')
                    => 'A country with this name already exists.',
                str_contains($e->getMessage(), 'uq_universities_country_name')
                    => 'A university with this name already exists in the selected country.',
                default => 'This record duplicates an existing one.',
            };
        }
        return null;
    }

    /* ------------------------------------------------------------------ */
    /* Countries                                                           */
    /* ------------------------------------------------------------------ */

    public function countriesList(string $search, string $status, int $page, int $perPage = 15): array
    {
        [$where, $params] = $this->filterSql($search, $status);
        return $this->paginate(
            "SELECT c.*, (SELECT COUNT(*) FROM universities u WHERE u.country_id = c.id) AS university_count
             FROM countries c {$where}",
            $params, $page, $perPage
        );
    }

    public function findCountry(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM countries WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function createCountry(array $data): int
    {
        $sql = 'INSERT INTO countries (name, iso2, iso3, region, currency_code, visa_notes, confidence_level, created_by, updated_by)
                VALUES (:name, :iso2, :iso3, :region, :currency_code, :visa_notes, :confidence_level, :uid, :uid)';
        try {
            $st = $this->pdo->prepare($sql);
            $st->bindValue(':name', $data['name']);
            $st->bindValue(':iso2', $data['iso2']);
            $st->bindValue(':iso3', $data['iso3']);
            $st->bindValue(':region', $data['region']);
            $st->bindValue(':currency_code', $data['currency_code']);
            $st->bindValue(':visa_notes', $data['visa_notes']);
            $st->bindValue(':confidence_level', $data['confidence_level'] ?? 'UNVERIFIED');
            $st->bindValue(':uid', Auth::id());
            $st->execute();
        } catch (PDOException $e) {
            $msg = $this->friendlyDuplicate($e, 'country');
            if ($msg !== null) {
                throw new RuntimeException($msg, 0, $e);
            }
            throw $e;
        }
        $id = (int) $this->pdo->lastInsertId();
        Audit::log(Auth::id(), 'CREATE', 'countries', $id);
        return $id;
    }

    public function updateCountry(int $id, array $data): void
    {
        $before = $this->findCountry($id);
        if ($before === null) {
            throw new RuntimeException('Country not found.');
        }
        $sql = 'UPDATE countries SET name=:name, iso2=:iso2, iso3=:iso3, region=:region,
                currency_code=:currency_code, visa_notes=:visa_notes, confidence_level=:confidence_level,
                updated_by=:uid WHERE id=:id';
        try {
            $st = $this->pdo->prepare($sql);
            $st->bindValue(':name', $data['name']);
            $st->bindValue(':iso2', $data['iso2']);
            $st->bindValue(':iso3', $data['iso3']);
            $st->bindValue(':region', $data['region']);
            $st->bindValue(':currency_code', $data['currency_code']);
            $st->bindValue(':visa_notes', $data['visa_notes']);
            $st->bindValue(':confidence_level', $data['confidence_level'] ?? 'UNVERIFIED');
            $st->bindValue(':uid', Auth::id());
            $st->bindValue(':id', $id, PDO::PARAM_INT);
            $st->execute();
        } catch (PDOException $e) {
            $msg = $this->friendlyDuplicate($e, 'country');
            if ($msg !== null) {
                throw new RuntimeException($msg, 0, $e);
            }
            throw $e;
        }
        Audit::log(Auth::id(), 'UPDATE', 'countries', $id);
    }

    /** Soft delete. Refuses when active universities still reference it. */
    public function archiveCountry(int $id): array
    {
        $country = $this->findCountry($id);
        if ($country === null) {
            return ['ok' => false, 'message' => 'Country not found.'];
        }
        if ($country['archived_at'] !== null) {
            return ['ok' => false, 'message' => 'This country is already archived.'];
        }
        $st = $this->pdo->prepare(
            'SELECT COUNT(*) FROM universities WHERE country_id = ? AND archived_at IS NULL'
        );
        $st->execute([$id]);
        $active = (int) $st->fetchColumn();
        if ($active > 0) {
            return ['ok' => false, 'message' => "Cannot archive: {$active} active university record(s) still belong to this country."];
        }
        $up = $this->pdo->prepare('UPDATE countries SET archived_at = NOW(), updated_by = ? WHERE id = ?');
        $up->execute([Auth::id(), $id]);
        Audit::log(Auth::id(), 'ARCHIVE', 'countries', $id);
        return ['ok' => true, 'message' => 'Country archived.'];
    }

    public function restoreCountry(int $id): array
    {
        $country = $this->findCountry($id);
        if ($country === null) {
            return ['ok' => false, 'message' => 'Country not found.'];
        }
        if ($country['archived_at'] === null) {
            return ['ok' => false, 'message' => 'This country is not archived.'];
        }
        $up = $this->pdo->prepare('UPDATE countries SET archived_at = NULL, updated_by = ? WHERE id = ?');
        $up->execute([Auth::id(), $id]);
        Audit::log(Auth::id(), 'RESTORE', 'countries', $id);
        return ['ok' => true, 'message' => 'Country restored.'];
    }

    /** Active countries for select boxes. */
    public function activeCountries(): array
    {
        return $this->pdo
            ->query('SELECT id, name FROM countries WHERE archived_at IS NULL ORDER BY name ASC')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ------------------------------------------------------------------ */
    /* Universities                                                        */
    /* ------------------------------------------------------------------ */

    public function universitiesList(string $search, string $status, ?int $countryId, string $type, int $page, int $perPage = 15): array
    {
        [$whereBase, $params] = $this->filterSql($search, $status);
        $where = $whereBase === '' ? [] : [substr($whereBase, strlen('WHERE '))];
        if ($countryId !== null) {
            $where[] = 'u.country_id = :cid';
            $params[':cid'] = $countryId;
        }
        if ($type !== '' && in_array(strtoupper($type), self::UNI_TYPES, true)) {
            $where[] = 'u.type = :utype';
            $params[':utype'] = strtoupper($type);
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT u.*, c.name AS country_name
                FROM universities u
                JOIN countries c ON c.id = u.country_id
                {$whereSql}";
        // qualify columns used by filterSql (name/region/city exist on both tables)
        $sql = str_replace('(name LIKE :q OR COALESCE(region, city,', '(u.name LIKE :q OR COALESCE(c.region, u.city,', $sql);
        $sql = str_replace('WHERE archived_at', 'WHERE u.archived_at', $sql);
        $sql = str_replace('AND archived_at', 'AND u.archived_at', $sql);
        return $this->paginate($sql, $params, $page, $perPage);
    }

    public function findUniversity(int $id): ?array
    {
        $st = $this->pdo->prepare(
            'SELECT u.*, c.name AS country_name FROM universities u
             JOIN countries c ON c.id = u.country_id WHERE u.id = ?'
        );
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function createUniversity(array $data): int
    {
        $sql = 'INSERT INTO universities (country_id, name, website, city, type, ranking_note, description,
                confidence_level, created_by, updated_by)
                VALUES (:country_id, :name, :website, :city, :type, :ranking_note, :description,
                :confidence_level, :uid, :uid)';
        try {
            $st = $this->pdo->prepare($sql);
            foreach (['country_id', 'name', 'website', 'city', 'type', 'ranking_note', 'description', 'confidence_level'] as $k) {
                $st->bindValue(':' . $k, $data[$k] ?? null);
            }
            $st->bindValue(':uid', Auth::id());
            $st->execute();
        } catch (PDOException $e) {
            $msg = $this->friendlyDuplicate($e, 'university');
            if ($msg !== null) {
                throw new RuntimeException($msg, 0, $e);
            }
            throw $e;
        }
        $id = (int) $this->pdo->lastInsertId();
        Audit::log(Auth::id(), 'CREATE', 'universities', $id);
        return $id;
    }

    public function updateUniversity(int $id, array $data): void
    {
        if ($this->findUniversity($id) === null) {
            throw new RuntimeException('University not found.');
        }
        $sql = 'UPDATE universities SET country_id=:country_id, name=:name, website=:website, city=:city,
                type=:type, ranking_note=:ranking_note, description=:description,
                confidence_level=:confidence_level, updated_by=:uid WHERE id=:id';
        try {
            $st = $this->pdo->prepare($sql);
            foreach (['country_id', 'name', 'website', 'city', 'type', 'ranking_note', 'description', 'confidence_level'] as $k) {
                $st->bindValue(':' . $k, $data[$k] ?? null);
            }
            $st->bindValue(':uid', Auth::id());
            $st->bindValue(':id', $id, PDO::PARAM_INT);
            $st->execute();
        } catch (PDOException $e) {
            $msg = $this->friendlyDuplicate($e, 'university');
            if ($msg !== null) {
                throw new RuntimeException($msg, 0, $e);
            }
            throw $e;
        }
        Audit::log(Auth::id(), 'UPDATE', 'universities', $id);
    }

    public function archiveUniversity(int $id): array
    {
        $uni = $this->findUniversity($id);
        if ($uni === null) {
            return ['ok' => false, 'message' => 'University not found.'];
        }
        if ($uni['archived_at'] !== null) {
            return ['ok' => false, 'message' => 'This university is already archived.'];
        }
        $st = $this->pdo->prepare('UPDATE universities SET archived_at = NOW(), updated_by = ? WHERE id = ?');
        $st->execute([Auth::id(), $id]);
        Audit::log(Auth::id(), 'ARCHIVE', 'universities', $id);
        return ['ok' => true, 'message' => 'University archived.'];
    }

    public function restoreUniversity(int $id): array
    {
        $uni = $this->findUniversity($id);
        if ($uni === null) {
            return ['ok' => false, 'message' => 'University not found.'];
        }
        if ($uni['archived_at'] === null) {
            return ['ok' => false, 'message' => 'This university is not archived.'];
        }
        $st = $this->pdo->prepare('UPDATE universities SET archived_at = NULL, updated_by = ? WHERE id = ?');
        $st->execute([Auth::id(), $id]);
        Audit::log(Auth::id(), 'RESTORE', 'universities', $id);
        return ['ok' => true, 'message' => 'University restored.'];
    }

    /** Count of universities per country id (for list badges). */
    public function universityCountForCountry(int $countryId): int
    {
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM universities WHERE country_id = ? AND archived_at IS NULL');
        $st->execute([$countryId]);
        return (int) $st->fetchColumn();
    }
}
