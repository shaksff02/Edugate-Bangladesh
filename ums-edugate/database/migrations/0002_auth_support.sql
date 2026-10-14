-- =====================================================================
-- UMS EduGate — Migration 0002: Authentication support (Phase 3)
-- ---------------------------------------------------------------------
-- Adds the pieces the auth layer needs that the core schema didn't have:
--   * login_attempts : failed-login tracking for throttling/lockout
--   * remember_tokens: secure "keep me signed in" (hashed selectors,
--                      single-use tokens, PRD rule: no plaintext)
--   * users.failed_attempts / users.locked_until : per-account lockout
-- All statements are idempotent (IF NOT EXISTS / guarded ALTER via
-- repeated-safe column checks is not available in MySQL DDL, so the
-- runner executes this file once and tracks it in schema_migrations).
-- =====================================================================

CREATE TABLE IF NOT EXISTS login_attempts (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email_attempted VARCHAR(190)  NOT NULL,
    ip_address    VARCHAR(45)     NULL,
    user_agent    VARCHAR(255)    NULL,
    success       TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '1 = login OK, 0 = failed',
    created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_login_email_time (email_attempted, created_at),
    KEY idx_login_ip_time (ip_address, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS remember_tokens (
    id             INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    user_id        INT UNSIGNED   NOT NULL,
    selector       CHAR(16)       NOT NULL COMMENT 'random id used to look up the row',
    validator_hash CHAR(64)       NOT NULL COMMENT 'SHA-256 of the secret half; raw token never stored',
    expires_at     DATETIME       NOT NULL,
    created_at     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_used_at   DATETIME       NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_remember_selector (selector),
    KEY idx_remember_user (user_id),
    CONSTRAINT fk_remember_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE users
    ADD COLUMN failed_attempts INT UNSIGNED NOT NULL DEFAULT 0 AFTER is_active,
    ADD COLUMN locked_until    DATETIME     NULL              AFTER failed_attempts;
