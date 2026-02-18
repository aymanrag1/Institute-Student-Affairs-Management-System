<?php
/**
 * Behavior Module – Violations & Threshold Warnings
 *
 * Point assignment rules:
 *   - Student Supervisor  : max 10 pts per violation
 *   - Student Affairs Mgr : max 20 pts per violation
 *   - Dean                : max 30 pts per violation (also handles dean_discretion types)
 *
 * Thresholds: 10 → 20 → 30 → email + digital acknowledgment required
 *             40 → create Expulsion Case, email Dean
 *
 * @package RSYI_StudentAffairs
 */

namespace RSYI_SA\Modules;

use RSYI_SA\Audit_Log;
use RSYI_SA\Email_Notifications;
use RSYI_SA\Roles;

defined( 'ABSPATH' ) || exit;

class Behavior {

    const THRESHOLDS = [ 10, 20, 30, 40 ];

    public static function init(): void {
        add_action( 'wp_ajax_rsyi_create_violation',     [ __CLASS__, 'ajax_create_violation' ] );
        add_action( 'wp_ajax_rsyi_overturn_violation',   [ __CLASS__, 'ajax_overturn_violation' ] );
        add_action( 'wp_ajax_rsyi_acknowledge_warning',  [ __CLASS__, 'ajax_acknowledge_warning' ] );
        add_action( 'wp_ajax_rsyi_approve_expulsion',    [ __CLASS__, 'ajax_approve_expulsion' ] );
        add_action( 'wp_ajax_rsyi_reject_expulsion',     [ __CLASS__, 'ajax_reject_expulsion' ] );
    }

    // ── Create violation ─────────────────────────────────────────────────────

    public static function ajax_create_violation(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );
        if ( ! current_user_can( 'rsyi_create_violation' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        $student_id       = (int) ( $_POST['student_id']       ?? 0 );
        $violation_type_id= (int) ( $_POST['violation_type_id'] ?? 0 );
        $points_assigned  = (int) ( $_POST['points_assigned']   ?? 0 );
        $incident_date    = sanitize_text_field( wp_unslash( $_POST['incident_date'] ?? date( 'Y-m-d' ) ) );
        $description      = sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) );

        // Validate student
        $profile = Accounts::get_profile_by_id( $student_id );
        if ( ! $profile ) {
            wp_send_json_error( [ 'message' => __( 'الطالب غير موجود.', 'rsyi-sa' ) ] );
        }

        // Validate violation type
        $vtype = self::get_violation_type( $violation_type_id );
        if ( ! $vtype ) {
            wp_send_json_error( [ 'message' => __( 'نوع المخالفة غير موجود.', 'rsyi-sa' ) ] );
        }

        $current_user = wp_get_current_user();
        $max_pts      = Roles::get_max_violation_points( $current_user );

        // Require dean for requires_dean types
        if ( $vtype->requires_dean && ! $current_user->has_cap( 'rsyi_approve_expulsion' ) ) {
            wp_send_json_error( [ 'message' => __( 'هذا النوع من المخالفات يتطلب صلاحية العميد.', 'rsyi-sa' ) ] );
        }

