<?php
/**
 * Database Installer
 * Creates / upgrades all custom tables on plugin activation.
 *
 * @package RSYI_StudentAffairs
 */

namespace RSYI_SA;

defined( 'ABSPATH' ) || exit;

class DB_Installer {

    const DB_VERSION_OPTION = 'rsyi_sa_db_version';
    const DB_VERSION        = '1.0.0';

    /**
     * Full activation sequence: tables + roles + upload dir + rewrite flush.
     */
    public static function activate(): void {
        self::create_tables();
        Roles::add_roles();
        self::create_upload_dir();
        self::seed_violation_types();
        flush_rewrite_rules();
        update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
    }

    /**
     * Create / upgrade all custom tables using dbDelta.
     */
    public static function create_tables(): void {
        global $wpdb;

        $charset = $wpdb->get_charset_collate();

        $sqls = self::get_table_sql( $charset );

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        foreach ( $sqls as $sql ) {
            dbDelta( $sql );
        }
    }

    /**
     * Array of CREATE TABLE statements (dbDelta-compatible).
     */
    private static function get_table_sql( string $charset ): array {
        global $wpdb;
        $p = $wpdb->prefix;

        return [

            // ── Cohorts ──────────────────────────────────────────────
            "CREATE TABLE {$p}rsyi_cohorts (
                id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                name        VARCHAR(120)    NOT NULL,
                code        VARCHAR(30)     NOT NULL,
                start_date  DATE            DEFAULT NULL,
                end_date    DATE            DEFAULT NULL,
                is_active   TINYINT(1)      NOT NULL DEFAULT 1,
                created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                UNIQUE KEY uq_cohort_code (code),
                KEY idx_cohorts_active (is_active)
            ) $charset;",

            // ── Student Profiles ──────────────────────────────────────
            "CREATE TABLE {$p}rsyi_student_profiles (
                id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id            BIGINT UNSIGNED NOT NULL,
                cohort_id          BIGINT UNSIGNED NOT NULL,
                arabic_full_name   VARCHAR(255)    NOT NULL,
                english_full_name  VARCHAR(255)    NOT NULL,
                national_id_number VARCHAR(20)     DEFAULT NULL,
                date_of_birth      DATE            DEFAULT NULL,
                phone              VARCHAR(30)     DEFAULT NULL,
                status             VARCHAR(20)     NOT NULL DEFAULT 'pending_docs',
                created_by         BIGINT UNSIGNED DEFAULT NULL,
                created_at         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_student_user (user_id),
                KEY idx_student_cohort (cohort_id),
                KEY idx_student_status (status)
            ) $charset;",

