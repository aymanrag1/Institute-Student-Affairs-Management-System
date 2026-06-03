<?php
namespace RSYI_SA\Modules;

defined( 'ABSPATH' ) || exit;

class Library_Reports {

    static function init(): void {
        $actions = [
            'rsyi_lib_report_stock',
            'rsyi_lib_report_movement',
            'rsyi_lib_report_low_stock',
            'rsyi_lib_report_zero_stock',
            'rsyi_lib_report_cohort_withdrawal',
            'rsyi_lib_report_supplier',
        ];
        foreach ( $actions as $a ) {
            add_action( "wp_ajax_{$a}", [ __CLASS__, 'ajax_dispatch' ] );
        }
    }

    static function ajax_dispatch(): void {
        check_ajax_referer( 'rsyi_sa_admin', 'nonce' );
        if ( ! current_user_can( 'rsyi_lib_view_warehouse' ) ) {
            wp_send_json_error( [ 'message' => 'غير مصرح / Unauthorized' ] );
        }
        $action = sanitize_key( $_POST['action'] ?? '' );
        $method = 'handle_' . substr( $action, strlen( 'rsyi_lib_' ) );
        if ( method_exists( __CLASS__, $method ) ) {
            static::$method();
        } else {
            wp_send_json_error( [ 'message' => 'Unknown report' ] );
        }
    }

    // ── Current Stock Report ─────────────────────────────────────────────────
    static function handle_report_stock(): void {
        global $wpdb;
        Library_Transactions::sync_all_stocks();
        $category  = sanitize_key( $_POST['category'] ?? '' );
        $where     = $category ? $wpdb->prepare( 'AND category=%s', $category ) : '';
        $rows = $wpdb->get_results(
            "SELECT b.id, b.title_ar, b.title_en, b.subject, b.grade_level,
                    b.isbn, b.category, b.current_stock, b.min_stock, b.price,
                    CASE WHEN b.current_stock=0 THEN 'zero'
                         WHEN b.current_stock<=b.min_stock AND b.min_stock>0 THEN 'low'
                         ELSE 'ok' END AS stock_status
             FROM {$wpdb->prefix}rsyi_books b
             WHERE b.is_active=1 {$where}
             ORDER BY b.category, b.title_ar"
        );
        wp_send_json_success( $rows ?: [] );
    }

    // ── Book Movement Report (running balance) ───────────────────────────────
    static function handle_report_movement(): void {
        global $wpdb;
        $book_id    = intval( $_POST['book_id'] ?? 0 );
        $date_from  = sanitize_text_field( $_POST['date_from'] ?? '' );
        $date_to    = sanitize_text_field( $_POST['date_to'] ?? '' );

        if ( ! $book_id ) { wp_send_json_error( [ 'message' => 'حدد الكتاب / Select book' ] ); }

        $where  = 'WHERE t.book_id=%d';
        $params = [ $book_id ];
        if ( $date_from ) { $where .= ' AND DATE(t.created_at) >= %s'; $params[] = $date_from; }
        if ( $date_to )   { $where .= ' AND DATE(t.created_at) <= %s'; $params[] = $date_to; }

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT t.*, b.title_ar, b.title_en, u.display_name AS created_by_name
             FROM {$wpdb->prefix}rsyi_lib_transactions t
             LEFT JOIN {$wpdb->prefix}rsyi_books b ON b.id=t.book_id
             LEFT JOIN {$wpdb->users} u ON u.ID=t.created_by
             {$where} ORDER BY t.created_at ASC", ...$params
        ) );

        // Calculate running balance
        $running = 0;
        foreach ( $rows as $r ) {
            $running += $r->quantity;
            $r->running_balance = $running;
        }
        wp_send_json_success( $rows ?: [] );
    }

    // ── Low Stock Report ─────────────────────────────────────────────────────
    static function handle_report_low_stock(): void {
        global $wpdb;
        Library_Transactions::sync_all_stocks();
        $rows = $wpdb->get_results(
            "SELECT id, title_ar, title_en, subject, grade_level, current_stock, min_stock,
                    (min_stock - current_stock) AS shortage
             FROM {$wpdb->prefix}rsyi_books
             WHERE is_active=1 AND current_stock < min_stock AND min_stock > 0
             ORDER BY shortage DESC"
        );
        wp_send_json_success( $rows ?: [] );
    }

    // ── Zero Stock Report ────────────────────────────────────────────────────
    static function handle_report_zero_stock(): void {
        global $wpdb;
        Library_Transactions::sync_all_stocks();
        $rows = $wpdb->get_results(
            "SELECT id, title_ar, title_en, subject, grade_level, category
             FROM {$wpdb->prefix}rsyi_books
             WHERE is_active=1 AND current_stock=0
             ORDER BY title_ar"
        );
        wp_send_json_success( $rows ?: [] );
    }

    // ── Cohort / Group Withdrawal Report ─────────────────────────────────────
    static function handle_report_cohort_withdrawal(): void {
        global $wpdb;
        $cohort_id = intval( $_POST['cohort_id'] ?? 0 );
        $date_from = sanitize_text_field( $_POST['date_from'] ?? '' );
        $date_to   = sanitize_text_field( $_POST['date_to'] ?? '' );

        $where  = 'WHERE 1=1';
        $params = [];
        if ( $cohort_id ) { $where .= ' AND o.cohort_id=%d'; $params[] = $cohort_id; }
        if ( $date_from ) { $where .= ' AND DATE(o.created_at) >= %s'; $params[] = $date_from; }
        if ( $date_to )   { $where .= ' AND DATE(o.created_at) <= %s'; $params[] = $date_to; }
        $where .= " AND o.status='completed'";

        $sql = "SELECT o.order_number, o.created_at, c.name AS cohort_name,
                       b.title_ar, b.title_en, i.quantity
                FROM {$wpdb->prefix}rsyi_lib_withdrawal_orders o
                JOIN {$wpdb->prefix}rsyi_lib_withdrawal_order_items i ON i.order_id=o.id
                JOIN {$wpdb->prefix}rsyi_books b ON b.id=i.book_id
                LEFT JOIN {$wpdb->prefix}rsyi_cohorts c ON c.id=o.cohort_id
                {$where}
                ORDER BY o.created_at DESC, b.title_ar";

        $rows = empty( $params ) ? $wpdb->get_results( $sql ) : $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) );
        wp_send_json_success( $rows ?: [] );
    }

    // ── Supplier Report ──────────────────────────────────────────────────────
    static function handle_report_supplier(): void {
        global $wpdb;
        $supplier_id = intval( $_POST['supplier_id'] ?? 0 );
        $where  = $supplier_id ? $wpdb->prepare( 'AND o.supplier_id=%d', $supplier_id ) : '';
        $rows = $wpdb->get_results(
            "SELECT o.order_number, o.created_at, o.total_quantity, o.total_value,
                    o.tax_enabled, o.tax_rate,
                    s.name AS supplier_name
             FROM {$wpdb->prefix}rsyi_lib_add_orders o
             LEFT JOIN {$wpdb->prefix}rsyi_lib_suppliers s ON s.id=o.supplier_id
             WHERE 1=1 {$where}
             ORDER BY o.created_at DESC LIMIT 200"
        );
        wp_send_json_success( $rows ?: [] );
    }
}