        // Points boundary check
        $points_assigned = max( 1, min( $points_assigned, $vtype->max_points, $max_pts ) );

        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'rsyi_violations',
            [
                'student_id'        => $student_id,
                'violation_type_id' => $violation_type_id,
                'points_assigned'   => $points_assigned,
                'incident_date'     => $incident_date,
                'description'       => $description,
                'assigned_by'       => $current_user->ID,
                'dean_override'     => $vtype->is_dean_discretion ? 1 : 0,
            ],
            [ '%d', '%d', '%d', '%s', '%s', '%d', '%d' ]
        );
        $violation_id = (int) $wpdb->insert_id;

        Audit_Log::log( 'violation', $violation_id, 'create', [
            'student_id'   => $student_id,
            'type'         => $vtype->name_en,
            'points'       => $points_assigned,
        ] );

        // Run threshold checks
        self::check_thresholds( $student_id );

        wp_send_json_success( [
            'message'      => __( 'تم تسجيل المخالفة بنجاح.', 'rsyi-sa' ),
            'violation_id' => $violation_id,
            'total_points' => self::get_total_points( $student_id ),
        ] );
    }

    // ── Overturn ─────────────────────────────────────────────────────────────

    public static function ajax_overturn_violation(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );
        if ( ! current_user_can( 'rsyi_overturn_violation' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        $violation_id = (int) ( $_POST['violation_id'] ?? 0 );
        $reason       = sanitize_textarea_field( wp_unslash( $_POST['reason'] ?? '' ) );
        if ( empty( $reason ) ) {
            wp_send_json_error( [ 'message' => __( 'يجب تحديد سبب إلغاء المخالفة.', 'rsyi-sa' ) ] );
        }

        global $wpdb;
        $v = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rsyi_violations WHERE id = %d AND status = 'active'", $violation_id
        ) );
        if ( ! $v ) {
            wp_send_json_error( [ 'message' => __( 'المخالفة غير موجودة أو تم إلغاؤها مسبقاً.', 'rsyi-sa' ) ] );
        }

        $wpdb->update(
            $wpdb->prefix . 'rsyi_violations',
            [
                'status'           => 'overturned',
                'overturned_by'    => get_current_user_id(),
                'overturned_at'    => current_time( 'mysql', true ),
                'overturned_reason'=> $reason,
            ],
            [ 'id' => $violation_id ],
            [ '%s', '%d', '%s', '%s' ],
            [ '%d' ]
        );

        Audit_Log::log( 'violation', $violation_id, 'overturn', [ 'reason' => $reason ] );

        // Re-check thresholds after point reduction
        self::check_thresholds( (int) $v->student_id );

        wp_send_json_success( [
            'message'      => __( 'تم إلغاء المخالفة.', 'rsyi-sa' ),
            'total_points' => self::get_total_points( (int) $v->student_id ),
        ] );
    }

    // ── Threshold checks ─────────────────────────────────────────────────────

    /**
     * After any violation change, recalculate totals and fire threshold events.
     */
    public static function check_thresholds( int $student_id ): void {
        $total  = self::get_total_points( $student_id );
        $profile = Accounts::get_profile_by_id( $student_id );
        if ( ! $profile ) return;

        foreach ( self::THRESHOLDS as $threshold ) {
            if ( $total >= $threshold && ! self::warning_exists( $student_id, $threshold ) ) {
                self::create_warning( $student_id, $threshold, $total, (int) $profile->user_id );
            }
        }
    }

    private static function create_warning( int $student_id, int $threshold, int $total, int $user_id ): void {
        global $wpdb;

        $warning_id = $wpdb->insert(
            $wpdb->prefix . 'rsyi_behavior_warnings',
            [
                'student_id'             => $student_id,
                'threshold'              => $threshold,
                'total_points_at_warning'=> $total,
                'created_at'             => current_time( 'mysql', true ),
            ],
            [ '%d', '%d', '%d', '%s' ]
        );
        $warning_id = (int) $wpdb->insert_id;

        if ( $threshold === 40 ) {
            // Create expulsion case instead of just a warning
            self::create_expulsion_case( $student_id, $total, $user_id );
        } else {
            // Email student
            Email_Notifications::behavior_warning( $user_id, $threshold, $total );
            // Mark email sent
            $wpdb->update(
                $wpdb->prefix . 'rsyi_behavior_warnings',
                [ 'email_sent_at' => current_time( 'mysql', true ) ],
                [ 'id' => $warning_id ],
                [ '%s' ], [ '%d' ]
            );
            Audit_Log::log( 'behavior_warning', $warning_id, 'create', [
                'student_id' => $student_id,
                'threshold'  => $threshold,
            ] );
        }
    }

    private static function create_expulsion_case( int $student_id, int $total, int $student_user_id ): void {
        global $wpdb;

        // Guard: only one open case at a time
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}rsyi_expulsion_cases WHERE student_id = %d AND status = 'pending_dean'",
            $student_id
        ) );
        if ( $existing ) return;

        $wpdb->insert(
            $wpdb->prefix . 'rsyi_expulsion_cases',
            [
                'student_id'  => $student_id,
                'triggered_by'=> '40_points',
                'total_points'=> $total,
                'status'      => 'pending_dean',
            ],
            [ '%d', '%s', '%d', '%s' ]
        );
        $case_id = (int) $wpdb->insert_id;

        Audit_Log::log( 'expulsion_case', $case_id, 'create', [
            'student_id'  => $student_id,
            'total_points'=> $total,
        ] );

        // Email all Deans
        $deans = get_users( [ 'role' => 'rsyi_dean', 'fields' => 'ID' ] );
        foreach ( $deans as $dean_id ) {
            Email_Notifications::expulsion_case_created( $case_id, $student_user_id, (int) $dean_id, $total );
        }
    }

    // ── Expulsion approval / rejection ───────────────────────────────────────

    public static function ajax_approve_expulsion(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );
        if ( ! current_user_can( 'rsyi_approve_expulsion' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية العميد مطلوبة.', 'rsyi-sa' ) ] );
        }

        $case_id = (int) ( $_POST['case_id'] ?? 0 );
        $notes   = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );
        global $wpdb;
        $case = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rsyi_expulsion_cases WHERE id = %d AND status = 'pending_dean'", $case_id
        ) );
        if ( ! $case ) {
            wp_send_json_error( [ 'message' => __( 'القضية غير موجودة أو تمت معالجتها.', 'rsyi-sa' ) ] );
        }

        $now = current_time( 'mysql', true );
        $wpdb->update(
            $wpdb->prefix . 'rsyi_expulsion_cases',
            [
                'status'         => 'approved',
                'dean_id'        => get_current_user_id(),
                'dean_decided_at'=> $now,
                'dean_notes'     => $notes,
                'executed_at'    => $now,
            ],
            [ 'id' => $case_id ],
            [ '%s', '%d', '%s', '%s', '%s' ],
            [ '%d' ]
        );

        // Suspend student account
        $wpdb->update(
            $wpdb->prefix . 'rsyi_student_profiles',
            [ 'status' => 'expelled' ],
            [ 'id' => (int) $case->student_id ],
            [ '%s' ], [ '%d' ]
        );

        $profile = Accounts::get_profile_by_id( (int) $case->student_id );
        Audit_Log::log( 'expulsion_case', $case_id, 'approve_and_execute', [ 'notes' => $notes ] );

        // Generate expulsion letter PDF
        $letter_url = '';
        if ( $profile ) {
            $pdf_gen    = new \RSYI_SA\PDF_Generator();
            $letter_url = $pdf_gen->generate_expulsion_letter( $case_id, $profile );
            $wpdb->update(
                $wpdb->prefix . 'rsyi_expulsion_cases',
                [ 'letter_path' => $letter_url, 'letter_generated_at' => $now ],
                [ 'id' => $case_id ]
            );
        }

        // Collect staff to notify
        $notify = [];
        foreach ( [ 'rsyi_dorm_supervisor', 'rsyi_student_affairs_mgr' ] as $role ) {
            $users = get_users( [ 'role' => $role, 'fields' => 'ID' ] );
            foreach ( $users as $uid ) { $notify[] = (int) $uid; }
        }
        if ( $profile ) {
            Email_Notifications::expulsion_executed( $case_id, (int) $profile->user_id, $letter_url, $notify );
        }

        wp_send_json_success( [ 'message' => __( 'تم اعتماد قرار الطرد وتنفيذه.', 'rsyi-sa' ), 'letter_url' => $letter_url ] );
    }

    public static function ajax_reject_expulsion(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );
        if ( ! current_user_can( 'rsyi_approve_expulsion' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية العميد مطلوبة.', 'rsyi-sa' ) ] );
        }
        $case_id = (int) ( $_POST['case_id'] ?? 0 );
        $notes   = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'rsyi_expulsion_cases',
            [
                'status'          => 'rejected',
                'dean_id'         => get_current_user_id(),
                'dean_decided_at' => current_time( 'mysql', true ),
                'dean_notes'      => $notes,
            ],
            [ 'id' => $case_id ],
            [ '%s', '%d', '%s', '%s' ],
            [ '%d' ]
        );
        Audit_Log::log( 'expulsion_case', $case_id, 'reject', [ 'notes' => $notes ] );
        wp_send_json_success( [ 'message' => __( 'تم رفض قرار الطرد.', 'rsyi-sa' ) ] );
    }

    // ── Student acknowledgment ────────────────────────────────────────────────

    public static function ajax_acknowledge_warning(): void {
        check_ajax_referer( 'rsyi_sa_portal', '_nonce' );
        if ( ! current_user_can( 'rsyi_acknowledge_warning' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        $warning_id = (int) ( $_POST['warning_id'] ?? 0 );
        $profile    = Accounts::get_profile_by_user_id( get_current_user_id() );
        if ( ! $profile ) {
            wp_send_json_error( [ 'message' => __( 'الطالب غير موجود.', 'rsyi-sa' ) ] );
        }

        global $wpdb;
        $warning = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rsyi_behavior_warnings WHERE id = %d AND student_id = %d",
            $warning_id, $profile->id
        ) );
        if ( ! $warning ) {
            wp_send_json_error( [ 'message' => __( 'التحذير غير موجود.', 'rsyi-sa' ) ] );
        }
        if ( $warning->acknowledged_at ) {
            wp_send_json_error( [ 'message' => __( 'تم الإقرار بهذا التحذير مسبقاً.', 'rsyi-sa' ) ] );
        }

        $wpdb->update(
            $wpdb->prefix . 'rsyi_behavior_warnings',
            [
                'acknowledged_at' => current_time( 'mysql', true ),
                'ack_ip'          => self::get_ip(),
            ],
            [ 'id' => $warning_id ],
            [ '%s', '%s' ], [ '%d' ]
        );
        Audit_Log::log( 'behavior_warning', $warning_id, 'acknowledged' );
        wp_send_json_success( [ 'message' => __( 'تم الإقرار بالتحذير.', 'rsyi-sa' ) ] );
    }

    // ── Query helpers ─────────────────────────────────────────────────────────

    public static function get_total_points( int $student_id ): int {
        global $wpdb;
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COALESCE(SUM(points_assigned),0) FROM {$wpdb->prefix}rsyi_violations
                 WHERE student_id = %d AND status = 'active'",
                $student_id
            )
        );
    }

    public static function get_violation_type( int $id ): ?object {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rsyi_violation_types WHERE id = %d", $id
        ) );
    }

    public static function get_all_violation_types(): array {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}rsyi_violation_types WHERE is_active = 1 ORDER BY name_ar"
        );
    }

    public static function warning_exists( int $student_id, int $threshold ): bool {
        global $wpdb;
        return (bool) $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}rsyi_behavior_warnings WHERE student_id = %d AND threshold = %d",
            $student_id, $threshold
        ) );
    }

    public static function get_student_warnings( int $student_id ): array {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rsyi_behavior_warnings WHERE student_id = %d ORDER BY threshold",
            $student_id
        ) );
    }

    public static function get_pending_warnings_for_student( int $student_id ): array {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rsyi_behavior_warnings WHERE student_id = %d AND acknowledged_at IS NULL AND threshold < 40 ORDER BY threshold",
            $student_id
        ) );
    }

    private static function get_ip(): string {
        return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' ) );
    }
}
