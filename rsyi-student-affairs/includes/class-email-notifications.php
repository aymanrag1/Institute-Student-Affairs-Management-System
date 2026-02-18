<?php
/**
 * Email Notifications
 *
 * Centralised mailer for all system events.
 *
 * @package RSYI_StudentAffairs
 */

namespace RSYI_SA;

defined( 'ABSPATH' ) || exit;

class Email_Notifications {

    // ── Helpers ──────────────────────────────────────────────────────────────

    private static function get_template( string $template_name, array $vars = [] ): string {
        $file = RSYI_SA_PLUGIN_DIR . 'templates/email/' . $template_name . '.php';
        if ( ! file_exists( $file ) ) {
            return '';
        }
        ob_start();
        extract( $vars, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract
        include $file;
        return ob_get_clean();
    }

    private static function send( string $to, string $subject, string $body, array $extra_headers = [] ): bool {
        $headers = array_merge(
            [ 'Content-Type: text/html; charset=UTF-8' ],
            $extra_headers
        );
        return wp_mail( $to, $subject, $body, $headers );
    }

    // ── Document events ──────────────────────────────────────────────────────

    public static function document_approved( int $student_user_id, string $doc_type_label ): void {
        $user = get_userdata( $student_user_id );
        if ( ! $user ) return;

        $subject = __( 'تمت الموافقة على وثيقتك', 'rsyi-sa' );
        $body    = self::get_template( 'document-approved', [
            'display_name'   => $user->display_name,
            'doc_type_label' => $doc_type_label,
        ] );
        self::send( $user->user_email, $subject, $body );
    }

    public static function document_rejected( int $student_user_id, string $doc_type_label, string $reason ): void {
        $user = get_userdata( $student_user_id );
        if ( ! $user ) return;

        $subject = __( 'تم رفض وثيقتك – يرجى إعادة الرفع', 'rsyi-sa' );
        $body    = self::get_template( 'document-rejected', [
            'display_name'   => $user->display_name,
            'doc_type_label' => $doc_type_label,
            'reason'         => $reason,
        ] );
        self::send( $user->user_email, $subject, $body );
    }

    public static function all_documents_approved( int $student_user_id ): void {
        $user = get_userdata( $student_user_id );
        if ( ! $user ) return;

        $subject = __( 'تم تفعيل حسابك – Red Sea Yacht Institute', 'rsyi-sa' );
        $body    = self::get_template( 'account-activated', [
            'display_name' => $user->display_name,
            'portal_url'   => home_url( '/student-portal/' ),
        ] );
        self::send( $user->user_email, $subject, $body );
    }

    // ── Request workflow emails ───────────────────────────────────────────────

    /**
     * Notify next approver in the workflow chain.
     */
    public static function request_pending_approval(
        string $request_type,
        int    $request_id,
        int    $approver_user_id,
        array  $request_data
    ): void {
        $approver = get_userdata( $approver_user_id );
        if ( ! $approver ) return;

        $label   = $request_type === 'exit' ? __( 'إذن خروج', 'rsyi-sa' ) : __( 'إذن مبيت', 'rsyi-sa' );
        $subject = sprintf( __( 'طلب %s جديد بانتظار موافقتك', 'rsyi-sa' ), $label );
        $body    = self::get_template( 'request-pending', [
            'approver_name' => $approver->display_name,
            'request_type'  => $label,
            'request_id'    => $request_id,
            'from_datetime' => $request_data['from_datetime'] ?? '',
            'to_datetime'   => $request_data['to_datetime']   ?? '',
            'reason'        => $request_data['reason']        ?? '',
            'admin_url'     => admin_url( 'admin.php?page=rsyi-requests&id=' . $request_id ),
        ] );
        self::send( $approver->user_email, $subject, $body );
    }

    public static function request_approved( string $request_type, int $student_user_id, int $request_id ): void {
        $user  = get_userdata( $student_user_id );
        if ( ! $user ) return;
        $label = $request_type === 'exit' ? __( 'إذن الخروج', 'rsyi-sa' ) : __( 'إذن المبيت', 'rsyi-sa' );
        $subject = sprintf( __( 'تمت الموافقة على %s #%d', 'rsyi-sa' ), $label, $request_id );
        $body    = self::get_template( 'request-approved', [
            'display_name'  => $user->display_name,
            'request_type'  => $label,
            'request_id'    => $request_id,
            'portal_url'    => home_url( '/student-portal/requests/' ),
        ] );
        self::send( $user->user_email, $subject, $body );
    }

    public static function request_rejected( string $request_type, int $student_user_id, int $request_id, string $notes ): void {
        $user  = get_userdata( $student_user_id );
        if ( ! $user ) return;
        $label = $request_type === 'exit' ? __( 'إذن الخروج', 'rsyi-sa' ) : __( 'إذن المبيت', 'rsyi-sa' );
        $subject = sprintf( __( 'تم رفض %s #%d', 'rsyi-sa' ), $label, $request_id );
        $body    = self::get_template( 'request-rejected', [
            'display_name' => $user->display_name,
            'request_type' => $label,
            'request_id'   => $request_id,
            'notes'        => $notes,
        ] );
        self::send( $user->user_email, $subject, $body );
    }

    // ── Behavior / Warning emails ─────────────────────────────────────────────

    public static function behavior_warning( int $student_user_id, int $threshold, int $total_points ): void {
        $user = get_userdata( $student_user_id );
        if ( ! $user ) return;

        $subject = sprintf( __( 'تحذير سلوكي – وصلت إلى %d نقطة', 'rsyi-sa' ), $threshold );
        $body    = self::get_template( 'behavior-warning', [
            'display_name' => $user->display_name,
            'threshold'    => $threshold,
            'total_points' => $total_points,
            'portal_url'   => home_url( '/student-portal/behavior/' ),
        ] );
        self::send( $user->user_email, $subject, $body );
    }

    // ── Expulsion emails ─────────────────────────────────────────────────────

    public static function expulsion_case_created( int $case_id, int $student_user_id, int $dean_user_id, int $total_points ): void {
        $dean    = get_userdata( $dean_user_id );
        $student = get_userdata( $student_user_id );
        if ( ! $dean || ! $student ) return;

        $subject = __( 'حالة طرد جديدة تستوجب موافقتك', 'rsyi-sa' );
        $body    = self::get_template( 'expulsion-created', [
            'dean_name'    => $dean->display_name,
            'student_name' => $student->display_name,
            'total_points' => $total_points,
            'case_id'      => $case_id,
            'admin_url'    => admin_url( 'admin.php?page=rsyi-expulsion&id=' . $case_id ),
        ] );
        self::send( $dean->user_email, $subject, $body );
    }

    public static function expulsion_executed(
        int    $case_id,
        int    $student_user_id,
        string $letter_url,
        array  $notify_users   // [ dorm_supervisor_id, sa_manager_id, ... ]
    ): void {
        $student = get_userdata( $student_user_id );
        if ( ! $student ) return;

        // Email student
        $subject = __( 'قرار الطرد من المعهد', 'rsyi-sa' );
        $body    = self::get_template( 'expulsion-executed-student', [
            'display_name' => $student->display_name,
            'letter_url'   => $letter_url,
        ] );
        self::send( $student->user_email, $subject, $body );

        // Email staff
        foreach ( $notify_users as $uid ) {
            $staff = get_userdata( $uid );
            if ( ! $staff ) continue;
            $subject_staff = sprintf( __( 'تم تنفيذ قرار طرد الطالب %s', 'rsyi-sa' ), $student->display_name );
            $body_staff    = self::get_template( 'expulsion-executed-staff', [
                'staff_name'   => $staff->display_name,
                'student_name' => $student->display_name,
                'case_id'      => $case_id,
                'letter_url'   => $letter_url,
            ] );
            self::send( $staff->user_email, $subject_staff, $body_staff );
        }
    }

    // ── Cohort transfer ──────────────────────────────────────────────────────

    public static function cohort_transfer_approved( int $student_user_id, string $from_cohort, string $to_cohort ): void {
        $user = get_userdata( $student_user_id );
        if ( ! $user ) return;

        $subject = __( 'تمت الموافقة على طلب تحويل الفوج', 'rsyi-sa' );
        $body    = self::get_template( 'cohort-transfer-approved', [
            'display_name' => $user->display_name,
            'from_cohort'  => $from_cohort,
            'to_cohort'    => $to_cohort,
        ] );
        self::send( $user->user_email, $subject, $body );
    }
}
