-- =====================================================================
-- UMS EduGate — Migration 0000: schema version tracking table
-- Created first by database/migrate.php; safe to re-run (IF NOT EXISTS).
-- =====================================================================
CREATE TABLE IF NOT EXISTS schema_migrations (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    filename    VARCHAR(255) NOT NULL COMMENT 'migration file basename, e.g. 0001_core_schema.sql',
    checksum    CHAR(64)     NOT NULL COMMENT 'SHA-256 of file contents at apply time',
    applied_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_migrations_filename (filename)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
