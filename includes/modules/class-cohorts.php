<?php
/**
 * Cohorts Module
 *
 * Manages cohort CRUD and the transfer request workflow.
 * Cohort assignment on a student profile is IMMUTABLE except via a
 * Dean-approved Transfer Request, which is logged in wp_rsyi_cohort_transfers.
 *
 * Transfer workflow: Staff requests → Dean approves → system executes (updates cohort_id).
 *
 * @package RSYI_StudentAffairs
 */

namespace RSYI_SA\Modules;

use RSYI_SA\Audit_Log;
use RSYI_SA\Email_Notifications;

defined( 'ABSPATH' ) || exit;

class Cohorts {

    public static function init(): void {
        add_action( 'wp_ajax_rsyi_create_cohort',           [ __CLASS__, 'ajax_create_cohort' ] );
        add_action( 'wp_ajax_rsyi_update_cohort',           [ __CLASS__, 'ajax_update_cohort' ] );
        add_action( 'wp_ajax_rsyi_request_cohort_transfer', [ __CLASS__, 'ajax_request_transfer' ] );
        add_action( 'wp_ajax_rsyi_approve_cohort_transfer', [ __CLASS__, 'ajax_approve_transfer' ] );
        add_action( 'wp_ajax_rsyi_reject_cohort_transfer',  [ __CLASS__, 'ajax_reject_transfer' ] );
    }

    // ── Cohort CRUD ──────────────────────────────────────────────────────────

