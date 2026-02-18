-- ============================================================
-- Red Sea Yacht Institute – Student Affairs Management System
-- Database Schema  (prefix: wp_rsyi_)
-- ============================================================

-- ----------------------------------------------------------
-- 1. COHORTS
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_rsyi_cohorts` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(120)    NOT NULL,
    `code`        VARCHAR(30)     NOT NULL UNIQUE,
    `start_date`  DATE            DEFAULT NULL,
    `end_date`    DATE            DEFAULT NULL,
    `is_active`   TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_cohorts_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 2. STUDENT PROFILES  (extends wp_users)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_rsyi_student_profiles` (
    `id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`              BIGINT UNSIGNED NOT NULL,          -- FK → wp_users.ID
    `cohort_id`            BIGINT UNSIGNED NOT NULL,          -- FK → wp_rsyi_cohorts.id (immutable)
    `arabic_full_name`     VARCHAR(255)    NOT NULL,
    `english_full_name`    VARCHAR(255)    NOT NULL,
    `national_id_number`   VARCHAR(20)     DEFAULT NULL,
    `date_of_birth`        DATE            DEFAULT NULL,
    `phone`                VARCHAR(30)     DEFAULT NULL,
    `status`               ENUM('pending_docs','active','suspended','expelled')
                                           NOT NULL DEFAULT 'pending_docs',
    `created_by`           BIGINT UNSIGNED DEFAULT NULL,      -- staff who created, NULL = self-reg
    `created_at`           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_student_user` (`user_id`),
    KEY `idx_student_cohort` (`cohort_id`),
    KEY `idx_student_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 3. DOCUMENTS
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_rsyi_documents` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id`      BIGINT UNSIGNED NOT NULL,              -- FK → wp_rsyi_student_profiles.id
    `doc_type`        ENUM(
                          'DOC01_national_id_front',
                          'DOC02_national_id_back',
                          'DOC03_birth_certificate',
                          'DOC04_military_certificate',
                          'DOC05_highschool_certificate',
                          'DOC06_graduation_certificate',
                          'DOC07_police_record_foundation',
                          'DOC08_police_record_authority'
                      ) NOT NULL,
    `file_path`       VARCHAR(500)    NOT NULL,              -- relative to wp-content/uploads/rsyi-docs/
    `file_name_orig`  VARCHAR(255)    NOT NULL,
    `file_size`       INT UNSIGNED    DEFAULT NULL,
    `mime_type`       VARCHAR(100)    DEFAULT NULL,
    `status`          ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `rejection_reason`TEXT            DEFAULT NULL,
    `uploaded_by`     BIGINT UNSIGNED NOT NULL,              -- FK → wp_users.ID
    `reviewed_by`     BIGINT UNSIGNED DEFAULT NULL,
    `reviewed_at`     DATETIME        DEFAULT NULL,
    `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_docs_student`  (`student_id`),
    KEY `idx_docs_type`     (`doc_type`),
    KEY `idx_docs_status`   (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 4. EXIT PERMITS
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_rsyi_exit_permits` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id`      BIGINT UNSIGNED NOT NULL,
    `from_datetime`   DATETIME        NOT NULL,
    `to_datetime`     DATETIME        NOT NULL,
    `reason`          TEXT            NOT NULL,
    `status`          ENUM(
                          'pending_dorm',
                          'pending_manager',
                          'approved',
                          'rejected',
                          'executed'
                      ) NOT NULL DEFAULT 'pending_dorm',
    `dorm_supervisor_id`   BIGINT UNSIGNED DEFAULT NULL,
    `dorm_approved_at`     DATETIME        DEFAULT NULL,
    `dorm_rejected_at`     DATETIME        DEFAULT NULL,
    `dorm_notes`           TEXT            DEFAULT NULL,
    `manager_id`           BIGINT UNSIGNED DEFAULT NULL,
    `manager_approved_at`  DATETIME        DEFAULT NULL,
    `manager_rejected_at`  DATETIME        DEFAULT NULL,
    `manager_notes`        TEXT            DEFAULT NULL,
    `executed_by`          BIGINT UNSIGNED DEFAULT NULL,
    `executed_at`          DATETIME        DEFAULT NULL,
    `created_at`           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_exit_student` (`student_id`),
    KEY `idx_exit_status`  (`status`),
    KEY `idx_exit_from`    (`from_datetime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 5. OVERNIGHT PERMITS
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_rsyi_overnight_permits` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id`      BIGINT UNSIGNED NOT NULL,
    `from_datetime`   DATETIME        NOT NULL,
    `to_datetime`     DATETIME        NOT NULL,
    `reason`          TEXT            NOT NULL,
    `status`          ENUM(
                          'pending_supervisor',
                          'pending_manager',
                          'pending_dean',
                          'approved',
                          'rejected',
                          'executed'
                      ) NOT NULL DEFAULT 'pending_supervisor',
    -- Step 1: Student Supervisor
    `supervisor_id`         BIGINT UNSIGNED DEFAULT NULL,
    `supervisor_approved_at` DATETIME       DEFAULT NULL,
    `supervisor_rejected_at` DATETIME       DEFAULT NULL,
    `supervisor_notes`       TEXT           DEFAULT NULL,
    -- Step 2: Student Affairs Manager
    `manager_id`            BIGINT UNSIGNED DEFAULT NULL,
    `manager_approved_at`   DATETIME        DEFAULT NULL,
    `manager_rejected_at`   DATETIME        DEFAULT NULL,
    `manager_notes`         TEXT            DEFAULT NULL,
    -- Step 3: Dean
    `dean_id`               BIGINT UNSIGNED DEFAULT NULL,
    `dean_approved_at`      DATETIME        DEFAULT NULL,
    `dean_rejected_at`      DATETIME        DEFAULT NULL,
    `dean_notes`            TEXT            DEFAULT NULL,
    -- Execution
    `executed_by`           BIGINT UNSIGNED DEFAULT NULL,
    `executed_at`           DATETIME        DEFAULT NULL,
    `created_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_overnight_student` (`student_id`),
    KEY `idx_overnight_status`  (`status`),
    KEY `idx_overnight_from`    (`from_datetime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 6. VIOLATION TYPES CATALOG
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_rsyi_violation_types` (
    `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name_ar`            VARCHAR(255)    NOT NULL,
    `name_en`            VARCHAR(255)    NOT NULL,
    `description`        TEXT            DEFAULT NULL,
    `default_points`     TINYINT UNSIGNED NOT NULL DEFAULT 5,
    `max_points`         TINYINT UNSIGNED NOT NULL DEFAULT 30,
    `requires_dean`      TINYINT(1)       NOT NULL DEFAULT 0,
    `is_dean_discretion` TINYINT(1)       NOT NULL DEFAULT 0,
    `is_active`          TINYINT(1)       NOT NULL DEFAULT 1,
    `created_at`         DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 7. BEHAVIOR VIOLATIONS  (actual incidents)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_rsyi_violations` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id`       BIGINT UNSIGNED NOT NULL,
    `violation_type_id` BIGINT UNSIGNED NOT NULL,
    `points_assigned`  TINYINT UNSIGNED NOT NULL,
    `incident_date`    DATE            NOT NULL,
    `description`      TEXT            DEFAULT NULL,
    `assigned_by`      BIGINT UNSIGNED NOT NULL,       -- staff user_id
    `dean_override`    TINYINT(1)      NOT NULL DEFAULT 0,
    `status`           ENUM('active','overturned')     NOT NULL DEFAULT 'active',
    `overturned_by`    BIGINT UNSIGNED DEFAULT NULL,
    `overturned_at`    DATETIME        DEFAULT NULL,
    `overturned_reason` TEXT           DEFAULT NULL,
    `created_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_violations_student`  (`student_id`),
    KEY `idx_violations_type`     (`violation_type_id`),
    KEY `idx_violations_date`     (`incident_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 8. BEHAVIOR WARNINGS  (threshold events: 10/20/30/40)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_rsyi_behavior_warnings` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id`       BIGINT UNSIGNED NOT NULL,
    `threshold`        TINYINT UNSIGNED NOT NULL,           -- 10, 20, 30, 40
    `total_points_at_warning` TINYINT UNSIGNED NOT NULL,
    `email_sent_at`    DATETIME        DEFAULT NULL,
    `acknowledged_at`  DATETIME        DEFAULT NULL,        -- student digital ack
    `ack_ip`           VARCHAR(45)     DEFAULT NULL,
    `created_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_warnings_student`   (`student_id`),
    KEY `idx_warnings_threshold` (`threshold`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 9. EXPULSION CASES
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_rsyi_expulsion_cases` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id`        BIGINT UNSIGNED NOT NULL,
    `triggered_by`      VARCHAR(50)     NOT NULL DEFAULT '40_points',
    `total_points`      TINYINT UNSIGNED NOT NULL,
    `status`            ENUM('pending_dean','approved','rejected') NOT NULL DEFAULT 'pending_dean',
    `dean_id`           BIGINT UNSIGNED DEFAULT NULL,
    `dean_decided_at`   DATETIME        DEFAULT NULL,
    `dean_notes`        TEXT            DEFAULT NULL,
    `letter_path`       VARCHAR(500)    DEFAULT NULL,
    `letter_generated_at` DATETIME      DEFAULT NULL,
    `executed_at`       DATETIME        DEFAULT NULL,
    `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_expulsion_student` (`student_id`),
    KEY `idx_expulsion_status`  (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 10. COHORT TRANSFERS
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_rsyi_cohort_transfers` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id`       BIGINT UNSIGNED NOT NULL,
    `from_cohort_id`   BIGINT UNSIGNED NOT NULL,
    `to_cohort_id`     BIGINT UNSIGNED NOT NULL,
    `reason`           TEXT            DEFAULT NULL,
    `requested_by`     BIGINT UNSIGNED NOT NULL,
    `status`           ENUM('pending_dean','approved','rejected') NOT NULL DEFAULT 'pending_dean',
    `dean_id`          BIGINT UNSIGNED DEFAULT NULL,
    `dean_decided_at`  DATETIME        DEFAULT NULL,
    `dean_notes`       TEXT            DEFAULT NULL,
    `executed_at`      DATETIME        DEFAULT NULL,
    `created_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_transfer_student`  (`student_id`),
    KEY `idx_transfer_status`   (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 11. AUDIT LOG
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_rsyi_audit_log` (
    `id`            BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `actor_user_id` BIGINT UNSIGNED  NOT NULL,
    `entity_type`   VARCHAR(60)      NOT NULL,   -- e.g. 'student_profile','document','exit_permit'
    `entity_id`     BIGINT UNSIGNED  NOT NULL,
    `action`        VARCHAR(60)      NOT NULL,   -- e.g. 'create','approve','reject','execute'
    `details_json`  LONGTEXT         DEFAULT NULL,
    `ip_address`    VARCHAR(45)      DEFAULT NULL,
    `user_agent`    VARCHAR(255)     DEFAULT NULL,
    `created_at`    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_actor`  (`actor_user_id`),
    KEY `idx_audit_entity` (`entity_type`,`entity_id`),
    KEY `idx_audit_action` (`action`),
    KEY `idx_audit_date`   (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
