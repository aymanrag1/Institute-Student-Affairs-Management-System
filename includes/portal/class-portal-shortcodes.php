<?php
/**
 * Student Portal Shortcodes
 *
 * Shortcode map:
 *   [rsyi_portal_dashboard]   – student home (status, warnings, pending ack)
 *   [rsyi_portal_documents]   – upload mandatory documents
 *   [rsyi_portal_requests]    – submit & view exit/overnight permits
 *   [rsyi_portal_behavior]    – view points, acknowledge warnings
 *   [rsyi_portal_register]    – self-registration form
 *
 * @package RSYI_StudentAffairs
 */

namespace RSYI_SA\Portal;

defined( 'ABSPATH' ) || exit;

class Shortcodes {

    public static function init(): void {
        $codes = [
            'rsyi_portal_dashboard' => 'render_dashboard',
            'rsyi_portal_documents' => 'render_documents',
            'rsyi_portal_requests'  => 'render_requests',
            'rsyi_portal_behavior'  => 'render_behavior',
            'rsyi_portal_register'  => 'render_register',
        ];
        foreach ( $codes as $tag => $method ) {
            add_shortcode( $tag, [ __CLASS__, $method ] );
        }
    }

    // ── Auth gate ─────────────────────────────────────────────────────────────

    private static function require_login(): bool {
        if ( is_user_logged_in() ) return true;
        wp_redirect( wp_login_url( get_permalink() ) );
        exit;
    }

    private static function require_student(): ?object {
        self::require_login();
        $profile = \RSYI_SA\Modules\Accounts::get_profile_by_user_id( get_current_user_id() );
        return $profile ?: null;
    }

    private static function render_template( string $name, array $vars = [] ): string {
        $file = RSYI_SA_PLUGIN_DIR . 'templates/portal/' . $name . '.php';
        if ( ! file_exists( $file ) ) return '';
        ob_start();
        extract( $vars, EXTR_SKIP ); // phpcs:ignore
        include $file;
        return ob_get_clean();
    }

    // ── Shortcode handlers ────────────────────────────────────────────────────

    public static function render_dashboard( $atts ): string {
        $profile = self::require_student();
        if ( ! $profile ) {
            return '<p>' . esc_html__( 'لم يتم العثور على ملفك الشخصي. يرجى التواصل مع الإدارة.', 'rsyi-sa' ) . '</p>';
        }

        $total_pts   = \RSYI_SA\Modules\Behavior::get_total_points( (int) $profile->id );
        $warnings    = \RSYI_SA\Modules\Behavior::get_pending_warnings_for_student( (int) $profile->id );
        $cohort      = \RSYI_SA\Modules\Cohorts::get_cohort( (int) $profile->cohort_id );

        return self::render_template( 'dashboard', compact( 'profile', 'total_pts', 'warnings', 'cohort' ) );
    }

    public static function render_documents( $atts ): string {
        $profile = self::require_student();
        if ( ! $profile ) return '';

        $doc_map = \RSYI_SA\Modules\Documents::get_student_documents_map( (int) $profile->id );
        $labels  = \RSYI_SA\Modules\Accounts::DOC_TYPE_LABELS;

        return self::render_template( 'documents', compact( 'profile', 'doc_map', 'labels' ) );
    }

    public static function render_requests( $atts ): string {
        $profile = self::require_student();
        if ( ! $profile ) return '';

        if ( $profile->status !== 'active' ) {
            return '<div class="rsyi-notice rsyi-notice-warning">'
                . esc_html__( 'يجب تفعيل حسابك أولاً (رفع جميع الوثائق المطلوبة).', 'rsyi-sa' )
                . '</div>';
        }

        $exit_permits      = \RSYI_SA\Modules\Requests::get_student_exit_permits( (int) $profile->id );
        $overnight_permits = \RSYI_SA\Modules\Requests::get_student_overnight_permits( (int) $profile->id );

        return self::render_template( 'requests', compact( 'profile', 'exit_permits', 'overnight_permits' ) );
    }

    public static function render_behavior( $atts ): string {
        $profile = self::require_student();
        if ( ! $profile ) return '';

        global $wpdb;
        $violations  = $wpdb->get_results( $wpdb->prepare(
            "SELECT v.*, vt.name_ar AS type_ar
             FROM {$wpdb->prefix}rsyi_violations v
             JOIN {$wpdb->prefix}rsyi_violation_types vt ON vt.id = v.violation_type_id
             WHERE v.student_id = %d AND v.status = 'active'
             ORDER BY v.incident_date DESC",
            $profile->id
        ) );
        $total_pts  = \RSYI_SA\Modules\Behavior::get_total_points( (int) $profile->id );
        $warnings   = \RSYI_SA\Modules\Behavior::get_student_warnings( (int) $profile->id );

        return self::render_template( 'behavior', compact( 'profile', 'violations', 'total_pts', 'warnings' ) );
    }

    public static function render_register( $atts ): string {
        if ( is_user_logged_in() ) {
            return '<p>' . esc_html__( 'أنت مسجل الدخول بالفعل.', 'rsyi-sa' ) . '</p>';
        }
        $cohorts = \RSYI_SA\Modules\Cohorts::get_all_cohorts( true );
        return self::render_template( 'register', compact( 'cohorts' ) );
    }
}
