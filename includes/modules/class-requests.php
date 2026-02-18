<?php
/**
 * Requests Module – Exit Permits & Overnight Permits
 *
 * Exit Permit Workflow:
 *   Student → Dorm Supervisor (step 1) → Student Affairs Manager (step 2) → Executed
 *
 * Overnight Permit Workflow:
 *   Student → Student Supervisor (step 1) → Student Affairs Manager (step 2) → Dean (step 3) → Executed
 *   (Dorm Supervisor can then print the daily aggregated PDF)
 *
 * @package RSYI_StudentAffairs
 */

namespace RSYI_SA\Modules;

use RSYI_SA\Audit_Log;
use RSYI_SA\Email_Notifications;

defined( 'ABSPATH' ) || exit;

class Requests {

    // ── Init ─────────────────────────────────────────────────────────────────

    public static function init(): void {
        // Exit permits
        add_action( 'wp_ajax_rsyi_submit_exit_permit',   [ __CLASS__, 'ajax_submit_exit' ] );
        add_action( 'wp_ajax_rsyi_approve_exit_permit',  [ __CLASS__, 'ajax_approve_exit' ] );
        add_action( 'wp_ajax_rsyi_reject_exit_permit',   [ __CLASS__, 'ajax_reject_exit' ] );
        add_action( 'wp_ajax_rsyi_execute_exit_permit',  [ __CLASS__, 'ajax_execute_exit' ] );
        // Overnight permits
        add_action( 'wp_ajax_rsyi_submit_overnight_permit',   [ __CLASS__, 'ajax_submit_overnight' ] );
        add_action( 'wp_ajax_rsyi_approve_overnight_permit',  [ __CLASS__, 'ajax_approve_overnight' ] );
        add_action( 'wp_ajax_rsyi_reject_overnight_permit',   [ __CLASS__, 'ajax_reject_overnight' ] );
        add_action( 'wp_ajax_rsyi_execute_overnight_permit',  [ __CLASS__, 'ajax_execute_overnight' ] );
    }

    // ══════════════════════════════════════════════════════════════════════════
    // EXIT PERMITS
    // ══════════════════════════════════════════════════════════════════════════

