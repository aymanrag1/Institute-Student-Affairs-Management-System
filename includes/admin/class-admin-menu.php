<?php
/**
 * Admin Menu – registers all WP Admin menu pages for RSYI Student Affairs.
 *
 * Menu structure:
 *   🎓 Student Affairs (top-level)
 *   ├── Dashboard
 *   ├── Students
 *   ├── Documents
 *   ├── Exit Permits
 *   ├── Overnight Permits
 *   ├── Violations
 *   ├── Expulsion Cases
 *   ├── Cohorts & Transfers
 *   ├── Daily Report PDF
 *   └── Audit Log
 *
 * @package RSYI_StudentAffairs
 */

namespace RSYI_SA\Admin;

defined( 'ABSPATH' ) || exit;

class Menu {

    public static function init(): void {
        add_action( 'admin_menu', [ __CLASS__, 'register_menus' ] );
        add_action( 'admin_init', [ __CLASS__, 'handle_form_submissions' ] );
        add_action( 'wp_ajax_rsyi_save_settings',           [ __CLASS__, 'ajax_save_settings' ] );
        add_action( 'wp_ajax_rsyi_reseed_violation_types',  [ __CLASS__, 'ajax_reseed_violation_types' ] );
        add_action( 'wp_ajax_rsyi_force_update_check',      [ __CLASS__, 'ajax_force_update_check' ] );
        add_action( 'wp_ajax_rsyi_create_portal_pages',     [ __CLASS__, 'ajax_create_portal_pages' ] );
        add_action( 'wp_ajax_rsyi_save_role_caps',          [ __CLASS__, 'ajax_save_role_caps' ] );
        add_action( 'wp_ajax_rsyi_get_period_students',     [ __CLASS__, 'ajax_get_period_students' ] );
        add_action( 'wp_ajax_rsyi_save_attendance',         [ __CLASS__, 'ajax_save_attendance' ] );
        add_action( 'wp_ajax_rsyi_upload_material',         [ __CLASS__, 'ajax_upload_material' ] );
        add_action( 'wp_ajax_rsyi_create_exam',             [ __CLASS__, 'ajax_create_exam' ] );
        add_action( 'wp_ajax_rsyi_save_exam_results',       [ __CLASS__, 'ajax_save_exam_results' ] );
        add_action( 'wp_ajax_rsyi_delete_exam',             [ __CLASS__, 'ajax_delete_exam' ] );
        add_action( 'wp_ajax_rsyi_update_exam',             [ __CLASS__, 'ajax_update_exam' ] );
        add_action( 'wp_ajax_rsyi_get_exam_stats',          [ __CLASS__, 'ajax_get_exam_stats' ] );
        add_action( 'wp_ajax_rsyi_export_exam_results',     [ __CLASS__, 'ajax_export_exam_results' ] );
        add_action( 'wp_ajax_rsyi_delete_material',         [ __CLASS__, 'ajax_delete_material' ] );
        add_action( 'wp_ajax_rsyi_get_questions',           [ __CLASS__, 'ajax_get_questions' ] );
        add_action( 'wp_ajax_rsyi_save_question',           [ __CLASS__, 'ajax_save_question' ] );
        add_action( 'wp_ajax_rsyi_delete_question',         [ __CLASS__, 'ajax_delete_question' ] );
        add_action( 'wp_ajax_rsyi_auto_grade_exam',         [ __CLASS__, 'ajax_auto_grade_exam' ] );
        add_action( 'wp_ajax_rsyi_regrade_result',          [ __CLASS__, 'ajax_regrade_result' ] );
        // Student-facing: submit exam answers (logged-in only)
        add_action( 'wp_ajax_rsyi_submit_exam',             [ __CLASS__, 'ajax_submit_exam' ] );
        add_action( 'wp_ajax_rsyi_get_exam_for_student',    [ __CLASS__, 'ajax_get_exam_for_student' ] );
    }

    public static function register_menus(): void {
        $icon = 'dashicons-welcome-learn-more';

        add_menu_page(
            __( 'Student Affairs – RSYI', 'rsyi-sa' ),
            __( 'Student Affairs', 'rsyi-sa' ),
            'rsyi_view_all_students',
            'rsyi-dashboard',
            [ __CLASS__, 'page_dashboard' ],
            $icon,
            30
        );

        $subpages = [
            [ 'rsyi-dashboard',    __( 'Dashboard', 'rsyi-sa' ),           'rsyi_view_all_students',    [ __CLASS__, 'page_dashboard' ] ],
            [ 'rsyi-students',     __( 'Students', 'rsyi-sa' ),            'rsyi_view_all_students',    [ __CLASS__, 'page_students' ] ],
            [ 'rsyi-documents',    __( 'Documents', 'rsyi-sa' ),           'rsyi_view_all_documents',   [ __CLASS__, 'page_documents' ] ],
            [ 'rsyi-attendance',   __( 'Attendance', 'rsyi-sa' ),          'rsyi_manage_attendance',    [ __CLASS__, 'page_attendance' ] ],
            [ 'rsyi-materials',    __( 'Study Materials', 'rsyi-sa' ),     'rsyi_upload_study_materials', [ __CLASS__, 'page_materials' ] ],
            [ 'rsyi-exams',        __( 'Exams', 'rsyi-sa' ),               'rsyi_manage_exams',         [ __CLASS__, 'page_exams' ] ],
            [ 'rsyi-grade-report', __( 'Grade Report', 'rsyi-sa' ),        'rsyi_view_exam_stats',      [ __CLASS__, 'page_grade_report' ] ],
            [ 'rsyi-exit',         __( 'Exit Permits', 'rsyi-sa' ),        'rsyi_view_all_requests',    [ __CLASS__, 'page_exit_permits' ] ],
            [ 'rsyi-overnight',    __( 'Overnight Permits', 'rsyi-sa' ),   'rsyi_view_all_requests',    [ __CLASS__, 'page_overnight_permits' ] ],
            [ 'rsyi-violations',   __( 'Violations', 'rsyi-sa' ),          'rsyi_view_all_violations',  [ __CLASS__, 'page_violations' ] ],
            [ 'rsyi-expulsion',    __( 'Expulsion Cases', 'rsyi-sa' ),     'rsyi_manage_expulsion',     [ __CLASS__, 'page_expulsion' ] ],
            [ 'rsyi-cohorts',      __( 'Cohorts', 'rsyi-sa' ),             'rsyi_manage_cohorts',       [ __CLASS__, 'page_cohorts' ] ],
            [ 'rsyi-evaluations',  __( 'Evaluations', 'rsyi-sa' ),         'rsyi_view_evaluations',     [ __CLASS__, 'page_evaluations' ] ],
            [ 'rsyi-daily-report', __( 'Daily Report PDF', 'rsyi-sa' ),    'rsyi_print_daily_report',   [ __CLASS__, 'page_daily_report' ] ],
            [ 'rsyi-audit',        __( 'Audit Log', 'rsyi-sa' ),           'rsyi_view_audit_log',       [ __CLASS__, 'page_audit_log' ] ],
            [ 'rsyi-roles',        __( 'Roles & Permissions', 'rsyi-sa' ), 'rsyi_manage_roles',         [ __CLASS__, 'page_roles' ] ],
            [ 'rsyi-settings',     __( 'Settings', 'rsyi-sa' ),            'rsyi_manage_settings',      [ __CLASS__, 'page_settings' ] ],
        ];

        foreach ( $subpages as $sub ) {
            add_submenu_page(
                'rsyi-dashboard',
                $sub[1],
                $sub[1],
                $sub[2],
                $sub[0],
                $sub[3]
            );
        }
    }

    // ── Page renderers ────────────────────────────────────────────────────────

    public static function page_dashboard(): void {
        self::render( 'dashboard' );
    }

