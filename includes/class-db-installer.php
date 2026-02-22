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
    const DB_VERSION        = '1.2.0';

    /**
     * Full activation sequence: tables + roles + upload dir + rewrite flush.
     */
    public static function activate(): void {
        self::create_tables();
        Roles::add_roles();
        self::create_upload_dir();
        self::seed_violation_types();
        self::create_portal_pages();
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

            // ── Evaluation Periods ───────────────────────────────────
            "CREATE TABLE {$p}rsyi_evaluation_periods (
                id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                name        VARCHAR(120)    NOT NULL,
                cohort_id   BIGINT UNSIGNED NOT NULL,
                start_date  DATE            DEFAULT NULL,
                end_date    DATE            DEFAULT NULL,
                is_active   TINYINT(1)      NOT NULL DEFAULT 1,
                created_by  BIGINT UNSIGNED NOT NULL,
                created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_eval_period_cohort (cohort_id),
                KEY idx_eval_period_active (is_active)
            ) $charset;",

            // ── Peer Evaluations ─────────────────────────────────────
            // Each student rates a student supervisor on 5 criteria (/10 each = /50 max)
            // Criteria:
            //   1. Self-discipline in behavior and timing
            //   2. Dealing with colleagues
            //   3. Sense of responsibility
            //   4. Decision making ability
            //   5. Problem solving ability
            "CREATE TABLE {$p}rsyi_peer_evaluations (
                id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                period_id       BIGINT UNSIGNED NOT NULL,
                evaluator_id    BIGINT UNSIGNED NOT NULL,
                evaluatee_id    BIGINT UNSIGNED NOT NULL,
                criterion_1     TINYINT UNSIGNED NOT NULL DEFAULT 0,
                criterion_2     TINYINT UNSIGNED NOT NULL DEFAULT 0,
                criterion_3     TINYINT UNSIGNED NOT NULL DEFAULT 0,
                criterion_4     TINYINT UNSIGNED NOT NULL DEFAULT 0,
                criterion_5     TINYINT UNSIGNED NOT NULL DEFAULT 0,
                total           TINYINT UNSIGNED NOT NULL DEFAULT 0,
                notes           TEXT            DEFAULT NULL,
                created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_peer_eval (period_id, evaluator_id, evaluatee_id),
                KEY idx_peer_eval_period    (period_id),
                KEY idx_peer_eval_evaluatee (evaluatee_id)
            ) $charset;",

            // ── Admin/Supervisor Evaluations ─────────────────────────
            // Student Affairs Manager or Dorm Supervisor rates a student supervisor
            // on 6 criteria (/10 each = /60 max)
            // Criteria:
            //   1. Self-discipline in behavior and timing
            //   2. Dealing with colleagues
            //   3. Sense of responsibility
            //   4. Decision making – understanding and comprehending the mission
            //   5. Problem solving – confidentiality in information transfer
            //   6. Good use of authority/privileges
            "CREATE TABLE {$p}rsyi_admin_evaluations (
                id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                period_id       BIGINT UNSIGNED NOT NULL,
                evaluatee_id    BIGINT UNSIGNED NOT NULL,
                evaluator_id    BIGINT UNSIGNED NOT NULL,
                evaluator_role  VARCHAR(60)     NOT NULL,
                criterion_1     TINYINT UNSIGNED NOT NULL DEFAULT 0,
                criterion_2     TINYINT UNSIGNED NOT NULL DEFAULT 0,
                criterion_3     TINYINT UNSIGNED NOT NULL DEFAULT 0,
                criterion_4     TINYINT UNSIGNED NOT NULL DEFAULT 0,
                criterion_5     TINYINT UNSIGNED NOT NULL DEFAULT 0,
                criterion_6     TINYINT UNSIGNED NOT NULL DEFAULT 0,
                total           TINYINT UNSIGNED NOT NULL DEFAULT 0,
                notes           TEXT            DEFAULT NULL,
                created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_admin_eval (period_id, evaluatee_id, evaluator_id),
                KEY idx_admin_eval_period    (period_id),
                KEY idx_admin_eval_evaluatee (evaluatee_id),
                KEY idx_admin_eval_role      (evaluator_role)
            ) $charset;",

            // ── Attendance ───────────────────────────────────────────
            "CREATE TABLE {$p}rsyi_attendance (
                id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                student_id   BIGINT UNSIGNED NOT NULL,
                cohort_id    BIGINT UNSIGNED NOT NULL,
                session_date DATE            NOT NULL,
                session_name VARCHAR(120)    DEFAULT NULL,
                status       VARCHAR(20)     NOT NULL DEFAULT 'present',
                notes        TEXT            DEFAULT NULL,
                recorded_by  BIGINT UNSIGNED NOT NULL,
                created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_attendance (student_id, session_date, session_name(50)),
                KEY idx_att_student (student_id),
                KEY idx_att_cohort  (cohort_id),
                KEY idx_att_date    (session_date)
            ) $charset;",

            // ── Study Materials ──────────────────────────────────────
            "CREATE TABLE {$p}rsyi_study_materials (
                id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                cohort_id     BIGINT UNSIGNED DEFAULT NULL,
                title         VARCHAR(255)    NOT NULL,
                description   TEXT            DEFAULT NULL,
                file_path     VARCHAR(500)    NOT NULL,
                file_name_orig VARCHAR(255)   NOT NULL,
                file_size     INT UNSIGNED    DEFAULT NULL,
                mime_type     VARCHAR(100)    DEFAULT NULL,
                subject       VARCHAR(120)    DEFAULT NULL,
                uploaded_by   BIGINT UNSIGNED NOT NULL,
                is_active     TINYINT(1)      NOT NULL DEFAULT 1,
                created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_mat_cohort   (cohort_id),
                KEY idx_mat_uploader (uploaded_by),
                KEY idx_mat_active   (is_active)
            ) $charset;",

            // ── Exams ────────────────────────────────────────────────
            "CREATE TABLE {$p}rsyi_exams (
                id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                cohort_id    BIGINT UNSIGNED DEFAULT NULL,
                title        VARCHAR(255)    NOT NULL,
                description  TEXT            DEFAULT NULL,
                subject      VARCHAR(120)    DEFAULT NULL,
                exam_date    DATE            DEFAULT NULL,
                duration_min SMALLINT UNSIGNED DEFAULT NULL,
                max_score    SMALLINT UNSIGNED NOT NULL DEFAULT 100,
                is_active    TINYINT(1)      NOT NULL DEFAULT 1,
                created_by   BIGINT UNSIGNED NOT NULL,
                created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_exam_cohort  (cohort_id),
                KEY idx_exam_date    (exam_date),
                KEY idx_exam_active  (is_active)
            ) $charset;",

            // ── Exam Results ─────────────────────────────────────────
            "CREATE TABLE {$p}rsyi_exam_results (
                id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                exam_id     BIGINT UNSIGNED NOT NULL,
                student_id  BIGINT UNSIGNED NOT NULL,
                score       SMALLINT UNSIGNED DEFAULT NULL,
                grade       VARCHAR(10)     DEFAULT NULL,
                notes       TEXT            DEFAULT NULL,
                recorded_by BIGINT UNSIGNED NOT NULL,
                created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_exam_result (exam_id, student_id),
                KEY idx_result_exam    (exam_id),
                KEY idx_result_student (student_id)
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
     * Create all student portal pages automatically on activation.
     *
     * Pages created (skipped if slug already exists):
     *   - student-register   → [rsyi_portal_register]
     *   - student-dashboard  → [rsyi_portal_dashboard]
     *   - student-documents  → [rsyi_portal_documents]
     *   - student-requests   → [rsyi_portal_requests]
     *   - student-behavior   → [rsyi_portal_behavior]
     *   - student-evaluation → [rsyi_portal_evaluation]
     *
     * Page IDs are stored in options so the plugin can link to them.
     */
    public static function create_portal_pages(): void {
        $pages = [
            [
                'slug'      => 'student-register',
                'title'     => 'Student Registration',
                'shortcode' => '[rsyi_portal_register]',
                'option'    => 'rsyi_page_register',
            ],
            [
                'slug'      => 'student-dashboard',
                'title'     => 'Student Dashboard',
                'shortcode' => '[rsyi_portal_dashboard]',
                'option'    => 'rsyi_page_dashboard',
            ],
            [
                'slug'      => 'student-documents',
                'title'     => 'My Documents',
                'shortcode' => '[rsyi_portal_documents]',
                'option'    => 'rsyi_page_documents',
            ],
            [
                'slug'      => 'student-requests',
                'title'     => 'My Permits & Requests',
                'shortcode' => '[rsyi_portal_requests]',
                'option'    => 'rsyi_page_requests',
            ],
            [
                'slug'      => 'student-behavior',
                'title'     => 'My Behavior Record',
                'shortcode' => '[rsyi_portal_behavior]',
                'option'    => 'rsyi_page_behavior',
            ],
            [
                'slug'      => 'student-evaluation',
                'title'     => 'Cohort Peer Evaluation',
                'shortcode' => '[rsyi_portal_evaluation]',
                'option'    => 'rsyi_page_evaluation',
            ],
        ];

        foreach ( $pages as $page ) {
            // Check if a page with this slug already exists
            $existing = get_page_by_path( $page['slug'], OBJECT, 'page' );

            if ( $existing ) {
                // Store the ID even if it already existed
                update_option( $page['option'], $existing->ID );
                continue;
            }

            $page_id = wp_insert_post( [
                'post_title'   => $page['title'],
                'post_name'    => $page['slug'],
                'post_content' => $page['shortcode'],
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_author'  => 1,
            ] );

            if ( $page_id && ! is_wp_error( $page_id ) ) {
                update_option( $page['option'], $page_id );
            }
        }

        // Set the register page as the WordPress login page redirect for students
        $register_id = get_option( 'rsyi_page_register' );
        if ( $register_id ) {
            update_option( 'rsyi_register_page_url', get_permalink( $register_id ) );
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