    public static function ajax_create_cohort(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );
        if ( ! current_user_can( 'rsyi_manage_cohorts' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        $name  = sanitize_text_field( wp_unslash( $_POST['name']  ?? '' ) );
        $code  = sanitize_key( $_POST['code']  ?? '' );
        $start = sanitize_text_field( wp_unslash( $_POST['start_date'] ?? '' ) );
        $end   = sanitize_text_field( wp_unslash( $_POST['end_date']   ?? '' ) );

        if ( empty( $name ) || empty( $code ) ) {
            wp_send_json_error( [ 'message' => __( 'الاسم والرمز مطلوبان.', 'rsyi-sa' ) ] );
        }

        global $wpdb;
        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}rsyi_cohorts WHERE code = %s", $code
        ) );
        if ( $exists ) {
            wp_send_json_error( [ 'message' => __( 'رمز الفوج موجود بالفعل.', 'rsyi-sa' ) ] );
        }

        $wpdb->insert(
            $wpdb->prefix . 'rsyi_cohorts',
            [ 'name' => $name, 'code' => $code, 'start_date' => $start ?: null, 'end_date' => $end ?: null ],
            [ '%s', '%s', '%s', '%s' ]
        );
        $cohort_id = (int) $wpdb->insert_id;

        Audit_Log::log( 'cohort', $cohort_id, 'create', [ 'name' => $name, 'code' => $code ] );
        wp_send_json_success( [ 'message' => __( 'تم إنشاء الفوج.', 'rsyi-sa' ), 'cohort_id' => $cohort_id ] );
    }

    public static function ajax_update_cohort(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );
        if ( ! current_user_can( 'rsyi_manage_cohorts' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        $cohort_id = (int) ( $_POST['cohort_id'] ?? 0 );
        $name      = sanitize_text_field( wp_unslash( $_POST['name']       ?? '' ) );
        $start     = sanitize_text_field( wp_unslash( $_POST['start_date'] ?? '' ) );
        $end       = sanitize_text_field( wp_unslash( $_POST['end_date']   ?? '' ) );
        $is_active = isset( $_POST['is_active'] ) ? (int) $_POST['is_active'] : 1;

        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'rsyi_cohorts',
            [ 'name' => $name, 'start_date' => $start ?: null, 'end_date' => $end ?: null, 'is_active' => $is_active ],
            [ 'id' => $cohort_id ],
            [ '%s', '%s', '%s', '%d' ],
            [ '%d' ]
        );

        Audit_Log::log( 'cohort', $cohort_id, 'update', [ 'name' => $name ] );
        wp_send_json_success( [ 'message' => __( 'تم تحديث الفوج.', 'rsyi-sa' ) ] );
    }

    // ── Transfer workflow ─────────────────────────────────────────────────────

    public static function ajax_request_transfer(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );
        if ( ! current_user_can( 'rsyi_manage_cohorts' ) && ! current_user_can( 'rsyi_edit_student' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        $student_id   = (int) ( $_POST['student_id']   ?? 0 );
        $to_cohort_id = (int) ( $_POST['to_cohort_id'] ?? 0 );
        $reason       = sanitize_textarea_field( wp_unslash( $_POST['reason'] ?? '' ) );

        $profile = Accounts::get_profile_by_id( $student_id );
        if ( ! $profile ) {
            wp_send_json_error( [ 'message' => __( 'الطالب غير موجود.', 'rsyi-sa' ) ] );
        }
        if ( (int) $profile->cohort_id === $to_cohort_id ) {
            wp_send_json_error( [ 'message' => __( 'الطالب مسجل بالفعل في هذا الفوج.', 'rsyi-sa' ) ] );
        }

        $to_cohort = self::get_cohort( $to_cohort_id );
        if ( ! $to_cohort ) {
            wp_send_json_error( [ 'message' => __( 'الفوج المستهدف غير موجود.', 'rsyi-sa' ) ] );
        }

        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'rsyi_cohort_transfers',
            [
                'student_id'    => $student_id,
                'from_cohort_id'=> (int) $profile->cohort_id,
                'to_cohort_id'  => $to_cohort_id,
                'reason'        => $reason,
                'requested_by'  => get_current_user_id(),
                'status'        => 'pending_dean',
            ],
            [ '%d', '%d', '%d', '%s', '%d', '%s' ]
        );
        $transfer_id = (int) $wpdb->insert_id;

        Audit_Log::log( 'cohort_transfer', $transfer_id, 'create', [
            'from' => $profile->cohort_id,
            'to'   => $to_cohort_id,
        ] );

        // Notify deans
        $deans = get_users( [ 'role' => 'rsyi_dean', 'fields' => 'ID' ] );
        // Simple email – reuse request_pending_approval template
        foreach ( $deans as $uid ) {
            Email_Notifications::request_pending_approval( 'cohort_transfer', $transfer_id, (int) $uid, [
                'from_datetime' => '',
                'to_datetime'   => '',
                'reason'        => $reason,
            ] );
        }

        wp_send_json_success( [ 'message' => __( 'تم إرسال طلب التحويل للعميد.', 'rsyi-sa' ), 'transfer_id' => $transfer_id ] );
    }

    public static function ajax_approve_transfer(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );
        if ( ! current_user_can( 'rsyi_approve_cohort_transfer' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية العميد مطلوبة.', 'rsyi-sa' ) ] );
        }

        $transfer_id = (int) ( $_POST['transfer_id'] ?? 0 );
        $notes       = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );

        global $wpdb;
        $transfer = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rsyi_cohort_transfers WHERE id = %d AND status = 'pending_dean'",
            $transfer_id
        ) );
        if ( ! $transfer ) {
            wp_send_json_error( [ 'message' => __( 'طلب التحويل غير موجود أو تمت معالجته.', 'rsyi-sa' ) ] );
        }

        $now = current_time( 'mysql', true );

        // Approve & execute: update transfer record
        $wpdb->update(
            $wpdb->prefix . 'rsyi_cohort_transfers',
            [
                'status'         => 'approved',
                'dean_id'        => get_current_user_id(),
                'dean_decided_at'=> $now,
                'dean_notes'     => $notes,
                'executed_at'    => $now,
            ],
            [ 'id' => $transfer_id ],
            [ '%s', '%d', '%s', '%s', '%s' ],
            [ '%d' ]
        );

        // Execute: update student profile cohort_id
        $wpdb->update(
            $wpdb->prefix . 'rsyi_student_profiles',
            [ 'cohort_id' => (int) $transfer->to_cohort_id ],
            [ 'id'        => (int) $transfer->student_id ],
            [ '%d' ], [ '%d' ]
        );

        Audit_Log::log( 'cohort_transfer', $transfer_id, 'approve_and_execute', [
            'from_cohort' => $transfer->from_cohort_id,
            'to_cohort'   => $transfer->to_cohort_id,
        ] );

        $profile    = Accounts::get_profile_by_id( (int) $transfer->student_id );
        $from_name  = self::get_cohort( (int) $transfer->from_cohort_id )->name ?? '';
        $to_name    = self::get_cohort( (int) $transfer->to_cohort_id   )->name ?? '';
        if ( $profile ) {
            Email_Notifications::cohort_transfer_approved( (int) $profile->user_id, $from_name, $to_name );
        }

        wp_send_json_success( [ 'message' => __( 'تمت الموافقة على التحويل وتنفيذه.', 'rsyi-sa' ) ] );
    }

    public static function ajax_reject_transfer(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );
        if ( ! current_user_can( 'rsyi_approve_cohort_transfer' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية العميد مطلوبة.', 'rsyi-sa' ) ] );
        }

        $transfer_id = (int) ( $_POST['transfer_id'] ?? 0 );
        $notes       = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'rsyi_cohort_transfers',
            [
                'status'          => 'rejected',
                'dean_id'         => get_current_user_id(),
                'dean_decided_at' => current_time( 'mysql', true ),
                'dean_notes'      => $notes,
            ],
            [ 'id' => $transfer_id ],
            [ '%s', '%d', '%s', '%s' ],
            [ '%d' ]
        );
        Audit_Log::log( 'cohort_transfer', $transfer_id, 'reject', [ 'notes' => $notes ] );
        wp_send_json_success( [ 'message' => __( 'تم رفض طلب التحويل.', 'rsyi-sa' ) ] );
    }

    // ── Query helpers ─────────────────────────────────────────────────────────

    public static function get_cohort( int $id ): ?object {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rsyi_cohorts WHERE id = %d", $id
        ) );
    }

    public static function get_all_cohorts( bool $active_only = false ): array {
        global $wpdb;
        $where = $active_only ? 'WHERE is_active = 1' : '';
        return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}rsyi_cohorts {$where} ORDER BY name" );
    }

    public static function get_transfer_history( int $student_id ): array {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT t.*, fc.name AS from_cohort_name, tc.name AS to_cohort_name, u.display_name AS requested_by_name
             FROM {$wpdb->prefix}rsyi_cohort_transfers t
             LEFT JOIN {$wpdb->prefix}rsyi_cohorts fc ON fc.id = t.from_cohort_id
             LEFT JOIN {$wpdb->prefix}rsyi_cohorts tc ON tc.id = t.to_cohort_id
             LEFT JOIN {$wpdb->users} u ON u.ID = t.requested_by
             WHERE t.student_id = %d ORDER BY t.created_at DESC",
            $student_id
        ) );
    }
}
