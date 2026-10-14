-- =====================================================================
-- UMS EduGate — Migration 0001: Core schema (Phase 2)
-- Engine: InnoDB, charset utf8mb4 / utf8mb4_unicode_ci everywhere.
-- Design notes (see database/ER_DIAGRAM.md and DECISIONS.md):
--   * Internal integer PKs only; readable names are never keys.
--   * Scope pattern for program-linked data: nullable program_id +
--     scope_type ENUM('PROGRAM','UNIVERSITY_ALL') with a CHECK
--     constraint enforcing consistency (PRD "all-program scope").
--   * Business records use archive/soft-delete (archived_at); FKs that
--     point at business records are RESTRICT to prevent accidental loss.
--   * Verification metadata (source_id, last_verified_at, verified_by,
--     confidence_level) on every fact table per PRD §6.
--   * Timezone assumption: all DATETIME values stored in UTC (config app.timezone).
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- users — staff accounts (login itself is wired in Phase 4)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name            VARCHAR(120)    NOT NULL,
    email           VARCHAR(190)    NOT NULL,
    password_hash   VARCHAR(255)    NOT NULL COMMENT 'argon2id/bcrypt via PHP password_hash(); never plaintext',
    role            ENUM('ADMIN','EDITOR','READ_ONLY') NOT NULL DEFAULT 'READ_ONLY',
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    last_login_at   DATETIME        NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by      INT UNSIGNED    NULL,
    updated_by      INT UNSIGNED    NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- countries
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS countries (
    id               INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    name             VARCHAR(120)   NOT NULL,
    iso2             CHAR(2)        NULL COMMENT 'ISO 3166-1 alpha-2, uppercase',
    iso3             CHAR(3)        NULL COMMENT 'ISO 3166-1 alpha-3, uppercase',
    region           VARCHAR(80)    NULL,
    currency_code    CHAR(3)        NULL,
    visa_notes       TEXT           NULL,
    source_id        INT UNSIGNED   NULL,
    last_verified_at DATETIME       NULL,
    verified_by      INT UNSIGNED   NULL,
    confidence_level ENUM('UNVERIFIED','LOW','MEDIUM','HIGH') NOT NULL DEFAULT 'UNVERIFIED',
    is_sample        TINYINT(1)     NOT NULL DEFAULT 0 COMMENT '1 = demo seed data, never present as verified',
    archived_at      DATETIME       NULL,
    created_at       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by       INT UNSIGNED   NULL,
    updated_by       INT UNSIGNED   NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_countries_name (name),
    UNIQUE KEY uq_countries_iso2 (iso2),
    KEY idx_countries_region (region),
    KEY idx_countries_archived (archived_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- sources — where a piece of data came from (URL, official PDF, phone…)
-- Referenced by every fact table; deleted only with intent (RESTRICT).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sources (
    id              INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    name            VARCHAR(190)   NOT NULL,
    url             VARCHAR(500)   NULL,
    source_type     ENUM('WEBSITE','OFFICIAL_PDF','PHONE','EMAIL','PARTNER','MANUAL','OTHER') NOT NULL DEFAULT 'WEBSITE',
    reliability     ENUM('UNRATED','LOW','MEDIUM','HIGH') NOT NULL DEFAULT 'UNRATED',
    notes           TEXT           NULL,
    is_sample       TINYINT(1)     NOT NULL DEFAULT 0,
    archived_at     DATETIME       NULL,
    created_at      DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by      INT UNSIGNED   NULL,
    updated_by      INT UNSIGNED   NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_sources_name_url (name, url),
    KEY idx_sources_type (source_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Late FKs on users (created after users exists) keep this file's
-- table order readable while remaining valid on fresh runs.
ALTER TABLE countries
    ADD CONSTRAINT fk_countries_source     FOREIGN KEY (source_id)   REFERENCES sources (id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_countries_verifier   FOREIGN KEY (verified_by) REFERENCES users (id)   ON DELETE SET NULL,
    ADD CONSTRAINT fk_countries_created_by FOREIGN KEY (created_by)  REFERENCES users (id)   ON DELETE SET NULL,
    ADD CONSTRAINT fk_countries_updated_by FOREIGN KEY (updated_by)  REFERENCES users (id)   ON DELETE SET NULL;

-- ---------------------------------------------------------------------
-- universities
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS universities (
    id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    country_id       INT UNSIGNED  NOT NULL,
    name             VARCHAR(190)  NOT NULL,
    website          VARCHAR(500)  NULL,
    city             VARCHAR(120)  NULL,
    type             ENUM('PUBLIC','PRIVATE','GOVERNMENT_AIDED','INTERNATIONAL_BRANCH','OTHER') NOT NULL DEFAULT 'OTHER',
    ranking_note     VARCHAR(255)  NULL,
    description      TEXT          NULL,
    source_id        INT UNSIGNED  NULL,
    last_verified_at DATETIME      NULL,
    verified_by      INT UNSIGNED  NULL,
    confidence_level ENUM('UNVERIFIED','LOW','MEDIUM','HIGH') NOT NULL DEFAULT 'UNVERIFIED',
    is_sample        TINYINT(1)    NOT NULL DEFAULT 0,
    archived_at      DATETIME      NULL,
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by       INT UNSIGNED  NULL,
    updated_by       INT UNSIGNED  NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_universities_country_name (country_id, name) COMMENT 'duplicate policy: same name within one country is rejected',
    KEY idx_universities_name (name),
    KEY idx_universities_type (type),
    KEY idx_universities_archived (archived_at),
    CONSTRAINT fk_universities_country    FOREIGN KEY (country_id)  REFERENCES countries (id) ON DELETE RESTRICT,
    CONSTRAINT fk_universities_source     FOREIGN KEY (source_id)   REFERENCES sources (id)   ON DELETE SET NULL,
    CONSTRAINT fk_universities_verifier   FOREIGN KEY (verified_by) REFERENCES users (id)     ON DELETE SET NULL,
    CONSTRAINT fk_universities_created_by FOREIGN KEY (created_by)  REFERENCES users (id)     ON DELETE SET NULL,
    CONSTRAINT fk_universities_updated_by FOREIGN KEY (updated_by)  REFERENCES users (id)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- campuses (belongs to university)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS campuses (
    id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    university_id    INT UNSIGNED  NOT NULL,
    name             VARCHAR(190)  NOT NULL,
    city             VARCHAR(120)  NULL,
    address          TEXT          NULL,
    is_main          TINYINT(1)    NOT NULL DEFAULT 0,
    source_id        INT UNSIGNED  NULL,
    last_verified_at DATETIME      NULL,
    verified_by      INT UNSIGNED  NULL,
    confidence_level ENUM('UNVERIFIED','LOW','MEDIUM','HIGH') NOT NULL DEFAULT 'UNVERIFIED',
    is_sample        TINYINT(1)    NOT NULL DEFAULT 0,
    archived_at      DATETIME      NULL,
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by       INT UNSIGNED  NULL,
    updated_by       INT UNSIGNED  NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_campuses_university_name (university_id, name),
    KEY idx_campuses_city (city),
    CONSTRAINT fk_campuses_university    FOREIGN KEY (university_id) REFERENCES universities (id) ON DELETE RESTRICT,
    CONSTRAINT fk_campuses_source        FOREIGN KEY (source_id)     REFERENCES sources (id)      ON DELETE SET NULL,
    CONSTRAINT fk_campuses_verifier      FOREIGN KEY (verified_by)   REFERENCES users (id)        ON DELETE SET NULL,
    CONSTRAINT fk_campuses_created_by    FOREIGN KEY (created_by)    REFERENCES users (id)        ON DELETE SET NULL,
    CONSTRAINT fk_campuses_updated_by    FOREIGN KEY (updated_by)    REFERENCES users (id)        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- intakes (seasonal admission intakes per university)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS intakes (
    id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    university_id    INT UNSIGNED  NOT NULL,
    name             VARCHAR(120)  NOT NULL COMMENT 'e.g. "Fall 2026"',
    intake_year      SMALLINT UNSIGNED NOT NULL,
    starts_on        DATE          NULL,
    ends_on          DATE          NULL,
    source_id        INT UNSIGNED  NULL,
    last_verified_at DATETIME      NULL,
    verified_by      INT UNSIGNED  NULL,
    confidence_level ENUM('UNVERIFIED','LOW','MEDIUM','HIGH') NOT NULL DEFAULT 'UNVERIFIED',
    is_sample        TINYINT(1)    NOT NULL DEFAULT 0,
    archived_at      DATETIME      NULL,
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by       INT UNSIGNED  NULL,
    updated_by       INT UNSIGNED  NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_intakes_university_name (university_id, name),
    KEY idx_intakes_year (intake_year),
    CONSTRAINT fk_intakes_university FOREIGN KEY (university_id) REFERENCES universities (id) ON DELETE RESTRICT,
    CONSTRAINT fk_intakes_source     FOREIGN KEY (source_id)     REFERENCES sources (id)      ON DELETE SET NULL,
    CONSTRAINT fk_intakes_verifier   FOREIGN KEY (verified_by)   REFERENCES users (id)        ON DELETE SET NULL,
    CONSTRAINT fk_intakes_created_by FOREIGN KEY (created_by)    REFERENCES users (id)        ON DELETE SET NULL,
    CONSTRAINT fk_intakes_updated_by FOREIGN KEY (updated_by)    REFERENCES users (id)        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- application_rounds (rounds inside an intake)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS application_rounds (
    id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    intake_id        INT UNSIGNED  NOT NULL,
    name             VARCHAR(120)  NOT NULL COMMENT 'e.g. "Round 1"',
    opens_at         DATETIME      NULL,
    closes_at        DATETIME      NULL,
    result_date      DATE          NULL,
    source_id        INT UNSIGNED  NULL,
    last_verified_at DATETIME      NULL,
    verified_by      INT UNSIGNED  NULL,
    confidence_level ENUM('UNVERIFIED','LOW','MEDIUM','HIGH') NOT NULL DEFAULT 'UNVERIFIED',
    is_sample        TINYINT(1)    NOT NULL DEFAULT 0,
    archived_at      DATETIME      NULL,
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by       INT UNSIGNED  NULL,
    updated_by       INT UNSIGNED  NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_rounds_intake_name (intake_id, name),
    KEY idx_rounds_closes (closes_at),
    CONSTRAINT fk_rounds_intake      FOREIGN KEY (intake_id) REFERENCES intakes (id) ON DELETE RESTRICT,
    CONSTRAINT fk_rounds_source      FOREIGN KEY (source_id) REFERENCES sources (id) ON DELETE SET NULL,
    CONSTRAINT fk_rounds_verifier    FOREIGN KEY (verified_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_rounds_created_by  FOREIGN KEY (created_by)  REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_rounds_updated_by  FOREIGN KEY (updated_by)  REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- programs
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS programs (
    id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    university_id    INT UNSIGNED  NOT NULL,
    campus_id        INT UNSIGNED  NULL,
    name             VARCHAR(190)  NOT NULL,
    level            ENUM('DIPLOMA','BACHELOR','MASTER','PHD','FOUNDATION','A_LEVEL','OTHER') NOT NULL DEFAULT 'OTHER',
    field            VARCHAR(120)  NULL COMMENT 'e.g. Business, Engineering',
    duration_months  SMALLINT UNSIGNED NULL,
    credits          SMALLINT UNSIGNED NULL,
    language_of_instruction VARCHAR(60) NULL,
    description      TEXT          NULL,
    source_id        INT UNSIGNED  NULL,
    last_verified_at DATETIME      NULL,
    verified_by      INT UNSIGNED  NULL,
    confidence_level ENUM('UNVERIFIED','LOW','MEDIUM','HIGH') NOT NULL DEFAULT 'UNVERIFIED',
    is_sample        TINYINT(1)    NOT NULL DEFAULT 0,
    archived_at      DATETIME      NULL,
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by       INT UNSIGNED  NULL,
    updated_by       INT UNSIGNED  NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_programs_university_name_level (university_id, name, level),
    KEY idx_programs_level (level),
    KEY idx_programs_field (field),
    KEY idx_programs_archived (archived_at),
    CONSTRAINT fk_programs_university FOREIGN KEY (university_id) REFERENCES universities (id) ON DELETE RESTRICT,
    CONSTRAINT fk_programs_campus     FOREIGN KEY (campus_id)     REFERENCES campuses (id)     ON DELETE SET NULL,
    CONSTRAINT fk_programs_source     FOREIGN KEY (source_id)     REFERENCES sources (id)      ON DELETE SET NULL,
    CONSTRAINT fk_programs_verifier   FOREIGN KEY (verified_by)   REFERENCES users (id)        ON DELETE SET NULL,
    CONSTRAINT fk_programs_created_by FOREIGN KEY (created_by)    REFERENCES users (id)        ON DELETE SET NULL,
    CONSTRAINT fk_programs_updated_by FOREIGN KEY (updated_by)    REFERENCES users (id)        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Shared scope columns used by the five program-scoped fact tables:
--   scope_type = 'PROGRAM'          -> program_id MUST be set
--   scope_type = 'UNIVERSITY_ALL'   -> program_id MUST be NULL (applies
--                                      to every program of the university)
-- The literal string 'ALL' is never stored in a numeric FK column.
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS academic_requirements (
    id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    university_id    INT UNSIGNED  NOT NULL,
    program_id       INT UNSIGNED  NULL,
    scope_type       ENUM('PROGRAM','UNIVERSITY_ALL') NOT NULL DEFAULT 'PROGRAM',
    requirement_text TEXT          NOT NULL,
    min_gpa          DECIMAL(4,2)  NULL,
    min_percentage   DECIMAL(5,2)  NULL,
    required_subjects VARCHAR(500) NULL,
    source_id        INT UNSIGNED  NULL,
    last_verified_at DATETIME      NULL,
    verified_by      INT UNSIGNED  NULL,
    confidence_level ENUM('UNVERIFIED','LOW','MEDIUM','HIGH') NOT NULL DEFAULT 'UNVERIFIED',
    is_sample        TINYINT(1)    NOT NULL DEFAULT 0,
    archived_at      DATETIME      NULL,
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by       INT UNSIGNED  NULL,
    updated_by       INT UNSIGNED  NULL,
    PRIMARY KEY (id),
    KEY idx_areq_program (program_id),
    KEY idx_areq_university_scope (university_id, scope_type),
    CONSTRAINT chk_areq_scope CHECK (
        (scope_type = 'PROGRAM' AND program_id IS NOT NULL) OR
        (scope_type = 'UNIVERSITY_ALL' AND program_id IS NULL)),
    CONSTRAINT fk_areq_university FOREIGN KEY (university_id) REFERENCES universities (id) ON DELETE RESTRICT,
    CONSTRAINT fk_areq_program    FOREIGN KEY (program_id)    REFERENCES programs (id)     ON DELETE RESTRICT,
    CONSTRAINT fk_areq_source     FOREIGN KEY (source_id)     REFERENCES sources (id)      ON DELETE SET NULL,
    CONSTRAINT fk_areq_verifier   FOREIGN KEY (verified_by)   REFERENCES users (id)        ON DELETE SET NULL,
    CONSTRAINT fk_areq_created_by FOREIGN KEY (created_by)    REFERENCES users (id)        ON DELETE SET NULL,
    CONSTRAINT fk_areq_updated_by FOREIGN KEY (updated_by)    REFERENCES users (id)        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS language_requirements (
    id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    university_id    INT UNSIGNED  NOT NULL,
    program_id       INT UNSIGNED  NULL,
    scope_type       ENUM('PROGRAM','UNIVERSITY_ALL') NOT NULL DEFAULT 'PROGRAM',
    test_name        ENUM('IELTS','TOEFL_IBT','TOEFL_PBT','IELTS_ONLINE','PTE','DUOLINGO','OET','OTHER') NOT NULL,
    min_overall      DECIMAL(4,2)  NULL,
    min_each_band    DECIMAL(4,2)  NULL,
    validity_months  SMALLINT UNSIGNED NULL,
    waiver_notes     TEXT          NULL,
    source_id        INT UNSIGNED  NULL,
    last_verified_at DATETIME      NULL,
    verified_by      INT UNSIGNED  NULL,
    confidence_level ENUM('UNVERIFIED','LOW','MEDIUM','HIGH') NOT NULL DEFAULT 'UNVERIFIED',
    is_sample        TINYINT(1)    NOT NULL DEFAULT 0,
    archived_at      DATETIME      NULL,
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by       INT UNSIGNED  NULL,
    updated_by       INT UNSIGNED  NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_lreq_scope_test (university_id, program_id, test_name),
    KEY idx_lreq_program (program_id),
    CONSTRAINT chk_lreq_scope CHECK (
        (scope_type = 'PROGRAM' AND program_id IS NOT NULL) OR
        (scope_type = 'UNIVERSITY_ALL' AND program_id IS NULL)),
    CONSTRAINT fk_lreq_university FOREIGN KEY (university_id) REFERENCES universities (id) ON DELETE RESTRICT,
    CONSTRAINT fk_lreq_program    FOREIGN KEY (program_id)    REFERENCES programs (id)     ON DELETE RESTRICT,
    CONSTRAINT fk_lreq_source     FOREIGN KEY (source_id)     REFERENCES sources (id)      ON DELETE SET NULL,
    CONSTRAINT fk_lreq_verifier   FOREIGN KEY (verified_by)   REFERENCES users (id)        ON DELETE SET NULL,
    CONSTRAINT fk_lreq_created_by FOREIGN KEY (created_by)    REFERENCES users (id)        ON DELETE SET NULL,
    CONSTRAINT fk_lreq_updated_by FOREIGN KEY (updated_by)    REFERENCES users (id)        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fees (
    id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    university_id    INT UNSIGNED  NOT NULL,
    program_id       INT UNSIGNED  NULL,
    scope_type       ENUM('PROGRAM','UNIVERSITY_ALL') NOT NULL DEFAULT 'PROGRAM',
    fee_type         ENUM('TUITION_PER_YEAR','TUITION_TOTAL','APPLICATION','REGISTRATION','DEPOSIT','LAB','LIBRARY','OTHER') NOT NULL,
    amount           DECIMAL(12,2) NOT NULL,
    currency_code    CHAR(3)       NOT NULL DEFAULT 'USD',
    academic_year    SMALLINT UNSIGNED NULL,
    notes            TEXT          NULL,
    source_id        INT UNSIGNED  NULL,
    last_verified_at DATETIME      NULL,
    verified_by      INT UNSIGNED  NULL,
    confidence_level ENUM('UNVERIFIED','LOW','MEDIUM','HIGH') NOT NULL DEFAULT 'UNVERIFIED',
    is_sample        TINYINT(1)    NOT NULL DEFAULT 0,
    archived_at      DATETIME      NULL,
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by       INT UNSIGNED  NULL,
    updated_by       INT UNSIGNED  NULL,
    PRIMARY KEY (id),
    KEY idx_fees_program (program_id),
    KEY idx_fees_university_scope (university_id, scope_type),
    KEY idx_fees_currency (currency_code),
    CONSTRAINT chk_fees_scope CHECK (
        (scope_type = 'PROGRAM' AND program_id IS NOT NULL) OR
        (scope_type = 'UNIVERSITY_ALL' AND program_id IS NULL)),
    CONSTRAINT fk_fees_university FOREIGN KEY (university_id) REFERENCES universities (id) ON DELETE RESTRICT,
    CONSTRAINT fk_fees_program    FOREIGN KEY (program_id)    REFERENCES programs (id)     ON DELETE RESTRICT,
    CONSTRAINT fk_fees_source     FOREIGN KEY (source_id)     REFERENCES sources (id)      ON DELETE SET NULL,
    CONSTRAINT fk_fees_verifier   FOREIGN KEY (verified_by)   REFERENCES users (id)        ON DELETE SET NULL,
    CONSTRAINT fk_fees_created_by FOREIGN KEY (created_by)    REFERENCES users (id)        ON DELETE SET NULL,
    CONSTRAINT fk_fees_updated_by FOREIGN KEY (updated_by)    REFERENCES users (id)        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS scholarships (
    id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    university_id    INT UNSIGNED  NOT NULL,
    program_id       INT UNSIGNED  NULL,
    scope_type       ENUM('PROGRAM','UNIVERSITY_ALL') NOT NULL DEFAULT 'PROGRAM',
    name             VARCHAR(190)  NOT NULL,
    scholarship_type ENUM('MERIT','NEED','COUNTRY_SPECIFIC','PARTNER','OTHER') NOT NULL DEFAULT 'MERIT',
    coverage_summary VARCHAR(500)  NULL,
    eligibility_notes TEXT         NULL,
    deadline_date    DATE          NULL,
    source_id        INT UNSIGNED  NULL,
    last_verified_at DATETIME      NULL,
    verified_by      INT UNSIGNED  NULL,
    confidence_level ENUM('UNVERIFIED','LOW','MEDIUM','HIGH') NOT NULL DEFAULT 'UNVERIFIED',
    is_sample        TINYINT(1)    NOT NULL DEFAULT 0,
    archived_at      DATETIME      NULL,
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by       INT UNSIGNED  NULL,
    updated_by       INT UNSIGNED  NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_scholarships_scope_name (university_id, program_id, name),
    KEY idx_scholarships_program (program_id),
    CONSTRAINT chk_sch_scope CHECK (
        (scope_type = 'PROGRAM' AND program_id IS NOT NULL) OR
        (scope_type = 'UNIVERSITY_ALL' AND program_id IS NULL)),
    CONSTRAINT fk_sch_university FOREIGN KEY (university_id) REFERENCES universities (id) ON DELETE RESTRICT,
    CONSTRAINT fk_sch_program    FOREIGN KEY (program_id)    REFERENCES programs (id)     ON DELETE RESTRICT,
    CONSTRAINT fk_sch_source     FOREIGN KEY (source_id)     REFERENCES sources (id)      ON DELETE SET NULL,
    CONSTRAINT fk_sch_verifier   FOREIGN KEY (verified_by)   REFERENCES users (id)        ON DELETE SET NULL,
    CONSTRAINT fk_sch_created_by FOREIGN KEY (created_by)    REFERENCES users (id)        ON DELETE SET NULL,
    CONSTRAINT fk_sch_updated_by FOREIGN KEY (updated_by)    REFERENCES users (id)        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS scholarship_tiers (
    id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    scholarship_id   INT UNSIGNED  NOT NULL,
    tier_name        VARCHAR(120)  NOT NULL COMMENT 'e.g. "Tier A — 100%"',
    coverage_percent DECIMAL(5,2)  NULL COMMENT 'percentage of tuition covered',
    fixed_amount     DECIMAL(12,2) NULL,
    currency_code    CHAR(3)       NULL,
    criteria_text    TEXT          NULL,
    slots_total      INT UNSIGNED  NULL,
    source_id        INT UNSIGNED  NULL,
    last_verified_at DATETIME      NULL,
    verified_by      INT UNSIGNED  NULL,
    confidence_level ENUM('UNVERIFIED','LOW','MEDIUM','HIGH') NOT NULL DEFAULT 'UNVERIFIED',
    is_sample        TINYINT(1)    NOT NULL DEFAULT 0,
    archived_at      DATETIME      NULL,
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by       INT UNSIGNED  NULL,
    updated_by       INT UNSIGNED  NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_tiers_scholarship_name (scholarship_id, tier_name),
    CONSTRAINT fk_tiers_scholarship FOREIGN KEY (scholarship_id) REFERENCES scholarships (id) ON DELETE RESTRICT,
    CONSTRAINT fk_tiers_source      FOREIGN KEY (source_id)      REFERENCES sources (id)      ON DELETE SET NULL,
    CONSTRAINT fk_tiers_verifier    FOREIGN KEY (verified_by)    REFERENCES users (id)        ON DELETE SET NULL,
    CONSTRAINT fk_tiers_created_by  FOREIGN KEY (created_by)     REFERENCES users (id)        ON DELETE SET NULL,
    CONSTRAINT fk_tiers_updated_by  FOREIGN KEY (updated_by)     REFERENCES users (id)        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS document_requirements (
    id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    university_id    INT UNSIGNED  NOT NULL,
    program_id       INT UNSIGNED  NULL,
    scope_type       ENUM('PROGRAM','UNIVERSITY_ALL') NOT NULL DEFAULT 'PROGRAM',
    document_name    VARCHAR(190)  NOT NULL,
    is_mandatory     TINYINT(1)    NOT NULL DEFAULT 1,
    format_notes     TEXT          NULL COMMENT 'attestation/notarisation/translation rules',
    source_id        INT UNSIGNED  NULL,
    last_verified_at DATETIME      NULL,
    verified_by      INT UNSIGNED  NULL,
    confidence_level ENUM('UNVERIFIED','LOW','MEDIUM','HIGH') NOT NULL DEFAULT 'UNVERIFIED',
    is_sample        TINYINT(1)    NOT NULL DEFAULT 0,
    archived_at      DATETIME      NULL,
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by       INT UNSIGNED  NULL,
    updated_by       INT UNSIGNED  NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_docs_scope_name (university_id, program_id, document_name),
    KEY idx_docs_program (program_id),
    CONSTRAINT chk_docs_scope CHECK (
        (scope_type = 'PROGRAM' AND program_id IS NOT NULL) OR
        (scope_type = 'UNIVERSITY_ALL' AND program_id IS NULL)),
    CONSTRAINT fk_docs_university FOREIGN KEY (university_id) REFERENCES universities (id) ON DELETE RESTRICT,
    CONSTRAINT fk_docs_program    FOREIGN KEY (program_id)    REFERENCES programs (id)     ON DELETE RESTRICT,
    CONSTRAINT fk_docs_source     FOREIGN KEY (source_id)     REFERENCES sources (id)      ON DELETE SET NULL,
    CONSTRAINT fk_docs_verifier   FOREIGN KEY (verified_by)   REFERENCES users (id)        ON DELETE SET NULL,
    CONSTRAINT fk_docs_created_by FOREIGN KEY (created_by)    REFERENCES users (id)        ON DELETE SET NULL,
    CONSTRAINT fk_docs_updated_by FOREIGN KEY (updated_by)    REFERENCES users (id)        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS visa_information (
    id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    country_id       INT UNSIGNED  NOT NULL,
    university_id    INT UNSIGNED  NULL COMMENT 'NULL = country-level general visa info',
    visa_category    VARCHAR(120)  NOT NULL COMMENT 'e.g. "Student Pass"',
    process_summary  TEXT          NULL,
    financial_requirement VARCHAR(500) NULL,
    processing_time_days SMALLINT UNSIGNED NULL,
    interview_required TINYINT(1)  NULL,
    source_id        INT UNSIGNED  NULL,
    last_verified_at DATETIME      NULL,
    verified_by      INT UNSIGNED  NULL,
    confidence_level ENUM('UNVERIFIED','LOW','MEDIUM','HIGH') NOT NULL DEFAULT 'UNVERIFIED',
    is_sample        TINYINT(1)    NOT NULL DEFAULT 0,
    archived_at      DATETIME      NULL,
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by       INT UNSIGNED  NULL,
    updated_by       INT UNSIGNED  NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_visa_country_univ_category (country_id, university_id, visa_category),
    CONSTRAINT fk_visa_country    FOREIGN KEY (country_id)    REFERENCES countries (id)    ON DELETE RESTRICT,
    CONSTRAINT fk_visa_university FOREIGN KEY (university_id) REFERENCES universities (id) ON DELETE RESTRICT,
    CONSTRAINT fk_visa_source     FOREIGN KEY (source_id)     REFERENCES sources (id)      ON DELETE SET NULL,
    CONSTRAINT fk_visa_verifier   FOREIGN KEY (verified_by)   REFERENCES users (id)        ON DELETE SET NULL,
    CONSTRAINT fk_visa_created_by FOREIGN KEY (created_by)    REFERENCES users (id)        ON DELETE SET NULL,
    CONSTRAINT fk_visa_updated_by FOREIGN KEY (updated_by)    REFERENCES users (id)        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS country_specific_requirements (
    id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    country_id       INT UNSIGNED  NOT NULL COMMENT 'applicant nationality country',
    university_id    INT UNSIGNED  NULL COMMENT 'NULL = applies to all universities in destination context',
    program_id       INT UNSIGNED  NULL COMMENT 'set only when scoped to one program; must imply university_id',
    requirement_text TEXT          NOT NULL,
    effective_from   DATE          NULL,
    effective_to     DATE          NULL,
    source_id        INT UNSIGNED  NULL,
    last_verified_at DATETIME      NULL,
    verified_by      INT UNSIGNED  NULL,
    confidence_level ENUM('UNVERIFIED','LOW','MEDIUM','HIGH') NOT NULL DEFAULT 'UNVERIFIED',
    is_sample        TINYINT(1)    NOT NULL DEFAULT 0,
    archived_at      DATETIME      NULL,
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by       INT UNSIGNED  NULL,
    updated_by       INT UNSIGNED  NULL,
    PRIMARY KEY (id),
    KEY idx_csr_country (country_id),
    KEY idx_csr_program (program_id),
    CONSTRAINT chk_csr_program_implies_university CHECK (
        program_id IS NULL OR university_id IS NOT NULL),
    CONSTRAINT fk_csr_country    FOREIGN KEY (country_id)    REFERENCES countries (id)    ON DELETE RESTRICT,
    CONSTRAINT fk_csr_university FOREIGN KEY (university_id) REFERENCES universities (id) ON DELETE RESTRICT,
    CONSTRAINT fk_csr_program    FOREIGN KEY (program_id)    REFERENCES programs (id)     ON DELETE RESTRICT,
    CONSTRAINT fk_csr_source     FOREIGN KEY (source_id)     REFERENCES sources (id)      ON DELETE SET NULL,
    CONSTRAINT fk_csr_verifier   FOREIGN KEY (verified_by)   REFERENCES users (id)        ON DELETE SET NULL,
    CONSTRAINT fk_csr_created_by FOREIGN KEY (created_by)    REFERENCES users (id)        ON DELETE SET NULL,
    CONSTRAINT fk_csr_updated_by FOREIGN KEY (updated_by)    REFERENCES users (id)        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- settings — key/value store for UI lists & options (workbook 01_Settings/_Lists)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    setting_key   VARCHAR(120) NOT NULL,
    setting_value TEXT         NOT NULL,
    value_type    ENUM('STRING','NUMBER','BOOL','JSON_LIST') NOT NULL DEFAULT 'STRING',
    description   VARCHAR(500) NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by    INT UNSIGNED NULL,
    updated_by    INT UNSIGNED NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_settings_key (setting_key),
    CONSTRAINT fk_settings_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_settings_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- audit_logs — append-only from the UI; no update/delete path exists.
-- Never stores passwords or session tokens.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_logs (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    actor_user_id INT UNSIGNED    NULL COMMENT 'NULL for system/seed actions',
    action        VARCHAR(60)     NOT NULL COMMENT 'e.g. CREATE, UPDATE, ARCHIVE, RESTORE, LOGIN, LOGOUT',
    entity_type   VARCHAR(60)     NOT NULL COMMENT 'table name, e.g. universities',
    entity_id     BIGINT UNSIGNED NULL,
    old_values    MEDIUMTEXT      NULL COMMENT 'safely JSON-encoded',
    new_values    MEDIUMTEXT      NULL COMMENT 'safely JSON-encoded',
    ip_address    VARCHAR(45)     NULL,
    created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_entity (entity_type, entity_id),
    KEY idx_audit_actor_time (actor_user_id, created_at),
    CONSTRAINT fk_audit_actor FOREIGN KEY (actor_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
