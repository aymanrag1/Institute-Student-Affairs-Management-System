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
    }

    public static function register_menus(): void {
        $icon = 'dashicons-welcome-learn-more';

        add_menu_page(
            __( 'شؤون الطلاب – RSYI', 'rsyi-sa' ),
            __( 'شؤون الطلاب', 'rsyi-sa' ),
            'rsyi_view_all_students',
            'rsyi-dashboard',
            [ __CLASS__, 'page_dashboard' ],
            $icon,
            30
        );

        $subpages = [
            [ 'rsyi-dashboard',    __( 'لوحة التحكم', 'rsyi-sa' ),    'rsyi_view_all_students',    [ __CLASS__, 'page_dashboard' ] ],
            [ 'rsyi-students',     __( 'الطلاب', 'rsyi-sa' ),          'rsyi_view_all_students',    [ __CLASS__, 'page_students' ] ],
            [ 'rsyi-documents',    __( 'الوثائق', 'rsyi-sa' ),         'rsyi_view_all_documents',   [ __CLASS__, 'page_documents' ] ],
            [ 'rsyi-exit',         __( 'أذونات الخروج', 'rsyi-sa' ),   'rsyi_view_all_requests',    [ __CLASS__, 'page_exit_permits' ] ],
            [ 'rsyi-overnight',    __( 'أذونات المبيت', 'rsyi-sa' ),   'rsyi_view_all_requests',    [ __CLASS__, 'page_overnight_permits' ] ],
            [ 'rsyi-violations',   __( 'المخالفات', 'rsyi-sa' ),       'rsyi_view_all_violations',  [ __CLASS__, 'page_violations' ] ],
            [ 'rsyi-expulsion',    __( 'فصل طالب', 'rsyi-sa' ),         'rsyi_manage_expulsion',     [ __CLASS__, 'page_expulsion' ] ],
            [ 'rsyi-cohorts',      __( 'الدفعة', 'rsyi-sa' ),            'rsyi_manage_cohorts',       [ __CLASS__, 'page_cohorts' ] ],
            [ 'rsyi-daily-report', __( 'التقرير اليومي PDF', 'rsyi-sa' ), 'rsyi_print_daily_report', [ __CLASS__, 'page_daily_report' ] ],
            [ 'rsyi-audit',        __( 'سجل الأحداث', 'rsyi-sa' ),     'rsyi_view_audit_log',       [ __CLASS__, 'page_audit_log' ] ],
            [ 'rsyi-settings',     __( 'الإعدادات', 'rsyi-sa' ),       'rsyi_manage_settings',      [ __CLASS__, 'page_settings' ] ],
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
        echo '<div class="wrap rsyi-admin-wrap" dir="rtl">';
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
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        $institute_name = sanitize_text_field( wp_unslash( $_POST['rsyi_institute_name'] ?? '' ) );

        if ( empty( $institute_name ) ) {
            wp_send_json_error( [ 'message' => __( 'اسم المعهد لا يمكن أن يكون فارغاً.', 'rsyi-sa' ) ] );
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

        wp_send_json_success( [ 'message' => __( 'تم حفظ الإعدادات بنجاح.', 'rsyi-sa' ) ] );
    }

    public static function ajax_force_update_check(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );

        if ( ! current_user_can( 'rsyi_manage_settings' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        // Clear the cache so Updater::get_latest_release() re-fetches from GitHub
        delete_transient( 'rsyi_sa_update_cache' );

        // Force WordPress to re-check all plugin updates
        delete_site_transient( 'update_plugins' );
        wp_update_plugins();

        $cached = get_transient( 'rsyi_sa_update_cache' );
        if ( ! $cached ) {
            wp_send_json_error( [ 'message' => __( 'تعذّر الاتصال بـ GitHub API. تأكد من أن المستودع عام أو أدخل Token صحيح.', 'rsyi-sa' ) ] );
        }

        $latest_version = ltrim( $cached->tag_name ?? '', 'v' );
        if ( version_compare( RSYI_SA_VERSION, $latest_version, '<' ) ) {
            wp_send_json_success( [
                'message' => sprintf(
                    /* translators: 1: latest version */
                    __( 'يوجد تحديث جديد: الإصدار %s. توجه إلى صفحة الإضافات للتحديث.', 'rsyi-sa' ),
                    $latest_version
                ),
            ] );
        } else {
            wp_send_json_success( [
                'message' => sprintf(
                    /* translators: 1: current version */
                    __( 'النظام محدَّث. الإصدار الحالي %s هو الأحدث.', 'rsyi-sa' ),
                    RSYI_SA_VERSION
                ),
            ] );
        }
    }

    public static function ajax_reseed_violation_types(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );

        if ( ! current_user_can( 'rsyi_manage_settings' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
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
                __( 'تمت الإضافة بنجاح. أُضيف %1$d نوع جديد. الإجمالي: %2$d.', 'rsyi-sa' ),
                $added,
                $total
            ),
        ] );
    }
}