    public static function page_students(): void {
        // Handle single student view
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'view' && ! empty( $_GET['id'] ) ) {
            self::render( 'student-detail', [ 'student_id' => (int) $_GET['id'] ] );
            return;
        }
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'add' ) {
            self::render( 'student-add' );
            return;
        }
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'import' ) {
            self::render( 'students-import' );
            return;
        }
        self::render( 'students-list' );
    }

    public static function page_documents(): void {
        if ( ! empty( $_GET['student_id'] ) ) {
            self::render( 'documents-student', [ 'student_id' => (int) $_GET['student_id'] ] );
            return;
        }
        self::render( 'documents-list' );
    }

    public static function page_exit_permits(): void {
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'view' && ! empty( $_GET['id'] ) ) {
            self::render( 'exit-permit-detail', [ 'permit_id' => (int) $_GET['id'] ] );
            return;
        }
        self::render( 'exit-permits-list' );
    }

    public static function page_overnight_permits(): void {
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'view' && ! empty( $_GET['id'] ) ) {
            self::render( 'overnight-permit-detail', [ 'permit_id' => (int) $_GET['id'] ] );
            return;
        }
        self::render( 'overnight-permits-list' );
    }

    public static function page_violations(): void {
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'add' ) {
            self::render( 'violation-add' );
            return;
        }
        self::render( 'violations-list' );
    }

    public static function page_expulsion(): void {
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'view' && ! empty( $_GET['id'] ) ) {
            self::render( 'expulsion-detail', [ 'case_id' => (int) $_GET['id'] ] );
            return;
        }
        self::render( 'expulsion-list' );
    }

    public static function page_cohorts(): void {
        $tab = sanitize_key( $_GET['tab'] ?? 'cohorts' );
        self::render( 'cohorts', [ 'tab' => $tab ] );
    }

    public static function page_evaluations(): void {
        $tab = sanitize_key( $_GET['tab'] ?? 'aggregation' );
        self::render( 'evaluations', [ 'tab' => $tab ] );
    }

    public static function page_attendance(): void {
        self::render( 'attendance' );
    }

    public static function page_materials(): void {
        self::render( 'study-materials' );
    }

    public static function page_exams(): void {
        self::render( 'exams' );
    }

    public static function page_grade_report(): void {
        self::render( 'grade-report' );
    }

    public static function page_roles(): void {
        self::render( 'roles' );
    }

    public static function page_daily_report(): void {
        self::render( 'daily-report' );
    }

    public static function page_audit_log(): void {
        self::render( 'audit-log' );
    }

    public static function page_settings(): void {
        self::render( 'settings' );
    }

    // ── Template loader ───────────────────────────────────────────────────────

    private static function render( string $template, array $vars = [] ): void {
        $file = RSYI_SA_PLUGIN_DIR . 'templates/admin/' . $template . '.php';
        if ( ! file_exists( $file ) ) {
            echo '<div class="notice notice-error"><p>' . esc_html( "Template missing: {$template}" ) . '</p></div>';
            return;
        }
        echo '<div class="wrap rsyi-admin-wrap" dir="ltr">';
        extract( $vars, EXTR_SKIP ); // phpcs:ignore
        include $file;
        echo '</div>';
    }

    // ── Handle non-AJAX form POSTs (fallback) ─────────────────────────────────

    public static function handle_form_submissions(): void {
        if ( ! isset( $_POST['rsyi_action'] ) ) return;
        // All primary actions use AJAX; this is a safety net for progressive-enhancement forms.
        check_admin_referer( 'rsyi_sa_admin_form' );
    }

    // ── Settings AJAX ──────────────────────────────────────────────────────────

    public static function ajax_save_settings(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );

        if ( ! current_user_can( 'rsyi_manage_settings' ) ) {
            wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rsyi-sa' ) ] );
        }

        $institute_name = sanitize_text_field( wp_unslash( $_POST['rsyi_institute_name'] ?? '' ) );

        if ( empty( $institute_name ) ) {
            wp_send_json_error( [ 'message' => __( 'Institute name cannot be empty.', 'rsyi-sa' ) ] );
        }

        update_option( 'rsyi_institute_name', $institute_name );

        $dean_name = sanitize_text_field( wp_unslash( $_POST['rsyi_dean_name'] ?? '' ) );
        update_option( 'rsyi_dean_name', $dean_name );

        $logo_url = esc_url_raw( wp_unslash( $_POST['rsyi_logo_url'] ?? '' ) );
        update_option( 'rsyi_logo_url', $logo_url );

        $logo_id = absint( $_POST['rsyi_logo_attachment_id'] ?? 0 );
        update_option( 'rsyi_logo_attachment_id', $logo_id );

        // GitHub token: '__KEEP__' means "don't change the stored value"
        $github_token = wp_unslash( $_POST['rsyi_github_token'] ?? '' );
        if ( $github_token !== '__KEEP__' ) {
            update_option( 'rsyi_github_token', sanitize_text_field( $github_token ) );
            // Clear cached release data so it re-fetches with the new token
            delete_transient( 'rsyi_sa_update_cache' );
        }

        wp_send_json_success( [ 'message' => __( 'Settings saved successfully.', 'rsyi-sa' ) ] );
    }

    public static function ajax_force_update_check(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );

        if ( ! current_user_can( 'rsyi_manage_settings' ) ) {
            wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rsyi-sa' ) ] );
        }

        // Direct API call – no transient dependency, returns detailed diagnostics.
        $result = \RSYI_SA\Updater::check_connection();

        if ( ! $result['success'] ) {
            wp_send_json_error( [
                'message'    => $result['error'],
                'error_type' => $result['error_type'] ?? 'unknown',
                'http_code'  => $result['http_code'] ?? 0,
            ] );
        }

        // Cache is already refreshed inside check_connection(); also clear the
        // WP plugin-update transient so the dashboard notice updates on next page load.
        delete_site_transient( 'update_plugins' );

        $latest_version = $result['latest_version'];

        if ( version_compare( RSYI_SA_VERSION, $latest_version, '<' ) ) {
            wp_send_json_success( [
                'message' => sprintf(
                    /* translators: %s: latest version number */
                    __( 'New update available: version %s. Go to the Plugins page to update.', 'rsyi-sa' ),
                    esc_html( $latest_version )
                ),
            ] );
        } else {
            wp_send_json_success( [
                'message' => sprintf(
                    /* translators: %s: current version number */
                    __( 'System is up to date. Current version %s is the latest.', 'rsyi-sa' ),
                    esc_html( RSYI_SA_VERSION )
                ),
            ] );
        }
    }

    public static function ajax_create_portal_pages(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );

        if ( ! current_user_can( 'rsyi_manage_settings' ) ) {
            wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rsyi-sa' ) ] );
        }

        \RSYI_SA\DB_Installer::create_portal_pages();

        wp_send_json_success( [
            'message' => __( 'Portal pages created successfully. The page list has been updated.', 'rsyi-sa' ),
        ] );
    }

    /**
     * Save role capability changes from the Roles & Permissions screen.
     * AJAX endpoint: rsyi_save_role_caps
     */
    public static function ajax_save_role_caps(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );

        if ( ! current_user_can( 'rsyi_manage_roles' ) ) {
            wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rsyi-sa' ) ] );
        }

        $role_slug = sanitize_key( $_POST['role_slug'] ?? '' );
        if ( empty( $role_slug ) ) {
            wp_send_json_error( [ 'message' => __( 'Role slug is required.', 'rsyi-sa' ) ] );
        }

        $role = get_role( $role_slug );
        if ( ! $role ) {
            wp_send_json_error( [ 'message' => __( 'Role not found.', 'rsyi-sa' ) ] );
        }

        // Prevent editing the student role's core self-service caps via this screen
        $protected_roles = [ 'administrator' ];
        if ( in_array( $role_slug, $protected_roles, true ) ) {
            wp_send_json_error( [ 'message' => __( 'This role cannot be modified here.', 'rsyi-sa' ) ] );
        }

        // All known RSYI capabilities
        $all_caps = \RSYI_SA\Roles::get_all_caps();

        // The submitted caps are those checked in the form (true = enabled)
        $submitted_caps = (array) ( $_POST['caps'] ?? [] );

        foreach ( $all_caps as $cap ) {
            if ( in_array( $cap, $submitted_caps, true ) ) {
                $role->add_cap( $cap, true );
            } else {
                $role->remove_cap( $cap );
            }
        }

        \RSYI_SA\Audit_Log::log( 'role', 0, 'update_caps', [
            'role'  => $role_slug,
            'caps'  => $submitted_caps,
        ] );

        wp_send_json_success( [ 'message' => sprintf( __( 'Saved permissions for "%s".', 'rsyi-sa' ), $role_slug ) ] );
    }

    /**
     * Return students in a given evaluation period's cohort (for dynamic dropdown).
     * AJAX endpoint: rsyi_get_period_students
     */
    public static function ajax_get_period_students(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );

        if ( ! current_user_can( 'rsyi_submit_admin_evaluation' ) ) {
            wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rsyi-sa' ) ] );
        }

        $period_id = absint( $_POST['period_id'] ?? 0 );
        if ( ! $period_id ) {
            wp_send_json_error( [ 'message' => __( 'Invalid period.', 'rsyi-sa' ) ] );
        }

        global $wpdb;
        $period = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rsyi_evaluation_periods WHERE id = %d",
            $period_id
        ) );

        if ( ! $period ) {
            wp_send_json_error( [ 'message' => __( 'Period not found.', 'rsyi-sa' ) ] );
        }

        $sp = $wpdb->prefix . 'rsyi_student_profiles';
        $students = $wpdb->get_results( $wpdb->prepare(
            "SELECT sp.user_id, sp.english_full_name
             FROM {$sp} sp
             WHERE sp.cohort_id = %d AND sp.status = 'active'
             ORDER BY sp.english_full_name ASC",
            (int) $period->cohort_id
        ) );

        wp_send_json_success( [ 'students' => $students ] );
    }

    // ── Attendance AJAX ────────────────────────────────────────────────────────

    public static function ajax_save_attendance(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );

        if ( ! current_user_can( 'rsyi_manage_attendance' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        $cohort_id    = absint( $_POST['cohort_id']    ?? 0 );
        $session_date = sanitize_text_field( wp_unslash( $_POST['session_date'] ?? '' ) );
        $student_ids  = array_map( 'absint', (array) ( $_POST['students'] ?? [] ) );

        if ( ! $cohort_id || ! $session_date || empty( $student_ids ) ) {
            wp_send_json_error( [ 'message' => __( 'بيانات غير مكتملة.', 'rsyi-sa' ) ] );
        }

        global $wpdb;
        $table      = $wpdb->prefix . 'rsyi_attendance';
        $current_by = get_current_user_id();
        $saved      = 0;

        foreach ( $student_ids as $profile_id ) {
            $status = sanitize_key( $_POST[ "status_{$profile_id}" ] ?? 'present' );
            $notes  = sanitize_text_field( wp_unslash( $_POST[ "notes_{$profile_id}" ] ?? '' ) );

            // Upsert: update if exists, insert if not
            $existing = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$table} WHERE student_id = %d AND cohort_id = %d AND session_date = %s LIMIT 1",
                $profile_id, $cohort_id, $session_date
            ) );

            if ( $existing ) {
                $wpdb->update(
                    $table,
                    [ 'status' => $status, 'notes' => $notes, 'recorded_by' => $current_by ],
                    [ 'id' => $existing ]
                );
            } else {
                $wpdb->insert( $table, [
                    'student_id'   => $profile_id,
                    'cohort_id'    => $cohort_id,
                    'session_date' => $session_date,
                    'status'       => $status,
                    'notes'        => $notes,
                    'recorded_by'  => $current_by,
                ] );
            }
            $saved++;
        }

        wp_send_json_success( [
            'message' => sprintf( __( 'تم حفظ حضور %d طالب.', 'rsyi-sa' ), $saved ),
        ] );
    }

    // ── Study Materials AJAX ───────────────────────────────────────────────────

    public static function ajax_upload_material(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );

        if ( ! current_user_can( 'rsyi_upload_study_materials' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        $title     = sanitize_text_field( wp_unslash( $_POST['title']     ?? '' ) );
        $subject   = sanitize_text_field( wp_unslash( $_POST['subject']   ?? '' ) );
        $cohort_id = absint( $_POST['cohort_id'] ?? 0 );
        $desc      = sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) );

        if ( empty( $title ) ) {
            wp_send_json_error( [ 'message' => __( 'عنوان المادة مطلوب.', 'rsyi-sa' ) ] );
        }

        if ( empty( $_FILES['material_file'] ) || $_FILES['material_file']['error'] !== UPLOAD_ERR_OK ) {
            wp_send_json_error( [ 'message' => __( 'فشل رفع الملف.', 'rsyi-sa' ) ] );
        }

        $file      = $_FILES['material_file'];
        $allowed   = [ 'application/pdf', 'application/msword',
                       'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                       'application/vnd.ms-powerpoint',
                       'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                       'application/vnd.ms-excel',
                       'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                       'application/zip', 'application/x-zip-compressed' ];
        $mime      = mime_content_type( $file['tmp_name'] );
        $max_size  = 20 * 1024 * 1024; // 20 MB

        if ( ! in_array( $mime, $allowed, true ) ) {
            wp_send_json_error( [ 'message' => __( 'نوع الملف غير مسموح.', 'rsyi-sa' ) ] );
        }
        if ( $file['size'] > $max_size ) {
            wp_send_json_error( [ 'message' => __( 'حجم الملف يتجاوز 20 ميجابايت.', 'rsyi-sa' ) ] );
        }

        // Save to protected uploads directory
        $upload_dir = RSYI_SA_UPLOAD_DIR . '/materials';
        if ( ! is_dir( $upload_dir ) ) {
            wp_mkdir_p( $upload_dir );
        }
        $filename    = wp_unique_filename( $upload_dir, sanitize_file_name( $file['name'] ) );
        $destination = $upload_dir . '/' . $filename;

        if ( ! move_uploaded_file( $file['tmp_name'], $destination ) ) {
            wp_send_json_error( [ 'message' => __( 'فشل حفظ الملف.', 'rsyi-sa' ) ] );
        }

        global $wpdb;
        $wpdb->insert( $wpdb->prefix . 'rsyi_study_materials', [
            'cohort_id'     => $cohort_id ?: null,
            'title'         => $title,
            'description'   => $desc,
            'file_path'     => 'materials/' . $filename,
            'file_name_orig'=> $file['name'],
            'file_size'     => $file['size'],
            'mime_type'     => $mime,
            'subject'       => $subject ?: null,
            'uploaded_by'   => get_current_user_id(),
            'is_active'     => 1,
        ] );

        \RSYI_SA\Audit_Log::log( 'study_material', $wpdb->insert_id, 'upload', [
            'title'     => $title,
            'cohort_id' => $cohort_id,
        ] );

        wp_send_json_success( [ 'message' => __( 'تم رفع المادة بنجاح.', 'rsyi-sa' ) ] );
    }

    // ── Exams AJAX ─────────────────────────────────────────────────────────────

    public static function ajax_create_exam(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );

        if ( ! current_user_can( 'rsyi_manage_exams' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        $title         = sanitize_text_field( wp_unslash( $_POST['title']       ?? '' ) );
        $subject       = sanitize_text_field( wp_unslash( $_POST['subject']     ?? '' ) );
        $cohort_id     = absint( $_POST['cohort_id'] ?? 0 );
        $starts_at_raw = sanitize_text_field( wp_unslash( $_POST['starts_at']   ?? '' ) );
        $ends_at_raw   = sanitize_text_field( wp_unslash( $_POST['ends_at']     ?? '' ) );
        $duration_min  = absint( $_POST['duration_min'] ?? 0 );
        $max_score     = absint( $_POST['max_score']    ?? 100 );
        $passing_score = ( isset( $_POST['passing_score'] ) && $_POST['passing_score'] !== '' ) ? absint( $_POST['passing_score'] ) : null;
        $exam_type     = sanitize_key( $_POST['exam_type'] ?? 'written' );
        $status        = sanitize_key( $_POST['status']    ?? 'published' );
        $desc          = sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) );
        $show_results  = isset( $_POST['show_results'] ) ? 1 : 0;
        $auto_grade    = isset( $_POST['auto_grade'] )   ? 1 : 0;
        $allow_regrade = isset( $_POST['allow_regrade'] ) ? 1 : 0;

        // Convert datetime-local format (2024-03-15T09:00) to MySQL format
        $starts_at = $starts_at_raw ? str_replace( 'T', ' ', $starts_at_raw ) . ':00' : null;
        $ends_at   = $ends_at_raw   ? str_replace( 'T', ' ', $ends_at_raw   ) . ':00' : null;

        if ( empty( $title ) ) {
            wp_send_json_error( [ 'message' => __( 'عنوان الامتحان مطلوب.', 'rsyi-sa' ) ] );
        }

        global $wpdb;
        $inserted = $wpdb->insert( $wpdb->prefix . 'rsyi_exams', [
            'cohort_id'     => $cohort_id ?: null,
            'title'         => $title,
            'description'   => $desc,
            'subject'       => $subject ?: null,
            'starts_at'     => $starts_at,
            'ends_at'       => $ends_at,
            'duration_min'  => $duration_min ?: null,
            'max_score'     => $max_score ?: 100,
            'passing_score' => $passing_score,
            'exam_type'     => $exam_type,
            'status'        => $status,
            'show_results'  => $show_results,
            'auto_grade'    => $auto_grade,
            'allow_regrade' => $allow_regrade,
            'is_active'     => 1,
            'created_by'    => get_current_user_id(),
        ] );

        if ( ! $inserted ) {
            wp_send_json_error( [ 'message' => __( 'فشل في إنشاء الامتحان. يرجى المحاولة مرة أخرى.', 'rsyi-sa' ) ] );
        }

        $exam_id = (int) $wpdb->insert_id;

        \RSYI_SA\Audit_Log::log( 'exam', $exam_id, 'create', [
            'title'     => $title,
            'cohort_id' => $cohort_id,
        ] );

        wp_send_json_success( [
            'message'  => __( 'تم إنشاء الامتحان بنجاح.', 'rsyi-sa' ),
            'exam_id'  => $exam_id,
        ] );
    }

    public static function ajax_save_exam_results(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );

        if ( ! current_user_can( 'rsyi_manage_exams' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        $exam_id     = absint( $_POST['exam_id']    ?? 0 );
        $student_ids = array_map( 'absint', (array) ( $_POST['student_ids'] ?? [] ) );

        if ( ! $exam_id || empty( $student_ids ) ) {
            wp_send_json_error( [ 'message' => __( 'بيانات غير مكتملة.', 'rsyi-sa' ) ] );
        }

        global $wpdb;
        $table      = $wpdb->prefix . 'rsyi_exam_results';
        $current_by = get_current_user_id();
        $saved      = 0;

        // Get passing threshold for this exam
        $exam = $wpdb->get_row( $wpdb->prepare(
            "SELECT max_score, passing_score FROM {$wpdb->prefix}rsyi_exams WHERE id = %d",
            $exam_id
        ) );
        $max_score    = $exam ? (int) $exam->max_score : 100;
        $passing_thrs = ( $exam && $exam->passing_score !== null )
                        ? (int) $exam->passing_score
                        : (int) round( $max_score * 0.5 );

        foreach ( $student_ids as $student_id ) {
            $score_raw = $_POST[ "score_{$student_id}" ] ?? '';
            if ( $score_raw === '' ) continue; // Skip empty entries

            $score = absint( $score_raw );
            $grade = sanitize_text_field( wp_unslash( $_POST[ "grade_{$student_id}" ] ?? '' ) );
            $notes = sanitize_text_field( wp_unslash( $_POST[ "notes_{$student_id}" ] ?? '' ) );

            // Auto-calculate grade letter if empty
            if ( empty( $grade ) ) {
                $pct   = $max_score > 0 ? $score / $max_score * 100 : 0;
                $grade = $pct >= 90 ? 'A+' : ( $pct >= 80 ? 'A' : ( $pct >= 70 ? 'B' : ( $pct >= 60 ? 'C' : ( $pct >= 50 ? 'D' : 'F' ) ) ) );
            }
            $is_passing = $score >= $passing_thrs ? 1 : 0;

            $existing = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$table} WHERE exam_id = %d AND student_id = %d LIMIT 1",
                $exam_id, $student_id
            ) );

            if ( $existing ) {
                $wpdb->update(
                    $table,
                    [ 'score' => $score, 'grade' => $grade, 'notes' => $notes, 'is_passing' => $is_passing, 'recorded_by' => $current_by ],
                    [ 'id' => $existing ]
                );
            } else {
                $wpdb->insert( $table, [
                    'exam_id'     => $exam_id,
                    'student_id'  => $student_id,
                    'score'       => $score,
                    'grade'       => $grade,
                    'notes'       => $notes,
                    'is_passing'  => $is_passing,
                    'recorded_by' => $current_by,
                ] );
            }
            $saved++;
        }

        wp_send_json_success( [
            'message' => sprintf( __( 'تم حفظ نتائج %d طالب.', 'rsyi-sa' ), $saved ),
        ] );
    }

    // ── Exam Management AJAX ───────────────────────────────────────────────────

    public static function ajax_delete_exam(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );

        if ( ! current_user_can( 'rsyi_delete_exam' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        $exam_id = absint( $_POST['exam_id'] ?? 0 );
        if ( ! $exam_id ) {
            wp_send_json_error( [ 'message' => __( 'معرف الامتحان مطلوب.', 'rsyi-sa' ) ] );
        }

        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'rsyi_exam_answers',   [ 'exam_id' => $exam_id ] );
        $wpdb->delete( $wpdb->prefix . 'rsyi_exam_results',   [ 'exam_id' => $exam_id ] );
        $wpdb->delete( $wpdb->prefix . 'rsyi_exam_questions', [ 'exam_id' => $exam_id ] );
        $wpdb->delete( $wpdb->prefix . 'rsyi_exams',          [ 'id'      => $exam_id ] );

        \RSYI_SA\Audit_Log::log( 'exam', $exam_id, 'delete', [] );

        wp_send_json_success( [ 'message' => __( 'تم حذف الامتحان ونتائجه.', 'rsyi-sa' ) ] );
    }

    public static function ajax_update_exam(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );

        if ( ! current_user_can( 'rsyi_edit_exam' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        $exam_id = absint( $_POST['exam_id'] ?? 0 );
        if ( ! $exam_id ) {
            wp_send_json_error( [ 'message' => __( 'معرف الامتحان مطلوب.', 'rsyi-sa' ) ] );
        }

        $title         = sanitize_text_field( wp_unslash( $_POST['title']       ?? '' ) );
        $subject       = sanitize_text_field( wp_unslash( $_POST['subject']     ?? '' ) );
        $cohort_id     = absint( $_POST['cohort_id'] ?? 0 );
        $starts_at_raw = sanitize_text_field( wp_unslash( $_POST['starts_at']   ?? '' ) );
        $ends_at_raw   = sanitize_text_field( wp_unslash( $_POST['ends_at']     ?? '' ) );
        $duration_min  = absint( $_POST['duration_min'] ?? 0 );
        $max_score     = absint( $_POST['max_score']    ?? 100 );
        $passing_score = ( isset( $_POST['passing_score'] ) && $_POST['passing_score'] !== '' ) ? absint( $_POST['passing_score'] ) : null;
        $exam_type     = sanitize_key( $_POST['exam_type']  ?? 'written' );
        $status        = sanitize_key( $_POST['status']     ?? 'published' );
        $desc          = sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) );
        $show_results  = isset( $_POST['show_results'] ) ? 1 : 0;
        $auto_grade    = isset( $_POST['auto_grade'] )   ? 1 : 0;
        $allow_regrade = isset( $_POST['allow_regrade'] ) ? 1 : 0;

        $starts_at = $starts_at_raw ? str_replace( 'T', ' ', $starts_at_raw ) . ':00' : null;
        $ends_at   = $ends_at_raw   ? str_replace( 'T', ' ', $ends_at_raw   ) . ':00' : null;

        if ( empty( $title ) ) {
            wp_send_json_error( [ 'message' => __( 'عنوان الامتحان مطلوب.', 'rsyi-sa' ) ] );
        }

        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'rsyi_exams',
            [
                'title'         => $title,
                'subject'       => $subject ?: null,
                'cohort_id'     => $cohort_id ?: null,
                'starts_at'     => $starts_at,
                'ends_at'       => $ends_at,
                'duration_min'  => $duration_min ?: null,
                'max_score'     => $max_score ?: 100,
                'passing_score' => $passing_score,
                'exam_type'     => $exam_type,
                'status'        => $status,
                'description'   => $desc,
                'show_results'  => $show_results,
                'auto_grade'    => $auto_grade,
                'allow_regrade' => $allow_regrade,
            ],
            [ 'id' => $exam_id ]
        );

        \RSYI_SA\Audit_Log::log( 'exam', $exam_id, 'update', [ 'title' => $title ] );

        wp_send_json_success( [ 'message' => __( 'تم تحديث الامتحان بنجاح.', 'rsyi-sa' ) ] );
    }

    public static function ajax_get_exam_stats(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );

        if ( ! current_user_can( 'rsyi_view_exam_stats' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        $exam_id = absint( $_POST['exam_id'] ?? 0 );
        if ( ! $exam_id ) {
            wp_send_json_error( [ 'message' => __( 'معرف الامتحان مطلوب.', 'rsyi-sa' ) ] );
        }

        global $wpdb;
        $exam = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rsyi_exams WHERE id = %d",
            $exam_id
        ) );

        if ( ! $exam ) {
            wp_send_json_error( [ 'message' => __( 'الامتحان غير موجود.', 'rsyi-sa' ) ] );
        }

        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT r.score, r.grade, sp.arabic_full_name, sp.english_full_name
             FROM {$wpdb->prefix}rsyi_exam_results r
             JOIN {$wpdb->prefix}rsyi_student_profiles sp ON sp.id = r.student_id
             WHERE r.exam_id = %d
             ORDER BY r.score DESC",
            $exam_id
        ) );

        $max      = (int) $exam->max_score ?: 100;
        $passing  = isset( $exam->passing_score ) && $exam->passing_score !== null
                    ? (int) $exam->passing_score
                    : (int) round( $max * 0.5 );

        $count    = count( $results );
        $passed   = 0;
        $scores   = [];
        $dist     = [ 'A+' => 0, 'A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'F' => 0 ];
        $ranked   = [];

        foreach ( $results as $r ) {
            $s    = (int) $r->score;
            $pct  = $max > 0 ? $s / $max * 100 : 0;
            if ( $s >= $passing ) $passed++;
            $scores[] = $s;
            $letter = $pct >= 90 ? 'A+' : ( $pct >= 80 ? 'A' : ( $pct >= 70 ? 'B' : ( $pct >= 60 ? 'C' : ( $pct >= 50 ? 'D' : 'F' ) ) ) );
            $dist[ $letter ]++;
            $ranked[] = [
                'name'   => $r->arabic_full_name ?: $r->english_full_name,
                'score'  => $s,
                'pct'    => round( $pct, 1 ),
                'grade'  => $letter,
            ];
        }

        $avg       = $count > 0 ? round( array_sum( $scores ) / $count, 1 ) : 0;
        $pass_pct  = $count > 0 ? round( $passed / $count * 100, 1 ) : 0;

        wp_send_json_success( [
            'exam'      => [ 'title' => $exam->title, 'max_score' => $max, 'passing_score' => $passing ],
            'count'     => $count,
            'avg'       => $avg,
            'max_val'   => $count > 0 ? max( $scores ) : 0,
            'min_val'   => $count > 0 ? min( $scores ) : 0,
            'passed'    => $passed,
            'pass_pct'  => $pass_pct,
            'dist'      => $dist,
            'ranked'    => $ranked,
        ] );
    }

    public static function ajax_export_exam_results(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );

        if ( ! current_user_can( 'rsyi_export_exam_results' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        $exam_id = absint( $_POST['exam_id'] ?? 0 );
        if ( ! $exam_id ) {
            wp_send_json_error( [ 'message' => __( 'معرف الامتحان مطلوب.', 'rsyi-sa' ) ] );
        }

        global $wpdb;
        $exam = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rsyi_exams WHERE id = %d",
            $exam_id
        ) );

        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT sp.arabic_full_name, sp.english_full_name, r.score, r.grade, r.notes, r.is_passing
             FROM {$wpdb->prefix}rsyi_exam_results r
             JOIN {$wpdb->prefix}rsyi_student_profiles sp ON sp.id = r.student_id
             WHERE r.exam_id = %d
             ORDER BY r.score DESC",
            $exam_id
        ) );

        $rows = [];
        $rows[] = [ 'الاسم بالعربي', 'الاسم بالإنجليزي', 'الدرجة', 'الدرجة القصوى', 'التقدير', 'النجاح/الرسوب', 'ملاحظات' ];
        foreach ( $results as $r ) {
            $rows[] = [
                $r->arabic_full_name,
                $r->english_full_name,
                $r->score,
                $exam ? $exam->max_score : '',
                $r->grade,
                $r->is_passing ? 'ناجح' : 'راسب',
                $r->notes,
            ];
        }

        $csv = '';
        foreach ( $rows as $row ) {
            $csv .= implode( ',', array_map( fn( $v ) => '"' . str_replace( '"', '""', $v ) . '"', $row ) ) . "\n";
        }

        wp_send_json_success( [
            'csv'      => $csv,
            'filename' => sanitize_file_name( ( $exam ? $exam->title : 'exam' ) . '-results.csv' ),
        ] );
    }

    // ── Study Materials AJAX (delete) ──────────────────────────────────────────

    public static function ajax_delete_material(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );

        if ( ! current_user_can( 'rsyi_upload_study_materials' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        $material_id = absint( $_POST['material_id'] ?? 0 );
        if ( ! $material_id ) {
            wp_send_json_error( [ 'message' => __( 'معرف المادة مطلوب.', 'rsyi-sa' ) ] );
        }

        global $wpdb;
        $material = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rsyi_study_materials WHERE id = %d",
            $material_id
        ) );

        if ( ! $material ) {
            wp_send_json_error( [ 'message' => __( 'المادة غير موجودة.', 'rsyi-sa' ) ] );
        }

        // Delete the physical file
        $file_path = RSYI_SA_UPLOAD_DIR . '/' . $material->file_path;
        if ( file_exists( $file_path ) ) {
            @unlink( $file_path ); // phpcs:ignore
        }

        $wpdb->delete( $wpdb->prefix . 'rsyi_study_materials', [ 'id' => $material_id ] );

        \RSYI_SA\Audit_Log::log( 'study_material', $material_id, 'delete', [ 'title' => $material->title ] );

        wp_send_json_success( [ 'message' => __( 'تم حذف المادة بنجاح.', 'rsyi-sa' ) ] );
    }

    public static function ajax_reseed_violation_types(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );

        if ( ! current_user_can( 'rsyi_manage_settings' ) ) {
            wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rsyi-sa' ) ] );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'rsyi_violation_types';

        $defaults = [
            [ 'name_ar' => 'التأخر عن الحضور',         'name_en' => 'Late Attendance',         'default_points' => 3,  'max_points' => 10, 'requires_dean' => 0, 'is_dean_discretion' => 0 ],
            [ 'name_ar' => 'الغياب بدون إذن',           'name_en' => 'Absent Without Leave',    'default_points' => 5,  'max_points' => 15, 'requires_dean' => 0, 'is_dean_discretion' => 0 ],
            [ 'name_ar' => 'السلوك غير اللائق',         'name_en' => 'Inappropriate Behavior',  'default_points' => 10, 'max_points' => 20, 'requires_dean' => 0, 'is_dean_discretion' => 0 ],
            [ 'name_ar' => 'مخالفة قواعد السكن',        'name_en' => 'Dorm Rules Violation',    'default_points' => 7,  'max_points' => 15, 'requires_dean' => 0, 'is_dean_discretion' => 0 ],
            [ 'name_ar' => 'الاعتداء الجسدي',           'name_en' => 'Physical Assault',        'default_points' => 20, 'max_points' => 30, 'requires_dean' => 1, 'is_dean_discretion' => 0 ],
            [ 'name_ar' => 'حيازة مواد مخدرة',          'name_en' => 'Possession of Narcotics', 'default_points' => 30, 'max_points' => 30, 'requires_dean' => 1, 'is_dean_discretion' => 1 ],
            [ 'name_ar' => 'التحرش أو الإساءة الجنسية', 'name_en' => 'Sexual Harassment/Abuse', 'default_points' => 30, 'max_points' => 30, 'requires_dean' => 1, 'is_dean_discretion' => 1 ],
            [ 'name_ar' => 'مخالفة تقديرية – العميد',   'name_en' => 'Dean Discretionary',      'default_points' => 5,  'max_points' => 30, 'requires_dean' => 1, 'is_dean_discretion' => 1 ],
        ];

        $added = 0;
        foreach ( $defaults as $type ) {
            // Only insert if this Arabic name doesn't already exist
            $exists = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$table} WHERE name_ar = %s LIMIT 1",
                $type['name_ar']
            ) );
            if ( ! $exists ) {
                $wpdb->insert( $table, $type );
                $added++;
            }
        }

        $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

        wp_send_json_success( [
            'message' => sprintf(
                /* translators: 1: added count, 2: total count */
                __( 'Done. Added %1$d new type(s). Total: %2$d.', 'rsyi-sa' ),
                $added,
                $total
            ),
        ] );
    }

    // ── Exam Questions AJAX ────────────────────────────────────────────────────

    public static function ajax_get_questions(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );
        if ( ! current_user_can( 'rsyi_manage_exams' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        $exam_id = absint( $_POST['exam_id'] ?? 0 );
        if ( ! $exam_id ) {
            wp_send_json_error( [ 'message' => __( 'معرف الامتحان مطلوب.', 'rsyi-sa' ) ] );
        }

        global $wpdb;
        $questions = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rsyi_exam_questions WHERE exam_id = %d ORDER BY question_number ASC",
            $exam_id
        ) );

        foreach ( $questions as $q ) {
            $q->image_url = $q->image_id ? wp_get_attachment_image_url( (int) $q->image_id, 'medium' ) : null;
        }

        wp_send_json_success( $questions );
    }

    public static function ajax_save_question(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );
        if ( ! current_user_can( 'rsyi_manage_exams' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        global $wpdb;

        $exam_id         = absint( $_POST['exam_id'] ?? 0 );
        $question_id     = absint( $_POST['question_id'] ?? 0 );
        $question_number = absint( $_POST['question_number'] ?? 1 );
        $question_text   = sanitize_textarea_field( wp_unslash( $_POST['question_text'] ?? '' ) );
        $question_type   = sanitize_key( $_POST['question_type'] ?? 'essay' );
        $image_id        = absint( $_POST['image_id'] ?? 0 );
        $marks           = (float) ( $_POST['marks'] ?? 1 );
        $explanation     = sanitize_textarea_field( wp_unslash( $_POST['explanation'] ?? '' ) );

        // Options / correct_answer depend on question type (sent as JSON string from JS)
        $options_raw        = wp_unslash( $_POST['options']         ?? '' );
        $correct_answer_raw = wp_unslash( $_POST['correct_answer']  ?? '' );

        // Validate JSON if provided
        $options        = null;
        $correct_answer = null;
        if ( $options_raw ) {
            $decoded = json_decode( $options_raw, true );
            $options = is_array( $decoded ) ? wp_json_encode( $decoded ) : null;
        }
        if ( $correct_answer_raw !== '' ) {
            $correct_answer = sanitize_textarea_field( $correct_answer_raw );
        }

        $allowed_types = [ 'mcq', 'true_false', 'matching', 'fill_blank', 'short_answer', 'essay', 'ordering' ];
        if ( ! in_array( $question_type, $allowed_types, true ) ) {
            $question_type = 'essay';
        }

        if ( ! $exam_id || ! $question_text ) {
            wp_send_json_error( [ 'message' => __( 'نص السؤال مطلوب.', 'rsyi-sa' ) ] );
        }

        $data = [
            'exam_id'         => $exam_id,
            'question_number' => max( 1, $question_number ),
            'question_text'   => $question_text,
            'question_type'   => $question_type,
            'options'         => $options,
            'correct_answer'  => $correct_answer,
            'explanation'     => $explanation ?: null,
            'image_id'        => $image_id ?: null,
            'marks'           => max( 0, $marks ),
        ];

        if ( $question_id ) {
            $wpdb->update(
                "{$wpdb->prefix}rsyi_exam_questions",
                $data,
                [ 'id' => $question_id ]
            );
            wp_send_json_success( [ 'message' => __( 'تم تحديث السؤال.', 'rsyi-sa' ), 'id' => $question_id ] );
        } else {
            $wpdb->insert( "{$wpdb->prefix}rsyi_exam_questions", $data );
            wp_send_json_success( [ 'message' => __( 'تم إضافة السؤال.', 'rsyi-sa' ), 'id' => $wpdb->insert_id ] );
        }
    }

    public static function ajax_delete_question(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );
        if ( ! current_user_can( 'rsyi_manage_exams' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        $question_id = absint( $_POST['question_id'] ?? 0 );
        if ( ! $question_id ) {
            wp_send_json_error( [ 'message' => __( 'معرف السؤال مطلوب.', 'rsyi-sa' ) ] );
        }

        global $wpdb;
        $wpdb->delete( "{$wpdb->prefix}rsyi_exam_questions", [ 'id' => $question_id ], [ '%d' ] );
        wp_send_json_success( [ 'message' => __( 'تم حذف السؤال.', 'rsyi-sa' ) ] );
    }

    // ── Auto-grade exam ────────────────────────────────────────────────────────

    public static function ajax_auto_grade_exam(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );
        if ( ! current_user_can( 'rsyi_manage_exams' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        $exam_id = absint( $_POST['exam_id'] ?? 0 );
        if ( ! $exam_id ) {
            wp_send_json_error( [ 'message' => __( 'معرف الامتحان مطلوب.', 'rsyi-sa' ) ] );
        }

        global $wpdb;

        $exam = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rsyi_exams WHERE id = %d",
            $exam_id
        ) );
        if ( ! $exam ) {
            wp_send_json_error( [ 'message' => __( 'الامتحان غير موجود.', 'rsyi-sa' ) ] );
        }

        $max_score    = (int) ( $exam->max_score ?: 100 );
        $passing_thrs = $exam->passing_score !== null ? (int) $exam->passing_score : (int) round( $max_score * 0.5 );

        // Get all questions for this exam
        $questions = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rsyi_exam_questions WHERE exam_id = %d ORDER BY question_number ASC",
            $exam_id
        ) );

        // Get all student answers for this exam
        $answers_raw = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rsyi_exam_answers WHERE exam_id = %d",
            $exam_id
        ) );

        // Build: [student_id][question_id] = answer_data
        $answers_map = [];
        foreach ( $answers_raw as $a ) {
            $answers_map[ (int) $a->student_id ][ (int) $a->question_id ] = $a->answer_data;
        }

        if ( empty( $answers_map ) ) {
            wp_send_json_error( [ 'message' => __( 'لا توجد إجابات مُسلَّمة لهذا الامتحان.', 'rsyi-sa' ) ] );
        }

        $graded = 0;
        foreach ( $answers_map as $student_id => $student_answers ) {
            $total_score    = 0;
            $total_possible = 0;

            foreach ( $questions as $q ) {
                $q_marks  = (float) $q->marks;
                $total_possible += $q_marks;
                $answer   = $student_answers[ (int) $q->id ] ?? null;
                if ( $answer === null ) continue;

                $is_correct = false;

                switch ( $q->question_type ) {
                    case 'mcq':
                        // options JSON: [{"text":"...","correct":bool},...]
                        // answer_data: index of chosen option (string)
                        $opts = $q->options ? json_decode( $q->options, true ) : [];
                        $chosen = (int) $answer;
                        if ( isset( $opts[ $chosen ] ) && ! empty( $opts[ $chosen ]['correct'] ) ) {
                            $is_correct = true;
                        }
                        break;

                    case 'true_false':
                        // correct_answer: "true" | "false"
                        $is_correct = ( strtolower( trim( $answer ) ) === strtolower( trim( $q->correct_answer ?? '' ) ) );
                        break;

                    case 'matching':
                        // options: [{"premise":"...","match":"..."},...]
                        // answer_data: JSON [{"premise":"...","match":"..."}, ...] – student's pairs
                        $correct_pairs = $q->options ? json_decode( $q->options, true ) : [];
                        $student_pairs = json_decode( $answer, true );
                        if ( is_array( $correct_pairs ) && is_array( $student_pairs ) ) {
                            $correct_map = [];
                            foreach ( $correct_pairs as $p ) {
                                $correct_map[ $p['premise'] ] = $p['match'];
                            }
                            $all_correct = true;
                            foreach ( $student_pairs as $p ) {
                                if ( ( $correct_map[ $p['premise'] ] ?? '' ) !== $p['match'] ) {
                                    $all_correct = false;
                                    break;
                                }
                            }
                            $is_correct = $all_correct && count( $student_pairs ) === count( $correct_pairs );
                        }
                        break;

                    case 'fill_blank':
                        // correct_answer: plain text
                        $is_correct = ( mb_strtolower( trim( $answer ) ) === mb_strtolower( trim( $q->correct_answer ?? '' ) ) );
                        break;

                    case 'ordering':
                        // options: [{"text":"...","order":int},...]
                        // answer_data: JSON array of texts in student's order
                        $correct_items = $q->options ? json_decode( $q->options, true ) : [];
                        $student_order = json_decode( $answer, true );
                        if ( is_array( $correct_items ) && is_array( $student_order ) ) {
                            usort( $correct_items, fn( $a, $b ) => $a['order'] - $b['order'] );
                            $correct_order = array_column( $correct_items, 'text' );
                            $is_correct    = ( $correct_order === $student_order );
                        }
                        break;

                    case 'short_answer':
                    case 'essay':
                    default:
                        // No auto-grading for open-ended types
                        continue 2;
                }

                if ( $is_correct ) {
                    $total_score += $q_marks;
                }

                // Update individual answer record
                $wpdb->update(
                    "{$wpdb->prefix}rsyi_exam_answers",
                    [
                        'is_correct'   => $is_correct ? 1 : 0,
                        'score_earned' => $is_correct ? $q_marks : 0,
                    ],
                    [
                        'exam_id'     => $exam_id,
                        'student_id'  => $student_id,
                        'question_id' => (int) $q->id,
                    ]
                );
            }

            // Normalize score to exam's max_score scale
            $scaled_score = $total_possible > 0
                ? (int) round( $total_score / $total_possible * $max_score )
                : 0;

            $pct   = $max_score > 0 ? $scaled_score / $max_score * 100 : 0;
            $grade = $pct >= 90 ? 'A+' : ( $pct >= 80 ? 'A' : ( $pct >= 70 ? 'B' : ( $pct >= 60 ? 'C' : ( $pct >= 50 ? 'D' : 'F' ) ) ) );
            $is_passing = $scaled_score >= $passing_thrs ? 1 : 0;

            $existing = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}rsyi_exam_results WHERE exam_id = %d AND student_id = %d LIMIT 1",
                $exam_id, $student_id
            ) );

            if ( $existing ) {
                $wpdb->update(
                    "{$wpdb->prefix}rsyi_exam_results",
                    [
                        'score'       => $scaled_score,
                        'grade'       => $grade,
                        'is_passing'  => $is_passing,
                        'auto_graded' => 1,
                        'recorded_by' => get_current_user_id(),
                    ],
                    [ 'id' => $existing ]
                );
            } else {
                $wpdb->insert(
                    "{$wpdb->prefix}rsyi_exam_results",
                    [
                        'exam_id'     => $exam_id,
                        'student_id'  => $student_id,
                        'score'       => $scaled_score,
                        'grade'       => $grade,
                        'is_passing'  => $is_passing,
                        'auto_graded' => 1,
                        'recorded_by' => get_current_user_id(),
                    ]
                );
            }
            $graded++;
        }

        \RSYI_SA\Audit_Log::log( 'exam', $exam_id, 'auto_grade', [ 'graded' => $graded ] );

        wp_send_json_success( [
            'message' => sprintf( __( 'تم التصحيح التلقائي لـ %d طالب.', 'rsyi-sa' ), $graded ),
            'graded'  => $graded,
        ] );
    }

    // ── Re-grade a single student result ──────────────────────────────────────

    public static function ajax_regrade_result(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );
        if ( ! current_user_can( 'rsyi_manage_exams' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        $exam_id    = absint( $_POST['exam_id']    ?? 0 );
        $student_id = absint( $_POST['student_id'] ?? 0 );
        $score      = absint( $_POST['score']      ?? 0 );
        $notes      = sanitize_text_field( wp_unslash( $_POST['notes'] ?? '' ) );

        if ( ! $exam_id || ! $student_id ) {
            wp_send_json_error( [ 'message' => __( 'بيانات غير مكتملة.', 'rsyi-sa' ) ] );
        }

        global $wpdb;

        $exam = $wpdb->get_row( $wpdb->prepare(
            "SELECT max_score, passing_score FROM {$wpdb->prefix}rsyi_exams WHERE id = %d",
            $exam_id
        ) );
        $max_score    = $exam ? (int) $exam->max_score : 100;
        $passing_thrs = ( $exam && $exam->passing_score !== null ) ? (int) $exam->passing_score : (int) round( $max_score * 0.5 );

        $pct       = $max_score > 0 ? $score / $max_score * 100 : 0;
        $grade     = $pct >= 90 ? 'A+' : ( $pct >= 80 ? 'A' : ( $pct >= 70 ? 'B' : ( $pct >= 60 ? 'C' : ( $pct >= 50 ? 'D' : 'F' ) ) ) );
        $is_passing = $score >= $passing_thrs ? 1 : 0;
        $now        = current_time( 'mysql' );

        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}rsyi_exam_results WHERE exam_id = %d AND student_id = %d LIMIT 1",
            $exam_id, $student_id
        ) );

        if ( $existing ) {
            $wpdb->update(
                "{$wpdb->prefix}rsyi_exam_results",
                [
                    'score'       => $score,
                    'grade'       => $grade,
                    'is_passing'  => $is_passing,
                    'notes'       => $notes,
                    'regraded_by' => get_current_user_id(),
                    'regraded_at' => $now,
                    'recorded_by' => get_current_user_id(),
                ],
                [ 'id' => $existing ]
            );
        } else {
            $wpdb->insert(
                "{$wpdb->prefix}rsyi_exam_results",
                [
                    'exam_id'     => $exam_id,
                    'student_id'  => $student_id,
                    'score'       => $score,
                    'grade'       => $grade,
                    'is_passing'  => $is_passing,
                    'notes'       => $notes,
                    'regraded_by' => get_current_user_id(),
                    'regraded_at' => $now,
                    'recorded_by' => get_current_user_id(),
                ]
            );
        }

        \RSYI_SA\Audit_Log::log( 'exam_result', $exam_id, 'regrade', [
            'student_id' => $student_id,
            'score'      => $score,
            'grade'      => $grade,
        ] );

        wp_send_json_success( [
            'message'    => __( 'تم تحديث الدرجة.', 'rsyi-sa' ),
            'grade'      => $grade,
            'is_passing' => $is_passing,
        ] );
    }

    // ── Student submits exam answers ───────────────────────────────────────────

    public static function ajax_submit_exam(): void {
        check_ajax_referer( 'rsyi_sa_portal', '_nonce' );

        if ( ! is_user_logged_in() || ! current_user_can( 'rsyi_take_exam' ) ) {
            wp_send_json_error( [ 'message' => __( 'يجب تسجيل الدخول أولاً.', 'rsyi-sa' ) ] );
        }

        $exam_id = absint( $_POST['exam_id'] ?? 0 );
        if ( ! $exam_id ) {
            wp_send_json_error( [ 'message' => __( 'معرف الامتحان مطلوب.', 'rsyi-sa' ) ] );
        }

        global $wpdb;

        // Get exam and validate it's still open
        $exam = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rsyi_exams WHERE id = %d AND is_active = 1",
            $exam_id
        ) );
        if ( ! $exam ) {
            wp_send_json_error( [ 'message' => __( 'الامتحان غير موجود.', 'rsyi-sa' ) ] );
        }

        $now = current_time( 'mysql' );
        if ( $exam->starts_at && $now < $exam->starts_at ) {
            wp_send_json_error( [ 'message' => __( 'لم يبدأ وقت الامتحان بعد.', 'rsyi-sa' ) ] );
        }
        if ( $exam->ends_at && $now > $exam->ends_at ) {
            wp_send_json_error( [ 'message' => __( 'انتهى وقت الامتحان.', 'rsyi-sa' ) ] );
        }

        // Get student profile
        $profile = \RSYI_SA\Modules\Accounts::get_profile_by_user_id( get_current_user_id() );
        if ( ! $profile ) {
            wp_send_json_error( [ 'message' => __( 'لم يتم العثور على ملفك الشخصي.', 'rsyi-sa' ) ] );
        }
        $student_id = (int) $profile->id;

        // Check not already submitted
        $already = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}rsyi_exam_results WHERE exam_id = %d AND student_id = %d LIMIT 1",
            $exam_id, $student_id
        ) );
        if ( $already ) {
            wp_send_json_error( [ 'message' => __( 'لقد سلّمت هذا الامتحان مسبقاً.', 'rsyi-sa' ) ] );
        }

        // Get answers from POST: answers[question_id] = answer_data
        $submitted_answers = (array) ( $_POST['answers'] ?? [] );

        // Save each answer
        foreach ( $submitted_answers as $q_id => $ans_raw ) {
            $question_id = absint( $q_id );
            if ( ! $question_id ) continue;

            $answer_data = is_array( $ans_raw )
                ? wp_json_encode( array_map( 'sanitize_text_field', $ans_raw ) )
                : sanitize_textarea_field( wp_unslash( (string) $ans_raw ) );

            $wpdb->replace(
                "{$wpdb->prefix}rsyi_exam_answers",
                [
                    'exam_id'     => $exam_id,
                    'student_id'  => $student_id,
                    'question_id' => $question_id,
                    'answer_data' => $answer_data,
                ]
            );
        }

        $max_score    = (int) ( $exam->max_score ?: 100 );
        $passing_thrs = $exam->passing_score !== null ? (int) $exam->passing_score : (int) round( $max_score * 0.5 );

        // If auto_grade is on: compute score immediately
        $score      = null;
        $grade      = null;
        $is_passing = null;
        $auto_graded = 0;

        if ( $exam->auto_grade ) {
            $questions = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}rsyi_exam_questions WHERE exam_id = %d",
                $exam_id
            ) );

            $total_score    = 0;
            $total_possible = 0;

            foreach ( $questions as $q ) {
                $q_marks = (float) $q->marks;
                $total_possible += $q_marks;
                $answer = $submitted_answers[ (string) $q->id ] ?? null;
                if ( $answer === null ) continue;

                $is_correct = false;

                switch ( $q->question_type ) {
                    case 'mcq':
                        $opts   = $q->options ? json_decode( $q->options, true ) : [];
                        $chosen = (int) $answer;
                        $is_correct = isset( $opts[ $chosen ] ) && ! empty( $opts[ $chosen ]['correct'] );
                        break;

                    case 'true_false':
                        $is_correct = ( strtolower( trim( is_array($answer) ? '' : $answer ) ) === strtolower( trim( $q->correct_answer ?? '' ) ) );
                        break;

                    case 'matching':
                        $correct_pairs  = $q->options ? json_decode( $q->options, true ) : [];
                        $student_pairs  = is_array( $answer ) ? $answer : json_decode( $answer, true );
                        if ( is_array( $correct_pairs ) && is_array( $student_pairs ) ) {
                            $correct_map = [];
                            foreach ( $correct_pairs as $p ) { $correct_map[ $p['premise'] ] = $p['match']; }
                            $all_ok = true;
                            foreach ( $student_pairs as $p ) {
                                if ( ( $correct_map[ $p['premise'] ] ?? '' ) !== $p['match'] ) { $all_ok = false; break; }
                            }
                            $is_correct = $all_ok && count( $student_pairs ) === count( $correct_pairs );
                        }
                        break;

                    case 'fill_blank':
                        $is_correct = ( mb_strtolower( trim( is_array($answer) ? '' : $answer ) ) === mb_strtolower( trim( $q->correct_answer ?? '' ) ) );
                        break;

                    case 'ordering':
                        $correct_items = $q->options ? json_decode( $q->options, true ) : [];
                        $student_order = is_array( $answer ) ? $answer : json_decode( $answer, true );
                        if ( is_array( $correct_items ) && is_array( $student_order ) ) {
                            usort( $correct_items, fn( $a, $b ) => $a['order'] - $b['order'] );
                            $is_correct = ( array_column( $correct_items, 'text' ) === $student_order );
                        }
                        break;

                    default:
                        continue 2;
                }

                if ( $is_correct ) $total_score += $q_marks;

                $wpdb->update(
                    "{$wpdb->prefix}rsyi_exam_answers",
                    [ 'is_correct' => $is_correct ? 1 : 0, 'score_earned' => $is_correct ? $q_marks : 0 ],
                    [ 'exam_id' => $exam_id, 'student_id' => $student_id, 'question_id' => (int) $q->id ]
                );
            }

            $score       = $total_possible > 0 ? (int) round( $total_score / $total_possible * $max_score ) : 0;
            $pct         = $max_score > 0 ? $score / $max_score * 100 : 0;
            $grade       = $pct >= 90 ? 'A+' : ( $pct >= 80 ? 'A' : ( $pct >= 70 ? 'B' : ( $pct >= 60 ? 'C' : ( $pct >= 50 ? 'D' : 'F' ) ) ) );
            $is_passing  = $score >= $passing_thrs ? 1 : 0;
            $auto_graded = 1;
        }

        // Save result record
        $wpdb->insert(
            "{$wpdb->prefix}rsyi_exam_results",
            [
                'exam_id'      => $exam_id,
                'student_id'   => $student_id,
                'score'        => $score,
                'grade'        => $grade,
                'is_passing'   => $is_passing,
                'submitted_at' => $now,
                'auto_graded'  => $auto_graded,
                'recorded_by'  => 0,
            ]
        );

        $response = [ 'message' => __( 'تم تسليم الامتحان بنجاح.', 'rsyi-sa' ) ];
        if ( $exam->show_results && $auto_graded ) {
            $response['score']      = $score;
            $response['grade']      = $grade;
            $response['is_passing'] = $is_passing;
            $response['max_score']  = $max_score;
        }

        wp_send_json_success( $response );
    }

    // ── Get exam data for student (portal) ────────────────────────────────────

    public static function ajax_get_exam_for_student(): void {
        check_ajax_referer( 'rsyi_sa_portal', '_nonce' );

        if ( ! is_user_logged_in() || ! current_user_can( 'rsyi_take_exam' ) ) {
            wp_send_json_error( [ 'message' => __( 'يجب تسجيل الدخول أولاً.', 'rsyi-sa' ) ] );
        }

        $exam_id = absint( $_POST['exam_id'] ?? 0 );
        if ( ! $exam_id ) {
            wp_send_json_error( [ 'message' => __( 'معرف الامتحان مطلوب.', 'rsyi-sa' ) ] );
        }

        global $wpdb;

        $exam = $wpdb->get_row( $wpdb->prepare(
            "SELECT id, title, starts_at, ends_at, duration_min, max_score FROM {$wpdb->prefix}rsyi_exams
             WHERE id = %d AND is_active = 1",
            $exam_id
        ) );
        if ( ! $exam ) {
            wp_send_json_error( [ 'message' => __( 'الامتحان غير موجود.', 'rsyi-sa' ) ] );
        }

        // Only return questions without correct_answer (don't leak answers to student)
        $questions = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, question_number, question_text, question_type, options, marks, image_id
             FROM {$wpdb->prefix}rsyi_exam_questions
             WHERE exam_id = %d ORDER BY question_number ASC",
            $exam_id
        ) );

        foreach ( $questions as $q ) {
            $q->image_url = $q->image_id ? wp_get_attachment_image_url( (int) $q->image_id, 'medium' ) : null;
            // For matching/ordering, shuffle options so student doesn't see the order
            if ( $q->options && in_array( $q->question_type, [ 'matching', 'ordering' ], true ) ) {
                $opts = json_decode( $q->options, true );
                if ( is_array( $opts ) ) {
                    if ( $q->question_type === 'ordering' ) {
                        // Remove order field before sending
                        $opts = array_map( fn( $o ) => [ 'text' => $o['text'] ], $opts );
                        shuffle( $opts );
                    } elseif ( $q->question_type === 'matching' ) {
                        // Send premises and matches separately (shuffled)
                        $matches = array_column( $opts, 'match' );
                        shuffle( $matches );
                        $q->shuffled_matches = $matches;
                        $opts = array_map( fn( $o ) => [ 'premise' => $o['premise'] ], $opts );
                    }
                    $q->options = wp_json_encode( $opts );
                }
            }
        }

        wp_send_json_success( [
            'exam'      => $exam,
            'questions' => $questions,
        ] );
    }
}
