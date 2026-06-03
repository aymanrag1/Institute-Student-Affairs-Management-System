<?php
namespace RSYI_SA\Modules;

defined( 'ABSPATH' ) || exit;

/**
 * Library module coordinator — books CRUD + sub-module init.
 */
class Library {

    static function init(): void {
        // Sub-modules
        Library_Transactions::class; // autoloaded
        Library_Orders::init();
        Library_Reports::init();

        // Books CRUD
        $book_actions = [
            'rsyi_save_book', 'rsyi_delete_book', 'rsyi_get_books',
            'rsyi_get_available_books',
            // Legacy simple issue/return kept for portal
            'rsyi_issue_book', 'rsyi_return_book', 'rsyi_get_issues', 'rsyi_get_student_books',
        ];
        foreach ( $book_actions as $a ) {
            add_action( "wp_ajax_{$a}", [ __CLASS__, 'ajax_books_dispatch' ] );
        }
        add_action( 'wp_ajax_nopriv_rsyi_get_available_books', [ __CLASS__, 'ajax_get_available_books' ] );
    }

    static function ajax_books_dispatch(): void {
        $action = sanitize_key( $_POST['action'] ?? '' );
        $map = [
            'rsyi_save_book'          => 'ajax_save_book',
            'rsyi_delete_book'        => 'ajax_delete_book',
            'rsyi_get_books'          => 'ajax_get_books',
            'rsyi_get_available_books'=> 'ajax_get_available_books',
            'rsyi_issue_book'         => 'ajax_issue_book',
            'rsyi_return_book'        => 'ajax_return_book',
            'rsyi_get_issues'         => 'ajax_get_issues',
            'rsyi_get_student_books'  => 'ajax_get_student_books',
        ];
        $method = $map[ $action ] ?? null;
        if ( $method && method_exists( __CLASS__, $method ) ) {
            static::$method();
        } else {
            wp_send_json_error( [ 'message' => 'Unknown action' ] );
        }
    }

    // ── Save book (add / edit) ──────────────────────────────────────────────
    static function ajax_save_book(): void {
        check_ajax_referer( 'rsyi_sa_admin', 'nonce' );
        if ( ! current_user_can( 'rsyi_lib_manage_warehouse' ) && ! current_user_can( 'rsyi_manage_library' ) ) {
            wp_send_json_error( [ 'message' => 'غير مصرح / Unauthorized' ] );
        }
        global $wpdb;
        $table = $wpdb->prefix . 'rsyi_books';
        $id    = intval( $_POST['book_id'] ?? 0 );
        $data  = [
            'title_ar'       => sanitize_text_field( $_POST['title_ar'] ?? '' ),
            'title_en'       => sanitize_text_field( $_POST['title_en'] ?? '' ),
            'author'         => sanitize_text_field( $_POST['author'] ?? '' ),
            'subject'        => sanitize_text_field( $_POST['subject'] ?? '' ),
            'grade_level'    => sanitize_text_field( $_POST['grade_level'] ?? '' ),
            'publisher'      => sanitize_text_field( $_POST['publisher'] ?? '' ),
            'unit'           => sanitize_text_field( $_POST['unit'] ?? 'copy' ),
            'category'       => sanitize_key( $_POST['category'] ?? 'general' ),
            'language'       => sanitize_text_field( $_POST['book_language'] ?? 'en' ),
            'isbn'           => sanitize_text_field( $_POST['isbn'] ?? '' ),
            'description'    => sanitize_textarea_field( $_POST['description'] ?? '' ),
            'cover_image_id' => intval( $_POST['cover_image_id'] ?? 0 ),
            'min_stock'      => max( 0, intval( $_POST['min_stock'] ?? 0 ) ),
            'price'          => (float) ( $_POST['price'] ?? 0 ),
            'is_active'      => 1,
            'updated_at'     => current_time( 'mysql' ),
        ];
        if ( $id > 0 ) {
            $wpdb->update( $table, $data, [ 'id' => $id ] );
            wp_send_json_success( [ 'message' => 'تم التحديث / Updated', 'id' => $id ] );
        } else {
            $data['total_copies']     = 0;
            $data['available_copies'] = 0;
            $data['current_stock']    = 0;
            $data['added_by']         = get_current_user_id();
            $data['created_at']       = current_time( 'mysql' );
            $wpdb->insert( $table, $data );
            wp_send_json_success( [ 'message' => 'تمت الإضافة / Added', 'id' => $wpdb->insert_id ] );
        }
    }

