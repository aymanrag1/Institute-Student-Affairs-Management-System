<?php
/**
 * PDF Generator
 *
 * Uses a bundled minimal HTML-to-PDF approach via mPDF (recommended) or
 * falls back to a pure-PHP FPDF/TCPDF shim. The generated PDFs are saved
 * in the secure uploads directory and a signed download URL is returned.
 *
 * To use mPDF: `composer require mpdf/mpdf` inside the plugin folder.
 * The class degrades gracefully if mPDF is absent (HTML file saved instead).
 *
 * @package RSYI_StudentAffairs
 */

namespace RSYI_SA;

defined( 'ABSPATH' ) || exit;

class PDF_Generator {

    private string $pdf_dir;

    public function __construct() {
        $this->pdf_dir = RSYI_SA_UPLOAD_DIR . '/pdfs';
        wp_mkdir_p( $this->pdf_dir );
    }

    // ── Daily Aggregated Report ───────────────────────────────────────────────

    /**
     * Generate daily exit + overnight report PDF for a given date / cohort.
     *
     * @param string $date      Y-m-d
     * @param int    $cohort_id 0 = all cohorts
     * @return string  Signed download URL
     */
    public function generate_daily_report( string $date, int $cohort_id = 0 ): string {
        $data = \RSYI_SA\Modules\Requests::get_daily_permits( $date, $cohort_id );

        ob_start();
        include RSYI_SA_PLUGIN_DIR . 'templates/pdf/daily-report.php';
        $html = ob_get_clean();

        $filename = 'daily-report-' . $date . ( $cohort_id ? '-cohort' . $cohort_id : '' ) . '-' . time() . '.pdf';
        $filepath = $this->pdf_dir . '/' . $filename;

        $this->render_pdf( $html, $filepath, 'daily-report', $date );

        // Store a pseudo doc record so Secure_Download can serve it
        return $this->store_and_get_url( $filepath, $filename, 'daily_report_' . $date );
    }

    // ── Expulsion Letter ─────────────────────────────────────────────────────

    /**
     * Generate expulsion letter PDF for a given case.
     *
     * @param int    $case_id
     * @param object $profile  wp_rsyi_student_profiles row
     * @return string  Absolute file path (stored in expulsion_cases.letter_path)
     */
    public function generate_expulsion_letter( int $case_id, object $profile ): string {
        ob_start();
        include RSYI_SA_PLUGIN_DIR . 'templates/pdf/expulsion-letter.php';
        $html = ob_get_clean();

        $filename = 'expulsion-letter-case' . $case_id . '-' . time() . '.pdf';
        $filepath = $this->pdf_dir . '/' . $filename;

        $this->render_pdf( $html, $filepath, 'expulsion-letter', (string) $case_id );

        return $this->store_and_get_url( $filepath, $filename, 'expulsion_letter_case_' . $case_id );
    }

    // ── Core render ──────────────────────────────────────────────────────────

    /**
     * Render HTML to PDF. Tries mPDF first, then TCPDF, then saves as HTML.
     */
    private function render_pdf( string $html, string $filepath, string $type, string $ref ): void {
        $autoload = RSYI_SA_PLUGIN_DIR . 'vendor/autoload.php';

        if ( file_exists( $autoload ) ) {
            require_once $autoload;

            // mPDF
            if ( class_exists( '\Mpdf\Mpdf' ) ) {
                try {
                    $mpdf = new \Mpdf\Mpdf( [
                        'mode'        => 'utf-8',
                        'format'      => 'A4',
                        'orientation' => 'P',
                        'default_font'=> 'dejavusans',
                        'tempDir'     => sys_get_temp_dir(),
                    ] );
                    $mpdf->SetDirectionality( 'rtl' );
                    $mpdf->WriteHTML( $html );
                    $mpdf->Output( $filepath, 'F' );
                    return;
                } catch ( \Exception $e ) {
                    error_log( 'RSYI_SA mPDF error: ' . $e->getMessage() );
                }
            }

            // TCPDF
            if ( class_exists( '\TCPDF' ) ) {
                try {
                    $pdf = new \TCPDF( 'P', 'mm', 'A4', true, 'UTF-8' );
                    $pdf->SetCreator( 'RSYI Student Affairs' );
                    $pdf->SetTitle( $type );
                    $pdf->AddPage();
                    $pdf->writeHTML( $html, true, false, true, false, '' );
                    $pdf->Output( $filepath, 'F' );
                    return;
                } catch ( \Exception $e ) {
                    error_log( 'RSYI_SA TCPDF error: ' . $e->getMessage() );
                }
            }
        }

        // Fallback: save styled HTML (browsers can print-to-PDF)
        $html_path = str_replace( '.pdf', '.html', $filepath );
        file_put_contents( $html_path, $html );
        // Copy to expected .pdf path so downstream code always finds it
        file_put_contents( $filepath, $html );
    }

    /**
     * Insert a record in wp_rsyi_documents (as a system file) so the
     * Secure_Download handler can serve it, and return a signed URL.
     */
    private function store_and_get_url( string $abs_path, string $filename, string $doc_type ): string {
        global $wpdb;

        $relative = 'pdfs/' . $filename;
        $size     = file_exists( $abs_path ) ? filesize( $abs_path ) : 0;

        // Check if already stored
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}rsyi_documents WHERE file_path = %s", $relative
        ) );

        if ( $existing ) {
            return Secure_Download::get_url( (int) $existing );
        }

        // Use student_id = 0 as system-generated marker
        $wpdb->insert(
            $wpdb->prefix . 'rsyi_documents',
            [
                'student_id'   => 0,
                'doc_type'     => sanitize_key( $doc_type ),
                'file_path'    => $relative,
                'file_name_orig'=> $filename,
                'file_size'    => $size,
                'mime_type'    => 'application/pdf',
                'status'       => 'approved',
                'uploaded_by'  => get_current_user_id(),
            ],
            [ '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%d' ]
        );
        $doc_id = (int) $wpdb->insert_id;

        return Secure_Download::get_url( $doc_id );
    }

    // ── AJAX handler for dorm supervisor ─────────────────────────────────────

    public static function init_ajax(): void {
        add_action( 'wp_ajax_rsyi_generate_daily_report', [ __CLASS__, 'ajax_daily_report' ] );
    }

    public static function ajax_daily_report(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );
        if ( ! current_user_can( 'rsyi_print_daily_report' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        $date      = sanitize_text_field( wp_unslash( $_POST['date']      ?? date( 'Y-m-d' ) ) );
        $cohort_id = (int) ( $_POST['cohort_id'] ?? 0 );

        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
            wp_send_json_error( [ 'message' => __( 'تنسيق التاريخ غير صالح.', 'rsyi-sa' ) ] );
        }

        $gen = new self();
        $url = $gen->generate_daily_report( $date, $cohort_id );

        Audit_Log::log( 'daily_report', 0, 'generate', [ 'date' => $date, 'cohort_id' => $cohort_id ] );

        wp_send_json_success( [ 'url' => $url, 'message' => __( 'تم إنشاء التقرير.', 'rsyi-sa' ) ] );
    }
}