            // ── Documents ────────────────────────────────────────────
            "CREATE TABLE {$p}rsyi_documents (
                id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                student_id       BIGINT UNSIGNED NOT NULL,
                doc_type         VARCHAR(60)     NOT NULL,
                file_path        VARCHAR(500)    NOT NULL,
                file_name_orig   VARCHAR(255)    NOT NULL,
                file_size        INT UNSIGNED    DEFAULT NULL,
                mime_type        VARCHAR(100)    DEFAULT NULL,
                status           VARCHAR(20)     NOT NULL DEFAULT 'pending',
                rejection_reason TEXT            DEFAULT NULL,
                uploaded_by      BIGINT UNSIGNED NOT NULL,
                reviewed_by      BIGINT UNSIGNED DEFAULT NULL,
                reviewed_at      DATETIME        DEFAULT NULL,
                created_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_docs_student (student_id),
                KEY idx_docs_type    (doc_type),
                KEY idx_docs_status  (status)
            ) $charset;",

            // ── Exit Permits ─────────────────────────────────────────
            "CREATE TABLE {$p}rsyi_exit_permits (
                id                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                student_id           BIGINT UNSIGNED NOT NULL,
                from_datetime        DATETIME        NOT NULL,
                to_datetime          DATETIME        NOT NULL,
                reason               TEXT            NOT NULL,
                status               VARCHAR(30)     NOT NULL DEFAULT 'pending_dorm',
                dorm_supervisor_id   BIGINT UNSIGNED DEFAULT NULL,
                dorm_approved_at     DATETIME        DEFAULT NULL,
                dorm_rejected_at     DATETIME        DEFAULT NULL,
                dorm_notes           TEXT            DEFAULT NULL,
                manager_id           BIGINT UNSIGNED DEFAULT NULL,
                manager_approved_at  DATETIME        DEFAULT NULL,
                manager_rejected_at  DATETIME        DEFAULT NULL,
                manager_notes        TEXT            DEFAULT NULL,
                executed_by          BIGINT UNSIGNED DEFAULT NULL,
                executed_at          DATETIME        DEFAULT NULL,
                created_at           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_exit_student (student_id),
                KEY idx_exit_status  (status),
                KEY idx_exit_from    (from_datetime)
            ) $charset;",

            // ── Overnight Permits ────────────────────────────────────
            "CREATE TABLE {$p}rsyi_overnight_permits (
                id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                student_id              BIGINT UNSIGNED NOT NULL,
                from_datetime           DATETIME        NOT NULL,
                to_datetime             DATETIME        NOT NULL,
                reason                  TEXT            NOT NULL,
                status                  VARCHAR(30)     NOT NULL DEFAULT 'pending_supervisor',
                supervisor_id           BIGINT UNSIGNED DEFAULT NULL,
                supervisor_approved_at  DATETIME        DEFAULT NULL,
                supervisor_rejected_at  DATETIME        DEFAULT NULL,
                supervisor_notes        TEXT            DEFAULT NULL,
                manager_id              BIGINT UNSIGNED DEFAULT NULL,
                manager_approved_at     DATETIME        DEFAULT NULL,
                manager_rejected_at     DATETIME        DEFAULT NULL,
                manager_notes           TEXT            DEFAULT NULL,
                dean_id                 BIGINT UNSIGNED DEFAULT NULL,
                dean_approved_at        DATETIME        DEFAULT NULL,
                dean_rejected_at        DATETIME        DEFAULT NULL,
                dean_notes              TEXT            DEFAULT NULL,
                executed_by             BIGINT UNSIGNED DEFAULT NULL,
                executed_at             DATETIME        DEFAULT NULL,
                created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_overnight_student (student_id),
                KEY idx_overnight_status  (status),
                KEY idx_overnight_from    (from_datetime)
            ) $charset;",

            // ── Violation Types ──────────────────────────────────────
            "CREATE TABLE {$p}rsyi_violation_types (
                id                  BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
                name_ar             VARCHAR(255)     NOT NULL,
                name_en             VARCHAR(255)     NOT NULL,
                description         TEXT             DEFAULT NULL,
                default_points      TINYINT UNSIGNED NOT NULL DEFAULT 5,
                max_points          TINYINT UNSIGNED NOT NULL DEFAULT 30,
                requires_dean       TINYINT(1)       NOT NULL DEFAULT 0,
                is_dean_discretion  TINYINT(1)       NOT NULL DEFAULT 0,
                is_active           TINYINT(1)       NOT NULL DEFAULT 1,
                created_at          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) $charset;",

            // ── Violations ───────────────────────────────────────────
            "CREATE TABLE {$p}rsyi_violations (
                id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                student_id         BIGINT UNSIGNED NOT NULL,
                violation_type_id  BIGINT UNSIGNED NOT NULL,
                points_assigned    TINYINT UNSIGNED NOT NULL,
                incident_date      DATE            NOT NULL,
                description        TEXT            DEFAULT NULL,
                assigned_by        BIGINT UNSIGNED NOT NULL,
                dean_override      TINYINT(1)      NOT NULL DEFAULT 0,
                status             VARCHAR(20)     NOT NULL DEFAULT 'active',
                overturned_by      BIGINT UNSIGNED DEFAULT NULL,
                overturned_at      DATETIME        DEFAULT NULL,
                overturned_reason  TEXT            DEFAULT NULL,
                created_at         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_violations_student (student_id),
                KEY idx_violations_type    (violation_type_id),
                KEY idx_violations_date    (incident_date)
            ) $charset;",

            // ── Behavior Warnings ────────────────────────────────────
            "CREATE TABLE {$p}rsyi_behavior_warnings (
                id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                student_id              BIGINT UNSIGNED NOT NULL,
                threshold               TINYINT UNSIGNED NOT NULL,
                total_points_at_warning TINYINT UNSIGNED NOT NULL,
                email_sent_at           DATETIME        DEFAULT NULL,
                acknowledged_at         DATETIME        DEFAULT NULL,
                ack_ip                  VARCHAR(45)     DEFAULT NULL,
                created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_warnings_student   (student_id),
                KEY idx_warnings_threshold (threshold)
            ) $charset;",

            // ── Expulsion Cases ──────────────────────────────────────
            "CREATE TABLE {$p}rsyi_expulsion_cases (
                id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                student_id          BIGINT UNSIGNED NOT NULL,
                triggered_by        VARCHAR(50)     NOT NULL DEFAULT '40_points',
                total_points        TINYINT UNSIGNED NOT NULL,
                status              VARCHAR(20)     NOT NULL DEFAULT 'pending_dean',
                dean_id             BIGINT UNSIGNED DEFAULT NULL,
                dean_decided_at     DATETIME        DEFAULT NULL,
                dean_notes          TEXT            DEFAULT NULL,
                letter_path         VARCHAR(500)    DEFAULT NULL,
                letter_generated_at DATETIME        DEFAULT NULL,
                executed_at         DATETIME        DEFAULT NULL,
                created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_expulsion_student (student_id),
                KEY idx_expulsion_status  (status)
            ) $charset;",

            // ── Cohort Transfers ─────────────────────────────────────
            "CREATE TABLE {$p}rsyi_cohort_transfers (
                id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                student_id      BIGINT UNSIGNED NOT NULL,
                from_cohort_id  BIGINT UNSIGNED NOT NULL,
                to_cohort_id    BIGINT UNSIGNED NOT NULL,
                reason          TEXT            DEFAULT NULL,
                requested_by    BIGINT UNSIGNED NOT NULL,
                status          VARCHAR(20)     NOT NULL DEFAULT 'pending_dean',
                dean_id         BIGINT UNSIGNED DEFAULT NULL,
                dean_decided_at DATETIME        DEFAULT NULL,
                dean_notes      TEXT            DEFAULT NULL,
                executed_at     DATETIME        DEFAULT NULL,
                created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_transfer_student (student_id),
                KEY idx_transfer_status  (status)
            ) $charset;",

            // ── Audit Log ────────────────────────────────────────────
            "CREATE TABLE {$p}rsyi_audit_log (
                id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                actor_user_id BIGINT UNSIGNED NOT NULL,
                entity_type   VARCHAR(60)     NOT NULL,
                entity_id     BIGINT UNSIGNED NOT NULL,
                action        VARCHAR(60)     NOT NULL,
                details_json  LONGTEXT        DEFAULT NULL,
                ip_address    VARCHAR(45)     DEFAULT NULL,
                user_agent    VARCHAR(255)    DEFAULT NULL,
                created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_audit_actor  (actor_user_id),
                KEY idx_audit_entity (entity_type, entity_id),
                KEY idx_audit_action (action),
                KEY idx_audit_date   (created_at)
            ) $charset;",
        ];
    }

    /**
     * Ensure the private uploads directory exists and is protected.
     */
    private static function create_upload_dir(): void {
        $dir = RSYI_SA_UPLOAD_DIR;
        if ( ! is_dir( $dir ) ) {
            wp_mkdir_p( $dir );
        }
        // Block direct HTTP access
        $htaccess = $dir . '/.htaccess';
        if ( ! file_exists( $htaccess ) ) {
            file_put_contents( $htaccess, "Options -Indexes\nDeny from all\n" );
        }
        // Fallback for Nginx: create an index file
        $index = $dir . '/index.php';
        if ( ! file_exists( $index ) ) {
            file_put_contents( $index, "<?php // Silence is golden.\n" );
        }
    }

    /**
     * Seed default violation types.
     */
    private static function seed_violation_types(): void {
        global $wpdb;
        $table = $wpdb->prefix . 'rsyi_violation_types';

        // Only seed if empty
        $count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
        if ( $count > 0 ) {
            return;
        }

        $types = [
            [ 'name_ar' => 'التأخر عن الحضور',         'name_en' => 'Late Attendance',         'default_points' => 3,  'max_points' => 10, 'requires_dean' => 0, 'is_dean_discretion' => 0 ],
            [ 'name_ar' => 'الغياب بدون إذن',           'name_en' => 'Absent Without Leave',    'default_points' => 5,  'max_points' => 15, 'requires_dean' => 0, 'is_dean_discretion' => 0 ],
            [ 'name_ar' => 'السلوك غير اللائق',         'name_en' => 'Inappropriate Behavior',  'default_points' => 10, 'max_points' => 20, 'requires_dean' => 0, 'is_dean_discretion' => 0 ],
            [ 'name_ar' => 'مخالفة قواعد السكن',        'name_en' => 'Dorm Rules Violation',    'default_points' => 7,  'max_points' => 15, 'requires_dean' => 0, 'is_dean_discretion' => 0 ],
            [ 'name_ar' => 'الاعتداء الجسدي',           'name_en' => 'Physical Assault',        'default_points' => 20, 'max_points' => 30, 'requires_dean' => 1, 'is_dean_discretion' => 0 ],
            [ 'name_ar' => 'حيازة مواد مخدرة',          'name_en' => 'Possession of Narcotics', 'default_points' => 30, 'max_points' => 30, 'requires_dean' => 1, 'is_dean_discretion' => 1 ],
            [ 'name_ar' => 'التحرش أو الإساءة الجنسية', 'name_en' => 'Sexual Harassment/Abuse', 'default_points' => 30, 'max_points' => 30, 'requires_dean' => 1, 'is_dean_discretion' => 1 ],
            [ 'name_ar' => 'مخالفة تقديرية – العميد',   'name_en' => 'Dean Discretionary',      'default_points' => 5,  'max_points' => 30, 'requires_dean' => 1, 'is_dean_discretion' => 1 ],
        ];

        foreach ( $types as $t ) {
            $wpdb->insert( $table, $t );
        }
    }
}
