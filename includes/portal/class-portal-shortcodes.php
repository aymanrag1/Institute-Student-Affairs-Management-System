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
 *   [rsyi_portal_evaluation]  – submit peer evaluations for cohort supervisors
 *
 * @package RSYI_StudentAffairs
 */

namespace RSYI_SA\Portal;

defined( 'ABSPATH' ) || exit;

class Shortcodes {

    public static function init(): void {
        $codes = [
            'rsyi_portal_dashboard'  => 'render_dashboard',
            'rsyi_portal_documents'  => 'render_documents',
            'rsyi_portal_requests'   => 'render_requests',
            'rsyi_portal_behavior'   => 'render_behavior',
            'rsyi_portal_register'   => 'render_register',
            'rsyi_portal_evaluation' => 'render_evaluation',
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
            return '<p>' . esc_html__( 'You are already logged in.', 'rsyi-sa' ) . '</p>';
        }
        $cohorts = \RSYI_SA\Modules\Cohorts::get_all_cohorts( true );
        return self::render_template( 'register', compact( 'cohorts' ) );
    }

    public static function render_evaluation( $atts ): string {
        $profile = self::require_student();
        if ( ! $profile ) {
            return '<p>' . esc_html__( 'Profile not found. Please contact administration.', 'rsyi-sa' ) . '</p>';
        }

        if ( $profile->status !== 'active' ) {
            return '<div class="rsyi-notice rsyi-notice-warning">'
                . esc_html__( 'Your account must be active to submit evaluations.', 'rsyi-sa' )
                . '</div>';
        }

        $user_id  = (int) $profile->user_id;
        $cohort_id = (int) $profile->cohort_id;

        // Active evaluation periods for this student's cohort
        global $wpdb;
        $p_table  = $wpdb->prefix . 'rsyi_evaluation_periods';
        $c_table  = $wpdb->prefix . 'rsyi_cohorts';
        $periods  = $wpdb->get_results( $wpdb->prepare(
            "SELECT ep.*, c.name AS cohort_name
             FROM {$p_table} ep
             LEFT JOIN {$c_table} c ON c.id = ep.cohort_id
             WHERE ep.cohort_id = %d AND ep.is_active = 1
             ORDER BY ep.created_at DESC",
            $cohort_id
        ) );

        // All active students in the cohort (potential evaluatees)
        $sp_table  = $wpdb->prefix . 'rsyi_student_profiles';
        $evaluatees = $wpdb->get_results( $wpdb->prepare(
            "SELECT sp.user_id, sp.english_full_name
             FROM {$sp_table} sp
             WHERE sp.cohort_id = %d AND sp.status = 'active' AND sp.user_id != %d
             ORDER BY sp.english_full_name ASC",
            $cohort_id,
            $user_id
        ) );

        // Already submitted peer evaluations (per period)
        $pe_table      = $wpdb->prefix . 'rsyi_peer_evaluations';
        $submitted_raw = $wpdb->get_results( $wpdb->prepare(
            "SELECT period_id, evaluatee_id, total FROM {$pe_table} WHERE evaluator_id = %d",
            $user_id
        ) );
        $submitted = [];
        foreach ( $submitted_raw as $s ) {
            $submitted[ (int) $s->period_id ][ (int) $s->evaluatee_id ] = (int) $s->total;
        }

        $peer_criteria = \RSYI_SA\Modules\Evaluations::get_peer_criteria();

        return self::render_template( 'evaluation', compact(
            'profile', 'periods', 'evaluatees', 'submitted', 'peer_criteria'
        ) );
    }
}