    public static function ajax_submit_exit(): void {
        check_ajax_referer( 'rsyi_sa_portal', '_nonce' );
        if ( ! current_user_can( 'rsyi_submit_exit_permit' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        $profile = Accounts::get_profile_by_user_id( get_current_user_id() );
        if ( ! $profile || $profile->status !== 'active' ) {
            wp_send_json_error( [ 'message' => __( 'يجب أن يكون حسابك مفعلاً لتقديم الطلبات.', 'rsyi-sa' ) ] );
        }

        $data   = self::sanitize_permit_input( $_POST );
        $errors = self::validate_permit_input( $data );
        if ( ! empty( $errors ) ) {
            wp_send_json_error( [ 'errors' => $errors ] );
        }

        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'rsyi_exit_permits',
            [
                'student_id'    => $profile->id,
                'from_datetime' => $data['from_datetime'],
                'to_datetime'   => $data['to_datetime'],
                'reason'        => $data['reason'],
                'status'        => 'pending_dorm',
            ],
            [ '%d', '%s', '%s', '%s', '%s' ]
        );
        $permit_id = (int) $wpdb->insert_id;

        Audit_Log::log( 'exit_permit', $permit_id, 'create', [ 'student_id' => $profile->id ] );

        // Notify all Dorm Supervisors
        self::notify_role_users( 'rsyi_dorm_supervisor', 'exit', $permit_id, $data );

        wp_send_json_success( [
            'message'   => __( 'تم تقديم طلب إذن الخروج بنجاح.', 'rsyi-sa' ),
            'permit_id' => $permit_id,
        ] );
    }

    public static function ajax_approve_exit(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );
        if ( ! current_user_can( 'rsyi_approve_exit_permit' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        $permit_id = (int) ( $_POST['permit_id'] ?? 0 );
        $notes     = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );
        $permit    = self::get_exit_permit( $permit_id );
        if ( ! $permit ) {
            wp_send_json_error( [ 'message' => __( 'الطلب غير موجود.', 'rsyi-sa' ) ] );
        }

        $user        = wp_get_current_user();
        $is_dorm     = $user->has_cap( 'rsyi_approve_exit_permit' ) && ! $user->has_cap( 'rsyi_approve_overnight_permit' );
        $is_manager  = $user->has_cap( 'rsyi_approve_exit_permit' )   && $user->has_cap( 'rsyi_approve_overnight_permit' );

        global $wpdb;
        $now = current_time( 'mysql', true );

        if ( $permit->status === 'pending_dorm' && ( $is_dorm || $is_manager ) ) {
            // Step 1 approved → move to step 2
            $wpdb->update(
                $wpdb->prefix . 'rsyi_exit_permits',
                [
                    'status'             => 'pending_manager',
                    'dorm_supervisor_id' => $user->ID,
                    'dorm_approved_at'   => $now,
                    'dorm_notes'         => $notes,
                ],
                [ 'id' => $permit_id ],
                [ '%s', '%d', '%s', '%s' ],
                [ '%d' ]
            );
            Audit_Log::log( 'exit_permit', $permit_id, 'approve_step1', [ 'notes' => $notes ] );
            // Notify SA Managers
            self::notify_role_users( 'rsyi_student_affairs_mgr', 'exit', $permit_id, (array) $permit );
            wp_send_json_success( [ 'message' => __( 'تمت الموافقة في الخطوة الأولى.', 'rsyi-sa' ) ] );

        } elseif ( $permit->status === 'pending_manager' && $is_manager ) {
            // Step 2 approved → fully approved
            $wpdb->update(
                $wpdb->prefix . 'rsyi_exit_permits',
                [
                    'status'              => 'approved',
                    'manager_id'          => $user->ID,
                    'manager_approved_at' => $now,
                    'manager_notes'       => $notes,
                ],
                [ 'id' => $permit_id ],
                [ '%s', '%d', '%s', '%s' ],
                [ '%d' ]
            );
            Audit_Log::log( 'exit_permit', $permit_id, 'approve_final', [ 'notes' => $notes ] );

            $profile = Accounts::get_profile_by_id( (int) $permit->student_id );
            if ( $profile ) {
                Email_Notifications::request_approved( 'exit', (int) $profile->user_id, $permit_id );
            }
            wp_send_json_success( [ 'message' => __( 'تمت الموافقة النهائية على إذن الخروج.', 'rsyi-sa' ) ] );

        } else {
            wp_send_json_error( [ 'message' => __( 'الحالة الحالية للطلب لا تسمح بهذه العملية.', 'rsyi-sa' ) ] );
        }
    }

    public static function ajax_reject_exit(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );
        if ( ! current_user_can( 'rsyi_reject_exit_permit' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        $permit_id = (int) ( $_POST['permit_id'] ?? 0 );
        $notes     = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );
        $permit    = self::get_exit_permit( $permit_id );
        if ( ! $permit || ! in_array( $permit->status, [ 'pending_dorm', 'pending_manager' ], true ) ) {
            wp_send_json_error( [ 'message' => __( 'الطلب غير موجود أو لا يمكن رفضه الآن.', 'rsyi-sa' ) ] );
        }

        global $wpdb;
        $now  = current_time( 'mysql', true );
        $user = wp_get_current_user();

        $update = [ 'status' => 'rejected' ];
        if ( $permit->status === 'pending_dorm' ) {
            $update['dorm_supervisor_id'] = $user->ID;
            $update['dorm_rejected_at']   = $now;
            $update['dorm_notes']         = $notes;
        } else {
            $update['manager_id']           = $user->ID;
            $update['manager_rejected_at']  = $now;
            $update['manager_notes']        = $notes;
        }
        $wpdb->update( $wpdb->prefix . 'rsyi_exit_permits', $update, [ 'id' => $permit_id ] );
        Audit_Log::log( 'exit_permit', $permit_id, 'reject', [ 'step' => $permit->status, 'notes' => $notes ] );

        $profile = Accounts::get_profile_by_id( (int) $permit->student_id );
        if ( $profile ) {
            Email_Notifications::request_rejected( 'exit', (int) $profile->user_id, $permit_id, $notes );
        }
        wp_send_json_success( [ 'message' => __( 'تم رفض الطلب وإغلاقه.', 'rsyi-sa' ) ] );
    }

    public static function ajax_execute_exit(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );
        if ( ! current_user_can( 'rsyi_approve_exit_permit' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }
        $permit_id = (int) ( $_POST['permit_id'] ?? 0 );
        $permit    = self::get_exit_permit( $permit_id );
        if ( ! $permit || $permit->status !== 'approved' ) {
            wp_send_json_error( [ 'message' => __( 'يمكن تنفيذ الأذونات الموافق عليها فقط.', 'rsyi-sa' ) ] );
        }
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'rsyi_exit_permits',
            [ 'status' => 'executed', 'executed_by' => get_current_user_id(), 'executed_at' => current_time( 'mysql', true ) ],
            [ 'id' => $permit_id ],
            [ '%s', '%d', '%s' ],
            [ '%d' ]
        );
        Audit_Log::log( 'exit_permit', $permit_id, 'execute' );
        wp_send_json_success( [ 'message' => __( 'تم تنفيذ إذن الخروج.', 'rsyi-sa' ) ] );
    }

