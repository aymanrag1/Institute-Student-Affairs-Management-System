<?php
/**
 * Documents Module
 *
 * Handles secure upload, status management (pending/approved/rejected),
 * and triggers student activation when all 8 mandatory docs are approved.
 *
 * @package RSYI_StudentAffairs
 */

namespace RSYI_SA\Modules;

use RSYI_SA\Audit_Log;
use RSYI_SA\Email_Notifications;
use RSYI_SA\Secure_Download;

defined( 'ABSPATH' ) || exit;

class Documents {

    /** Allowed MIME types for uploads */
    private const ALLOWED_MIME = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/pdf',
    ];

    /** Max file size in bytes (10 MB) */
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    public static function init(): void {
        add_action( 'wp_ajax_rsyi_upload_document',   [ __CLASS__, 'ajax_upload' ] );
        add_action( 'wp_ajax_rsyi_approve_document',  [ __CLASS__, 'ajax_approve' ] );
        add_action( 'wp_ajax_rsyi_reject_document',   [ __CLASS__, 'ajax_reject' ] );
        add_action( 'wp_ajax_rsyi_delete_document',   [ __CLASS__, 'ajax_delete' ] );
    }

    // ── Upload ───────────────────────────────────────────────────────────────

    public static function ajax_upload(): void {
        // Allow both portal nonce (student self-upload) and admin nonce (staff upload)
        $nonce_ok = wp_verify_nonce( $_POST['_nonce'] ?? '', 'rsyi_sa_portal' )
                 || wp_verify_nonce( $_POST['_nonce'] ?? '', 'rsyi_sa_admin' );
        if ( ! $nonce_ok ) {
            wp_send_json_error( [ 'message' => __( 'جلسة غير صالحة.', 'rsyi-sa' ) ] );
        }

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'message' => __( 'يجب تسجيل الدخول.', 'rsyi-sa' ) ] );
        }

        $doc_type   = sanitize_text_field( wp_unslash( $_POST['doc_type'] ?? '' ) );
        $student_id = (int) ( $_POST['student_id'] ?? 0 );

        if ( ! array_key_exists( $doc_type, Accounts::DOC_TYPE_LABELS ) ) {
            wp_send_json_error( [ 'message' => __( 'نوع الوثيقة غير صالح.', 'rsyi-sa' ) ] );
        }

        // Resolve student profile
        if ( current_user_can( 'rsyi_view_all_documents' ) && $student_id > 0 ) {
            $profile = Accounts::get_profile_by_id( $student_id );
        } else {
            $profile = Accounts::get_profile_by_user_id( get_current_user_id() );
            $student_id = $profile ? (int) $profile->id : 0;
        }

        if ( ! $profile ) {
            wp_send_json_error( [ 'message' => __( 'ملف الطالب غير موجود.', 'rsyi-sa' ) ] );
        }

        if ( ! current_user_can( 'rsyi_upload_own_documents' ) && ! current_user_can( 'rsyi_view_all_documents' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        // File validation
        if ( empty( $_FILES['document_file'] ) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK ) {
            wp_send_json_error( [ 'message' => __( 'خطأ في رفع الملف.', 'rsyi-sa' ) ] );
        }

        $file = $_FILES['document_file'];

        if ( $file['size'] > self::MAX_FILE_SIZE ) {
            wp_send_json_error( [ 'message' => __( 'حجم الملف يتجاوز الحد المسموح به (10 ميغابايت).', 'rsyi-sa' ) ] );
        }

        // Validate MIME via file content (not just extension)
        $finfo    = new \finfo( FILEINFO_MIME_TYPE );
        $detected = $finfo->file( $file['tmp_name'] );
        if ( ! in_array( $detected, self::ALLOWED_MIME, true ) ) {
            wp_send_json_error( [ 'message' => __( 'نوع الملف غير مسموح به. يُقبل: JPEG, PNG, WebP, PDF.', 'rsyi-sa' ) ] );
        }

        // Build unique storage path
        $ext       = self::mime_to_ext( $detected );
        $filename  = sprintf( '%d_%s_%s.%s', $student_id, $doc_type, wp_generate_password( 8, false ), $ext );
        $sub_dir   = sprintf( '%04d', $student_id ); // group files by student ID
        $upload_dir = RSYI_SA_UPLOAD_DIR . '/' . $sub_dir;
        wp_mkdir_p( $upload_dir );

        $dest = $upload_dir . '/' . $filename;
        if ( ! move_uploaded_file( $file['tmp_name'], $dest ) ) {
            wp_send_json_error( [ 'message' => __( 'فشل حفظ الملف.', 'rsyi-sa' ) ] );
        }

        global $wpdb;

        // If a previous copy exists for this doc_type, mark it superseded by deleting the DB record
        // (file stays on disk for audit; we overwrite the active record)
        $wpdb->delete(
            $wpdb->prefix . 'rsyi_documents',
            [ 'student_id' => $profile->id, 'doc_type' => $doc_type, 'status' => 'rejected' ],
            [ '%d', '%s', '%s' ]
        );

        $wpdb->insert(
            $wpdb->prefix . 'rsyi_documents',
            [
                'student_id'     => $profile->id,
                'doc_type'       => $doc_type,
                'file_path'      => $sub_dir . '/' . $filename,
                'file_name_orig' => sanitize_file_name( $file['name'] ),
                'file_size'      => $file['size'],
                'mime_type'      => $detected,
                'status'         => 'pending',
                'uploaded_by'    => get_current_user_id(),
            ],
            [ '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%d' ]
        );
        $doc_id = (int) $wpdb->insert_id;

        Audit_Log::log( 'document', $doc_id, 'upload', [
            'student_id' => $profile->id,
            'doc_type'   => $doc_type,
            'file'       => $filename,
        ] );

        wp_send_json_success( [
            'message'    => __( 'تم رفع الوثيقة بنجاح وهي قيد المراجعة.', 'rsyi-sa' ),
            'doc_id'     => $doc_id,
            'status'     => 'pending',
            'download_url' => Secure_Download::get_url( $doc_id ),
        ] );
    }

    // ── Approve ──────────────────────────────────────────────────────────────

    public static function ajax_approve(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );
        if ( ! current_user_can( 'rsyi_approve_document' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        $doc_id = (int) ( $_POST['doc_id'] ?? 0 );
        $doc    = self::get_document( $doc_id );
        if ( ! $doc ) {
            wp_send_json_error( [ 'message' => __( 'الوثيقة غير موجودة.', 'rsyi-sa' ) ] );
        }
        if ( $doc->status !== 'pending' ) {
            wp_send_json_error( [ 'message' => __( 'لا يمكن الموافقة على هذه الوثيقة في حالتها الحالية.', 'rsyi-sa' ) ] );
        }

        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'rsyi_documents',
            [
                'status'      => 'approved',
                'reviewed_by' => get_current_user_id(),
                'reviewed_at' => current_time( 'mysql', true ),
            ],
            [ 'id' => $doc_id ],
            [ '%s', '%d', '%s' ],
            [ '%d' ]
        );

        $profile = Accounts::get_profile_by_id( (int) $doc->student_id );
        if ( $profile ) {
            Email_Notifications::document_approved(
                (int) $profile->user_id,
                Accounts::DOC_TYPE_LABELS[ $doc->doc_type ] ?? $doc->doc_type
            );
            // Try to activate student
            Accounts::maybe_activate_student( (int) $doc->student_id );
        }

        Audit_Log::log( 'document', $doc_id, 'approve', [ 'doc_type' => $doc->doc_type ] );
        wp_send_json_success( [ 'message' => __( 'تمت الموافقة على الوثيقة.', 'rsyi-sa' ) ] );
    }

    // ── Reject ───────────────────────────────────────────────────────────────

    public static function ajax_reject(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );
        if ( ! current_user_can( 'rsyi_reject_document' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        $doc_id = (int) ( $_POST['doc_id'] ?? 0 );
        $reason = sanitize_textarea_field( wp_unslash( $_POST['rejection_reason'] ?? '' ) );
        if ( empty( $reason ) ) {
            wp_send_json_error( [ 'message' => __( 'يجب تحديد سبب الرفض.', 'rsyi-sa' ) ] );
        }

        $doc = self::get_document( $doc_id );
        if ( ! $doc || $doc->status !== 'pending' ) {
            wp_send_json_error( [ 'message' => __( 'الوثيقة غير موجودة أو ليست في حالة انتظار.', 'rsyi-sa' ) ] );
        }

        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'rsyi_documents',
            [
                'status'           => 'rejected',
                'rejection_reason' => $reason,
                'reviewed_by'      => get_current_user_id(),
                'reviewed_at'      => current_time( 'mysql', true ),
            ],
            [ 'id' => $doc_id ],
            [ '%s', '%s', '%d', '%s' ],
            [ '%d' ]
        );

        $profile = Accounts::get_profile_by_id( (int) $doc->student_id );
        if ( $profile ) {
            Email_Notifications::document_rejected(
                (int) $profile->user_id,
                Accounts::DOC_TYPE_LABELS[ $doc->doc_type ] ?? $doc->doc_type,
                $reason
            );
        }

        Audit_Log::log( 'document', $doc_id, 'reject', [
            'doc_type' => $doc->doc_type,
            'reason'   => $reason,
        ] );

        wp_send_json_success( [ 'message' => __( 'تم رفض الوثيقة.', 'rsyi-sa' ) ] );
    }

    // ── Delete ───────────────────────────────────────────────────────────────

    public static function ajax_delete(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );
        if ( ! current_user_can( 'rsyi_approve_document' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }
        $doc_id = (int) ( $_POST['doc_id'] ?? 0 );
        $doc = self::get_document( $doc_id );
        if ( ! $doc ) {
            wp_send_json_error( [ 'message' => __( 'الوثيقة غير موجودة.', 'rsyi-sa' ) ] );
        }
        // Only delete physical file if rejected (approved files kept for audit)
        if ( $doc->status === 'rejected' ) {
            $abs = RSYI_SA_UPLOAD_DIR . '/' . $doc->file_path;
            if ( file_exists( $abs ) ) {
                wp_delete_file( $abs );
            }
        }
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'rsyi_documents', [ 'id' => $doc_id ], [ '%d' ] );
        Audit_Log::log( 'document', $doc_id, 'delete', [ 'doc_type' => $doc->doc_type ] );
        wp_send_json_success( [ 'message' => __( 'تم حذف الوثيقة.', 'rsyi-sa' ) ] );
    }

    // ── Read helpers ─────────────────────────────────────────────────────────

    public static function get_document( int $id ): ?object {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}rsyi_documents WHERE id = %d",
                $id
            )
        );
    }

    public static function get_student_documents( int $student_profile_id ): array {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}rsyi_documents WHERE student_id = %d ORDER BY doc_type",
                $student_profile_id
            )
        );
    }

    /**
     * Returns an assoc array keyed by doc_type with the latest doc or null.
     */
    public static function get_student_documents_map( int $student_profile_id ): array {
        $docs = self::get_student_documents( $student_profile_id );
        $map  = [];
        foreach ( Accounts::MANDATORY_DOC_TYPES as $type ) {
            $map[ $type ] = null;
        }
        foreach ( $docs as $doc ) {
            $map[ $doc->doc_type ] = $doc;
        }
        return $map;
    }

    // ── Util ─────────────────────────────────────────────────────────────────

    private static function mime_to_ext( string $mime ): string {
        return match ( $mime ) {
            'image/jpeg'       => 'jpg',
            'image/png'        => 'png',
            'image/webp'       => 'webp',
            'application/pdf'  => 'pdf',
            default            => 'bin',
        };
    }
}