    // ── Delete book ─────────────────────────────────────────────────────────
    static function ajax_delete_book(): void {
        check_ajax_referer( 'rsyi_sa_admin', 'nonce' );
        if ( ! current_user_can( 'rsyi_lib_manage_warehouse' ) ) { wp_send_json_error( [ 'message' => 'غير مصرح / Unauthorized' ] ); }
        global $wpdb;
        $id = intval( $_POST['book_id'] ?? 0 );
        if ( ! $id ) { wp_send_json_error( [ 'message' => 'معرف غير صالح / Invalid ID' ] ); }
        $has_active = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}rsyi_lib_withdrawal_orders o
             JOIN {$wpdb->prefix}rsyi_lib_withdrawal_order_items i ON i.order_id=o.id
             WHERE i.book_id=%d AND o.status IN ('pending','approved')", $id
        ) );
        if ( $has_active ) { wp_send_json_error( [ 'message' => 'الكتاب في إذن نشط / Book in active order' ] ); }
        $wpdb->update( $wpdb->prefix . 'rsyi_books', [ 'is_active' => 0 ], [ 'id' => $id ] );
        wp_send_json_success( [ 'message' => 'تم الحذف / Deleted' ] );
    }

    // ── Get books (admin) ────────────────────────────────────────────────────
    static function ajax_get_books(): void {
        check_ajax_referer( 'rsyi_sa_admin', 'nonce' );
        if ( ! current_user_can( 'rsyi_lib_view_warehouse' ) ) { wp_send_json_error( [ 'message' => 'غير مصرح / Unauthorized' ] ); }
        global $wpdb;
        $category = sanitize_key( $_POST['category'] ?? '' );
        $search   = sanitize_text_field( $_POST['search'] ?? '' );
        $where    = [ 'is_active = 1' ]; $vals = [];
        if ( $category ) { $where[] = 'category = %s'; $vals[] = $category; }
        if ( $search )   {
            $where[] = '(title_ar LIKE %s OR title_en LIKE %s OR isbn LIKE %s OR subject LIKE %s)';
            $like    = '%' . $wpdb->esc_like( $search ) . '%';
            $vals    = array_merge( $vals, [ $like, $like, $like, $like ] );
        }
        $sql   = "SELECT * FROM {$wpdb->prefix}rsyi_books WHERE " . implode( ' AND ', $where ) . ' ORDER BY title_ar';
        $books = empty( $vals ) ? $wpdb->get_results( $sql ) : $wpdb->get_results( $wpdb->prepare( $sql, ...$vals ) );
        foreach ( $books as &$b ) { $b->cover_url = $b->cover_image_id ? wp_get_attachment_url( $b->cover_image_id ) : ''; }
        wp_send_json_success( $books ?: [] );
    }

    // ── Available books (portal) ─────────────────────────────────────────────
    static function ajax_get_available_books(): void {
        if ( ! is_user_logged_in() ) { wp_send_json_error(); }
        global $wpdb;
        $books = $wpdb->get_results(
            "SELECT id, title_ar, title_en, author, category, language, isbn, description,
                    cover_image_id, current_stock, min_stock
             FROM {$wpdb->prefix}rsyi_books WHERE is_active=1 ORDER BY category, title_ar"
        );
        foreach ( $books as &$b ) { $b->cover_url = $b->cover_image_id ? wp_get_attachment_url( $b->cover_image_id ) : ''; }
        wp_send_json_success( $books ?: [] );
    }

    // ── Legacy simple issue/return (portal fallback) ─────────────────────────
    static function ajax_issue_book(): void {
        check_ajax_referer( 'rsyi_sa_admin', 'nonce' );
        if ( ! current_user_can( 'rsyi_lib_manage_warehouse' ) ) { wp_send_json_error( [ 'message' => 'غير مصرح / Unauthorized' ] ); }
        global $wpdb;
        $book_id    = intval( $_POST['book_id'] ?? 0 );
        $student_id = intval( $_POST['student_id'] ?? 0 );
        $due_date   = sanitize_text_field( $_POST['due_date'] ?? '' );
        if ( ! $book_id || ! $student_id || ! $due_date ) { wp_send_json_error( [ 'message' => 'بيانات غير مكتملة / Incomplete data' ] ); }
        $stock = Library_Transactions::get_real_stock( $book_id );
        if ( $stock < 1 ) { wp_send_json_error( [ 'message' => 'لا توجد نسخ متاحة / No copies available' ] ); }
        $wpdb->insert( $wpdb->prefix . 'rsyi_book_issues', [
            'book_id' => $book_id, 'student_id' => $student_id,
            'issued_by' => get_current_user_id(), 'issued_at' => current_time( 'mysql' ),
            'due_date' => $due_date, 'status' => 'issued',
            'created_at' => current_time( 'mysql' ), 'updated_at' => current_time( 'mysql' ),
        ] );
        Library_Transactions::record_withdrawal( $book_id, 1, 0, get_current_user_id() );
        wp_send_json_success( [ 'message' => 'تم الصرف / Issued' ] );
    }

    static function ajax_return_book(): void {
        check_ajax_referer( 'rsyi_sa_admin', 'nonce' );
        if ( ! current_user_can( 'rsyi_lib_manage_warehouse' ) ) { wp_send_json_error( [ 'message' => 'غير مصرح / Unauthorized' ] ); }
        global $wpdb;
        $issue_id = intval( $_POST['issue_id'] ?? 0 );
        $notes    = sanitize_textarea_field( $_POST['return_notes'] ?? '' );
        $issue    = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rsyi_book_issues WHERE id=%d AND status='issued'", $issue_id
        ) );
        if ( ! $issue ) { wp_send_json_error( [ 'message' => 'السجل غير موجود / Record not found' ] ); }
        $wpdb->update( $wpdb->prefix . 'rsyi_book_issues', [
            'status' => 'returned', 'returned_at' => current_time( 'mysql' ),
            'return_notes' => $notes, 'updated_at' => current_time( 'mysql' ),
        ], [ 'id' => $issue_id ] );
        Library_Transactions::record_return( (int)$issue->book_id, 1, 0, get_current_user_id() );
        wp_send_json_success( [ 'message' => 'تمت الإعادة / Returned' ] );
    }

    static function ajax_get_issues(): void {
        check_ajax_referer( 'rsyi_sa_admin', 'nonce' );
        if ( ! current_user_can( 'rsyi_lib_manage_warehouse' ) ) { wp_send_json_error( [ 'message' => 'غير مصرح / Unauthorized' ] ); }
        global $wpdb;
        $rows = $wpdb->get_results(
            "SELECT i.*, b.title_ar, b.title_en, p.student_name_ar, p.student_id_number, u.display_name AS issued_by_name
             FROM {$wpdb->prefix}rsyi_book_issues i
             LEFT JOIN {$wpdb->prefix}rsyi_books b ON b.id=i.book_id
             LEFT JOIN {$wpdb->prefix}rsyi_student_profiles p ON p.id=i.student_id
             LEFT JOIN {$wpdb->users} u ON u.ID=i.issued_by
             ORDER BY i.issued_at DESC LIMIT 200"
        );
        wp_send_json_success( $rows ?: [] );
    }

    static function ajax_get_student_books(): void {
        check_ajax_referer( 'rsyi_sa_admin', 'nonce' );
        if ( ! current_user_can( 'rsyi_lib_manage_warehouse' ) ) { wp_send_json_error( [ 'message' => 'غير مصرح / Unauthorized' ] ); }
        global $wpdb;
        $sid  = intval( $_POST['student_id'] ?? 0 );
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT i.*, b.title_ar, b.title_en FROM {$wpdb->prefix}rsyi_book_issues i
             LEFT JOIN {$wpdb->prefix}rsyi_books b ON b.id=i.book_id WHERE i.student_id=%d ORDER BY i.issued_at DESC", $sid
        ) );
        wp_send_json_success( $rows ?: [] );
    }

    // ── Helpers used by templates + portal ───────────────────────────────────
    static function get_available_books(): array {
        global $wpdb;
        $books = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}rsyi_books WHERE is_active=1 ORDER BY category, title_ar"
        );
        foreach ( $books as &$b ) { $b->cover_url = $b->cover_image_id ? wp_get_attachment_url( $b->cover_image_id ) : ''; }
        return $books ?: [];
    }

    static function get_student_issues( int $student_id ): array {
        global $wpdb;
        $now  = current_time( 'Y-m-d' );
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT i.*, b.title_ar, b.title_en, b.author, b.cover_image_id
             FROM {$wpdb->prefix}rsyi_book_issues i LEFT JOIN {$wpdb->prefix}rsyi_books b ON b.id=i.book_id
             WHERE i.student_id=%d AND i.status IN ('issued','overdue') ORDER BY i.issued_at DESC", $student_id
        ) );
        foreach ( $rows as $r ) {
            if ( $r->status === 'issued' && $r->due_date < $now ) { $r->status = 'overdue'; }
            $r->cover_url = $r->cover_image_id ? wp_get_attachment_url( $r->cover_image_id ) : '';
        }
        return $rows ?: [];
    }

    static function get_all_students(): array {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT id, student_name_ar, student_id_number FROM {$wpdb->prefix}rsyi_student_profiles WHERE status='active' ORDER BY student_name_ar"
        ) ?: [];
    }
}