    // ══════════════════════════════════════════════════════════════════════════
    // OVERNIGHT PERMITS
    // ══════════════════════════════════════════════════════════════════════════

    public static function ajax_submit_overnight(): void {
        check_ajax_referer( 'rsyi_sa_portal', '_nonce' );
        if ( ! current_user_can( 'rsyi_submit_overnight_permit' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        $profile = Accounts::get_profile_by_user_id( get_current_user_id() );
        if ( ! $profile || $profile->status !== 'active' ) {
            wp_send_json_error( [ 'message' => __( 'يجب أن يكون حسابك مفعلاً.', 'rsyi-sa' ) ] );
        }

        $data   = self::sanitize_permit_input( $_POST );
        $errors = self::validate_permit_input( $data );
        if ( ! empty( $errors ) ) {
            wp_send_json_error( [ 'errors' => $errors ] );
        }

        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'rsyi_overnight_permits',
            [
                'student_id'    => $profile->id,
                'from_datetime' => $data['from_datetime'],
                'to_datetime'   => $data['to_datetime'],
                'reason'        => $data['reason'],
                'status'        => 'pending_supervisor',
            ],
            [ '%d', '%s', '%s', '%s', '%s' ]
        );
        $permit_id = (int) $wpdb->insert_id;

        Audit_Log::log( 'overnight_permit', $permit_id, 'create', [ 'student_id' => $profile->id ] );
        self::notify_role_users( 'rsyi_student_supervisor', 'overnight', $permit_id, $data );

        wp_send_json_success( [
            'message'   => __( 'تم تقديم طلب إذن المبيت بنجاح.', 'rsyi-sa' ),
            'permit_id' => $permit_id,
        ] );
    }

    public static function ajax_approve_overnight(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );
        if ( ! current_user_can( 'rsyi_approve_overnight_permit' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        $permit_id = (int) ( $_POST['permit_id'] ?? 0 );
        $notes     = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );
        $permit    = self::get_overnight_permit( $permit_id );
        if ( ! $permit ) {
            wp_send_json_error( [ 'message' => __( 'الطلب غير موجود.', 'rsyi-sa' ) ] );
        }

        $user = wp_get_current_user();
        $now  = current_time( 'mysql', true );
        global $wpdb;
        $table = $wpdb->prefix . 'rsyi_overnight_permits';

        switch ( $permit->status ) {
            case 'pending_supervisor':
                if ( ! $user->has_cap( 'rsyi_approve_overnight_permit' ) ) break;
                $wpdb->update( $table, [
                    'status'                => 'pending_manager',
                    'supervisor_id'         => $user->ID,
                    'supervisor_approved_at'=> $now,
                    'supervisor_notes'      => $notes,
                ], [ 'id' => $permit_id ] );
                Audit_Log::log( 'overnight_permit', $permit_id, 'approve_step1', [] );
                self::notify_role_users( 'rsyi_student_affairs_mgr', 'overnight', $permit_id, (array) $permit );
                wp_send_json_success( [ 'message' => __( 'تمت الموافقة في الخطوة الأولى (المشرف).', 'rsyi-sa' ) ] );
                return;

            case 'pending_manager':
                // SA Manager has rsyi_approve_overnight_permit but NOT rsyi_approve_expulsion
                if ( ! $user->has_cap( 'rsyi_approve_overnight_permit' ) ) break;
                $wpdb->update( $table, [
                    'status'              => 'pending_dean',
                    'manager_id'          => $user->ID,
                    'manager_approved_at' => $now,
                    'manager_notes'       => $notes,
                ], [ 'id' => $permit_id ] );
                Audit_Log::log( 'overnight_permit', $permit_id, 'approve_step2', [] );
                self::notify_role_users( 'rsyi_dean', 'overnight', $permit_id, (array) $permit );
                wp_send_json_success( [ 'message' => __( 'تمت الموافقة في الخطوة الثانية (المدير).', 'rsyi-sa' ) ] );
                return;

            case 'pending_dean':
                if ( ! $user->has_cap( 'rsyi_approve_expulsion' ) ) break; // dean-only cap
                $wpdb->update( $table, [
                    'status'          => 'approved',
                    'dean_id'         => $user->ID,
                    'dean_approved_at'=> $now,
                    'dean_notes'      => $notes,
                ], [ 'id' => $permit_id ] );
                Audit_Log::log( 'overnight_permit', $permit_id, 'approve_final', [] );
                $profile = Accounts::get_profile_by_id( (int) $permit->student_id );
                if ( $profile ) {
                    Email_Notifications::request_approved( 'overnight', (int) $profile->user_id, $permit_id );
                }
                wp_send_json_success( [ 'message' => __( 'تمت الموافقة النهائية على إذن المبيت.', 'rsyi-sa' ) ] );
                return;
        }
        wp_send_json_error( [ 'message' => __( 'الحالة الحالية لا تسمح بهذه العملية.', 'rsyi-sa' ) ] );
    }

    public static function ajax_reject_overnight(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );
        if ( ! current_user_can( 'rsyi_reject_overnight_permit' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        $permit_id = (int) ( $_POST['permit_id'] ?? 0 );
        $notes     = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );
        $permit    = self::get_overnight_permit( $permit_id );
        $valid     = in_array( $permit->status ?? '', [ 'pending_supervisor', 'pending_manager', 'pending_dean' ], true );
        if ( ! $permit || ! $valid ) {
            wp_send_json_error( [ 'message' => __( 'الطلب غير موجود أو لا يمكن رفضه الآن.', 'rsyi-sa' ) ] );
        }

        global $wpdb;
        $now  = current_time( 'mysql', true );
        $user = wp_get_current_user();
        $update = [ 'status' => 'rejected' ];
        if ( $permit->status === 'pending_supervisor' ) {
            $update += [ 'supervisor_id' => $user->ID, 'supervisor_rejected_at' => $now, 'supervisor_notes' => $notes ];
        } elseif ( $permit->status === 'pending_manager' ) {
            $update += [ 'manager_id' => $user->ID, 'manager_rejected_at' => $now, 'manager_notes' => $notes ];
        } else {
            $update += [ 'dean_id' => $user->ID, 'dean_rejected_at' => $now, 'dean_notes' => $notes ];
        }
        $wpdb->update( $wpdb->prefix . 'rsyi_overnight_permits', $update, [ 'id' => $permit_id ] );
        Audit_Log::log( 'overnight_permit', $permit_id, 'reject', [ 'step' => $permit->status ] );

        $profile = Accounts::get_profile_by_id( (int) $permit->student_id );
        if ( $profile ) {
            Email_Notifications::request_rejected( 'overnight', (int) $profile->user_id, $permit_id, $notes );
        }
        wp_send_json_success( [ 'message' => __( 'تم رفض الطلب وإغلاقه.', 'rsyi-sa' ) ] );
    }

    public static function ajax_execute_overnight(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );
        if ( ! current_user_can( 'rsyi_approve_exit_permit' ) ) { // Dorm Supervisor
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }
        $permit_id = (int) ( $_POST['permit_id'] ?? 0 );
        $permit    = self::get_overnight_permit( $permit_id );
        if ( ! $permit || $permit->status !== 'approved' ) {
            wp_send_json_error( [ 'message' => __( 'يمكن تنفيذ الأذونات الموافق عليها فقط.', 'rsyi-sa' ) ] );
        }
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'rsyi_overnight_permits',
            [ 'status' => 'executed', 'executed_by' => get_current_user_id(), 'executed_at' => current_time( 'mysql', true ) ],
            [ 'id' => $permit_id ]
        );
        Audit_Log::log( 'overnight_permit', $permit_id, 'execute' );
        wp_send_json_success( [ 'message' => __( 'تم تنفيذ إذن المبيت.', 'rsyi-sa' ) ] );
    }

    // ── Query helpers ─────────────────────────────────────────────────────────

    public static function get_exit_permit( int $id ): ?object {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rsyi_exit_permits WHERE id = %d", $id
        ) );
    }

    public static function get_overnight_permit( int $id ): ?object {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rsyi_overnight_permits WHERE id = %d", $id
        ) );
    }

    /**
     * Get approved exit + overnight permits for a given date (for daily PDF).
     */
    public static function get_daily_permits( string $date, int $cohort_id = 0 ): array {
        global $wpdb;
        $result = [ 'exit' => [], 'overnight' => [] ];

        $cohort_join  = '';
        $cohort_where = '';
        $params       = [];

        if ( $cohort_id > 0 ) {
            $cohort_join  = "JOIN {$wpdb->prefix}rsyi_student_profiles sp ON sp.id = ep.student_id";
            $cohort_where = 'AND sp.cohort_id = %d';
            $params[]     = $cohort_id;
        }

        // Exit permits active on $date
        $params_exit = array_merge( [ $date . ' 00:00:00', $date . ' 23:59:59' ], $params );
        $result['exit'] = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ep.*, u.display_name AS student_name, sp.arabic_full_name, sp.cohort_id, c.name AS cohort_name
                 FROM {$wpdb->prefix}rsyi_exit_permits ep
                 JOIN {$wpdb->prefix}rsyi_student_profiles sp ON sp.id = ep.student_id
                 JOIN {$wpdb->users} u ON u.ID = sp.user_id
                 LEFT JOIN {$wpdb->prefix}rsyi_cohorts c ON c.id = sp.cohort_id
                 WHERE ep.status IN ('approved','executed')
                   AND ep.from_datetime <= %s
                   AND ep.to_datetime  >= %s
                   {$cohort_where}
                 ORDER BY ep.from_datetime",
                ...$params_exit
            )
        );

        // Overnight permits active on $date
        $params_overnight = array_merge( [ $date . ' 00:00:00', $date . ' 23:59:59' ], $params );
        $result['overnight'] = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT op.*, u.display_name AS student_name, sp.arabic_full_name, sp.cohort_id, c.name AS cohort_name
                 FROM {$wpdb->prefix}rsyi_overnight_permits op
                 JOIN {$wpdb->prefix}rsyi_student_profiles sp ON sp.id = op.student_id
                 JOIN {$wpdb->users} u ON u.ID = sp.user_id
                 LEFT JOIN {$wpdb->prefix}rsyi_cohorts c ON c.id = sp.cohort_id
                 WHERE op.status IN ('approved','executed')
                   AND op.from_datetime <= %s
                   AND op.to_datetime  >= %s
                   {$cohort_where}
                 ORDER BY op.from_datetime",
                ...$params_overnight
            )
        );

        return $result;
    }

    public static function get_student_exit_permits( int $student_id ): array {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}rsyi_exit_permits WHERE student_id = %d ORDER BY created_at DESC",
                $student_id
            )
        );
    }

    public static function get_student_overnight_permits( int $student_id ): array {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}rsyi_overnight_permits WHERE student_id = %d ORDER BY created_at DESC",
                $student_id
            )
        );
    }

    // ── Input helpers ─────────────────────────────────────────────────────────

    private static function sanitize_permit_input( array $post ): array {
        return [
            'from_datetime' => sanitize_text_field( wp_unslash( $post['from_datetime'] ?? '' ) ),
            'to_datetime'   => sanitize_text_field( wp_unslash( $post['to_datetime']   ?? '' ) ),
            'reason'        => sanitize_textarea_field( wp_unslash( $post['reason']    ?? '' ) ),
        ];
    }

    private static function validate_permit_input( array $data ): array {
        $errors = [];
        if ( empty( $data['from_datetime'] ) ) $errors[] = __( 'تاريخ البداية مطلوب.',    'rsyi-sa' );
        if ( empty( $data['to_datetime'] ) )   $errors[] = __( 'تاريخ النهاية مطلوب.',    'rsyi-sa' );
        if ( empty( $data['reason'] ) )        $errors[] = __( 'سبب الطلب مطلوب.',         'rsyi-sa' );
        if ( ! empty( $data['from_datetime'] ) && ! empty( $data['to_datetime'] )
            && strtotime( $data['to_datetime'] ) <= strtotime( $data['from_datetime'] ) ) {
            $errors[] = __( 'يجب أن يكون تاريخ النهاية بعد تاريخ البداية.', 'rsyi-sa' );
        }
        return $errors;
    }

    // ── Notification helper ───────────────────────────────────────────────────

    /**
     * Find all users of a given role and email them about a pending request.
     */
    private static function notify_role_users( string $role, string $type, int $permit_id, array $data ): void {
        $users = get_users( [ 'role' => $role, 'fields' => 'ID' ] );
        foreach ( $users as $uid ) {
            Email_Notifications::request_pending_approval( $type, $permit_id, (int) $uid, $data );
        }
    }
}
