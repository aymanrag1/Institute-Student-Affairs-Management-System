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
            [ 'rsyi-dashboard',    __( 'Dashboard', 'rsyi-sa' ),       'rsyi_view_all_students',    [ __CLASS__, 'page_dashboard' ] ],
            [ 'rsyi-students',     __( 'Students', 'rsyi-sa' ),        'rsyi_view_all_students',    [ __CLASS__, 'page_students' ] ],
            [ 'rsyi-documents',    __( 'Documents', 'rsyi-sa' ),       'rsyi_view_all_documents',   [ __CLASS__, 'page_documents' ] ],
            [ 'rsyi-exit',         __( 'Exit Permits', 'rsyi-sa' ),    'rsyi_view_all_requests',    [ __CLASS__, 'page_exit_permits' ] ],
            [ 'rsyi-overnight',    __( 'Overnight Permits', 'rsyi-sa' ), 'rsyi_view_all_requests',  [ __CLASS__, 'page_overnight_permits' ] ],
            [ 'rsyi-violations',   __( 'Violations', 'rsyi-sa' ),      'rsyi_view_all_violations',  [ __CLASS__, 'page_violations' ] ],
            [ 'rsyi-expulsion',    __( 'Expulsion Cases', 'rsyi-sa' ), 'rsyi_manage_expulsion',     [ __CLASS__, 'page_expulsion' ] ],
            [ 'rsyi-cohorts',      __( 'Cohorts', 'rsyi-sa' ),         'rsyi_manage_cohorts',       [ __CLASS__, 'page_cohorts' ] ],
            [ 'rsyi-evaluations',  __( 'Evaluations', 'rsyi-sa' ),     'rsyi_view_evaluations',     [ __CLASS__, 'page_evaluations' ] ],
            [ 'rsyi-daily-report', __( 'Daily Report PDF', 'rsyi-sa' ), 'rsyi_print_daily_report',  [ __CLASS__, 'page_daily_report' ] ],
            [ 'rsyi-audit',        __( 'Audit Log', 'rsyi-sa' ),       'rsyi_view_audit_log',       [ __CLASS__, 'page_audit_log' ] ],
            [ 'rsyi-settings',     __( 'Settings', 'rsyi-sa' ),        'rsyi_manage_settings',      [ __CLASS__, 'page_settings' ] ],
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
