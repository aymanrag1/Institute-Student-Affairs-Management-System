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

        $title        = sanitize_text_field( wp_unslash( $_POST['title']       ?? '' ) );
        $subject      = sanitize_text_field( wp_unslash( $_POST['subject']     ?? '' ) );
        $cohort_id    = absint( $_POST['cohort_id'] ?? 0 );
        $exam_date    = sanitize_text_field( wp_unslash( $_POST['exam_date']   ?? '' ) );
        $duration_min = absint( $_POST['duration_min'] ?? 0 );
        $max_score    = absint( $_POST['max_score']    ?? 100 );
        $desc         = sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) );

        if ( empty( $title ) ) {
            wp_send_json_error( [ 'message' => __( 'عنوان الامتحان مطلوب.', 'rsyi-sa' ) ] );
        }

        global $wpdb;
        $wpdb->insert( $wpdb->prefix . 'rsyi_exams', [
            'cohort_id'    => $cohort_id ?: null,
            'title'        => $title,
            'description'  => $desc,
            'subject'      => $subject ?: null,
            'exam_date'    => $exam_date ?: null,
            'duration_min' => $duration_min ?: null,
            'max_score'    => $max_score ?: 100,
            'is_active'    => 1,
            'created_by'   => get_current_user_id(),
        ] );

        \RSYI_SA\Audit_Log::log( 'exam', $wpdb->insert_id, 'create', [
            'title'     => $title,
            'cohort_id' => $cohort_id,
        ] );

        wp_send_json_success( [
            'message'  => __( 'تم إنشاء الامتحان بنجاح.', 'rsyi-sa' ),
            'exam_id'  => $wpdb->insert_id,
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

        foreach ( $student_ids as $student_id ) {
            $score_raw = $_POST[ "score_{$student_id}" ] ?? '';
            if ( $score_raw === '' ) continue; // Skip empty entries

            $score = absint( $score_raw );
            $grade = sanitize_text_field( wp_unslash( $_POST[ "grade_{$student_id}" ] ?? '' ) );
            $notes = sanitize_text_field( wp_unslash( $_POST[ "notes_{$student_id}" ] ?? '' ) );

            $existing = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$table} WHERE exam_id = %d AND student_id = %d LIMIT 1",
                $exam_id, $student_id
            ) );

            if ( $existing ) {
                $wpdb->update(
                    $table,
                    [ 'score' => $score, 'grade' => $grade, 'notes' => $notes, 'recorded_by' => $current_by ],
                    [ 'id' => $existing ]
                );
            } else {
                $wpdb->insert( $table, [
                    'exam_id'     => $exam_id,
                    'student_id'  => $student_id,
                    'score'       => $score,
                    'grade'       => $grade,
                    'notes'       => $notes,
                    'recorded_by' => $current_by,
                ] );
            }
            $saved++;
        }

        wp_send_json_success( [
            'message' => sprintf( __( 'تم حفظ نتائج %d طالب.', 'rsyi-sa' ), $saved ),
        ] );
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
}
