<?php
/**
 * Secure File Download Handler
 *
 * Documents live outside the webroot (in /uploads/rsyi-docs/ protected by .htaccess).
 * This handler authenticates the request, checks capability, then streams the file.
 *
 * URL pattern:  /?rsyi_download=1&doc_id=<ID>&_nonce=<nonce>
 *
 * @package RSYI_StudentAffairs
 */

namespace RSYI_SA;

defined( 'ABSPATH' ) || exit;

class Secure_Download {

    private const QUERY_VAR = 'rsyi_download';

    public static function init(): void {
        add_action( 'init',             [ __CLASS__, 'add_rewrite_rule' ] );
        add_filter( 'query_vars',       [ __CLASS__, 'add_query_vars' ] );
        add_action( 'template_redirect',[ __CLASS__, 'handle_request' ] );
    }

    public static function add_rewrite_rule(): void {
        add_rewrite_rule(
            '^rsyi-download/([0-9]+)/?$',
            'index.php?' . self::QUERY_VAR . '=$matches[1]',
            'top'
        );
    }

    public static function add_query_vars( array $vars ): array {
        $vars[] = self::QUERY_VAR;
        return $vars;
    }

    /**
     * Generate a signed download URL for a document.
     */
    public static function get_url( int $doc_id ): string {
        return add_query_arg(
            [
                self::QUERY_VAR => $doc_id,
                '_nonce'        => wp_create_nonce( 'rsyi_dl_' . $doc_id ),
            ],
            home_url( '/' )
        );
    }

    /**
     * Handle an incoming download request.
     */
    public static function handle_request(): void {
        $doc_id = (int) get_query_var( self::QUERY_VAR );
        if ( $doc_id <= 0 ) {
            return;
        }

        // Must be logged in
        if ( ! is_user_logged_in() ) {
            wp_die( esc_html__( 'يجب تسجيل الدخول للوصول إلى هذا الملف.', 'rsyi-sa' ), 403 );
        }

        // Verify nonce
        $nonce = isset( $_GET['_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'rsyi_dl_' . $doc_id ) ) {
            wp_die( esc_html__( 'رابط التنزيل غير صالح أو منتهي الصلاحية.', 'rsyi-sa' ), 403 );
        }

        // Load document record
        global $wpdb;
        $doc = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT d.*, p.user_id AS student_user_id
                 FROM {$wpdb->prefix}rsyi_documents d
                 JOIN {$wpdb->prefix}rsyi_student_profiles p ON p.id = d.student_id
                 WHERE d.id = %d",
                $doc_id
            )
        );

        if ( ! $doc ) {
            wp_die( esc_html__( 'الملف غير موجود.', 'rsyi-sa' ), 404 );
        }

        $current_user = wp_get_current_user();

        // Capability check:
        // - Student may only download their own documents
        // - Staff with rsyi_view_all_documents may download any
        $is_own       = ( (int) $doc->student_user_id === (int) $current_user->ID );
        $is_staff     = $current_user->has_cap( 'rsyi_view_all_documents' );

        if ( ! $is_own && ! $is_staff ) {
            wp_die( esc_html__( 'ليس لديك صلاحية للوصول إلى هذا الملف.', 'rsyi-sa' ), 403 );
        }

        // Build absolute path
        $abs_path = RSYI_SA_UPLOAD_DIR . '/' . ltrim( $doc->file_path, '/' );
        $abs_path = realpath( $abs_path );

        // Path traversal guard
        if ( ! $abs_path || strpos( $abs_path, realpath( RSYI_SA_UPLOAD_DIR ) ) !== 0 ) {
            wp_die( esc_html__( 'مسار الملف غير صالح.', 'rsyi-sa' ), 400 );
        }

        if ( ! is_readable( $abs_path ) ) {
            wp_die( esc_html__( 'الملف غير متاح حالياً.', 'rsyi-sa' ), 404 );
        }

        // Audit
        Audit_Log::log( 'document', $doc_id, 'download', [
            'student_id' => $doc->student_id,
            'doc_type'   => $doc->doc_type,
        ] );

        // Stream the file
        $mime = $doc->mime_type ?: mime_content_type( $abs_path ) ?: 'application/octet-stream';
        header( 'Content-Type: ' . $mime );
        header( 'Content-Disposition: inline; filename="' . rawurlencode( $doc->file_name_orig ) . '"' );
        header( 'Content-Length: ' . filesize( $abs_path ) );
        header( 'Cache-Control: private, no-cache, no-store' );
        header( 'X-Content-Type-Options: nosniff' );

        // Flush any output buffering
        if ( ob_get_level() ) {
            ob_end_clean();
        }
        readfile( $abs_path );
        exit;
    }
}
