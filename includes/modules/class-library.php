<?php
namespace RSYI_SA\Modules;

defined( 'ABSPATH' ) || exit;

class Library {

    static function init(): void {
        // Manage (trainer+)
        foreach ( [ 'save_book','delete_book','get_books','issue_book','return_book','get_issues','get_student_books' ] as $action ) {
            add_action( "wp_ajax_rsyi_{$action}", [ __CLASS__, "ajax_{$action}" ] );
        }
        // Student view
        add_action( 'wp_ajax_rsyi_get_available_books', [ __CLASS__, 'ajax_get_available_books' ] );
        add_action( 'wp_ajax_nopriv_rsyi_get_available_books', [ __CLASS__, 'ajax_get_available_books' ] );
    }

    // ── Save book (add / edit) ──────────────────────────────────────────────────
    static function ajax_save_book(): void {
        check_ajax_referer( 'rsyi_sa_admin', 'nonce' );
        if ( ! current_user_can( 'rsyi_manage_library' ) ) {
            wp_send_json_error( [ 'message' => 'غير مصرح / Unauthorized' ] );
        }
        global $wpdb;
        $table = $wpdb->prefix . 'rsyi_books';

        $id       = intval( $_POST['book_id'] ?? 0 );
        $data     = [
            'title_ar'        => sanitize_text_field( $_POST['title_ar'] ?? '' ),
            'title_en'        => sanitize_text_field( $_POST['title_en'] ?? '' ),
            'author'          => sanitize_text_field( $_POST['author'] ?? '' ),
            'category'        => sanitize_key( $_POST['category'] ?? 'general' ),
            'language'        => sanitize_text_field( $_POST['book_language'] ?? 'en' ),
            'isbn'            => sanitize_text_field( $_POST['isbn'] ?? '' ),
            'description'     => sanitize_textarea_field( $_POST['description'] ?? '' ),
            'cover_image_id'  => intval( $_POST['cover_image_id'] ?? 0 ),
            'total_copies'    => max( 1, intval( $_POST['total_copies'] ?? 1 ) ),
            'is_active'       => 1,
            'updated_at'      => current_time( 'mysql' ),
        ];

        if ( $id > 0 ) {
            $old = $wpdb->get_row( $wpdb->prepare( "SELECT total_copies, available_copies FROM {$table} WHERE id=%d", $id ) );
            if ( $old ) {
                $diff = $data['total_copies'] - $old->total_copies;
                $data['available_copies'] = max( 0, $old->available_copies + $diff );
            }
            $wpdb->update( $table, $data, [ 'id' => $id ] );
            wp_send_json_success( [ 'message' => 'تم التحديث / Updated', 'id' => $id ] );
        } else {
            $data['available_copies'] = $data['total_copies'];
            $data['added_by']         = get_current_user_id();
            $data['created_at']       = current_time( 'mysql' );
            $wpdb->insert( $table, $data );
            wp_send_json_success( [ 'message' => 'تمت الإضافة / Added', 'id' => $wpdb->insert_id ] );
        }
    }

    // ── Delete book ─────────────────────────────────────────────────────────────
    static function ajax_delete_book(): void {
        check_ajax_referer( 'rsyi_sa_admin', 'nonce' );
        if ( ! current_user_can( 'rsyi_manage_library' ) ) {
            wp_send_json_error( [ 'message' => 'غير مصرح / Unauthorized' ] );
        }
        global $wpdb;
        $id = intval( $_POST['book_id'] ?? 0 );
        if ( ! $id ) { wp_send_json_error( [ 'message' => 'معرف غير صالح / Invalid ID' ] ); }

        $active = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}rsyi_book_issues WHERE book_id=%d AND status='issued'", $id
        ) );
        if ( $active > 0 ) {
            wp_send_json_error( [ 'message' => 'الكتاب مُصرَف حالياً / Book is currently issued' ] );
        }
        $wpdb->update( $wpdb->prefix . 'rsyi_books', [ 'is_active' => 0 ], [ 'id' => $id ] );
        wp_send_json_success( [ 'message' => 'تم الحذف / Deleted' ] );
    }

    // ── Get books (admin) ────────────────────────────────────────────────────────
    static function ajax_get_books(): void {
        check_ajax_referer( 'rsyi_sa_admin', 'nonce' );
        if ( ! current_user_can( 'rsyi_manage_library' ) ) {
            wp_send_json_error( [ 'message' => 'غير مصرح / Unauthorized' ] );
        }
        global $wpdb;
        $table    = $wpdb->prefix . 'rsyi_books';
        $category = sanitize_key( $_POST['category'] ?? '' );
        $lang     = sanitize_text_field( $_POST['book_language'] ?? '' );
        $search   = sanitize_text_field( $_POST['search'] ?? '' );

        $where = [ 'is_active = 1' ];
        $vals  = [];
        if ( $category ) { $where[] = 'category = %s'; $vals[] = $category; }
        if ( $lang )      { $where[] = 'language = %s'; $vals[] = $lang; }
        if ( $search )    { $where[] = '(title_ar LIKE %s OR title_en LIKE %s OR author LIKE %s)';
                            $like = '%' . $wpdb->esc_like( $search ) . '%';
                            $vals = array_merge( $vals, [ $like, $like, $like ] ); }

        $sql = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY created_at DESC';
        $books = empty( $vals ) ? $wpdb->get_results( $sql ) : $wpdb->get_results( $wpdb->prepare( $sql, ...$vals ) );

        foreach ( $books as &$b ) {
            $b->cover_url = $b->cover_image_id ? wp_get_attachment_url( $b->cover_image_id ) : '';
        }
        wp_send_json_success( $books );
    }

    // ── Issue book ───────────────────────────────────────────────────────────────
    static function ajax_issue_book(): void {
        check_ajax_referer( 'rsyi_sa_admin', 'nonce' );
        if ( ! current_user_can( 'rsyi_manage_library' ) ) {
            wp_send_json_error( [ 'message' => 'غير مصرح / Unauthorized' ] );
        }
        global $wpdb;
        $book_id    = intval( $_POST['book_id'] ?? 0 );
        $student_id = intval( $_POST['student_id'] ?? 0 );
        $due_date   = sanitize_text_field( $_POST['due_date'] ?? '' );

        if ( ! $book_id || ! $student_id || ! $due_date ) {
            wp_send_json_error( [ 'message' => 'بيانات غير مكتملة / Incomplete data' ] );
        }

        $book = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rsyi_books WHERE id=%d AND is_active=1", $book_id
        ) );
        if ( ! $book )                     { wp_send_json_error( [ 'message' => 'الكتاب غير موجود / Book not found' ] ); }
        if ( $book->available_copies < 1 ) { wp_send_json_error( [ 'message' => 'لا توجد نسخ متاحة / No copies available' ] ); }

        // Check student doesn't already have this book
        $already = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}rsyi_book_issues WHERE book_id=%d AND student_id=%d AND status='issued'",
            $book_id, $student_id
        ) );
        if ( $already ) { wp_send_json_error( [ 'message' => 'الطالب يمتلك هذا الكتاب بالفعل / Student already has this book' ] ); }

        $wpdb->insert( $wpdb->prefix . 'rsyi_book_issues', [
            'book_id'    => $book_id,
            'student_id' => $student_id,
            'issued_by'  => get_current_user_id(),
            'issued_at'  => current_time( 'mysql' ),
            'due_date'   => $due_date,
            'status'     => 'issued',
            'created_at' => current_time( 'mysql' ),
            'updated_at' => current_time( 'mysql' ),
        ] );
        $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->prefix}rsyi_books SET available_copies = available_copies - 1, updated_at=%s WHERE id=%d",
            current_time( 'mysql' ), $book_id
        ) );
        wp_send_json_success( [ 'message' => 'تم الصرف / Book issued' ] );
    }

    // ── Return book ──────────────────────────────────────────────────────────────
    static function ajax_return_book(): void {
        check_ajax_referer( 'rsyi_sa_admin', 'nonce' );
        if ( ! current_user_can( 'rsyi_manage_library' ) ) {
            wp_send_json_error( [ 'message' => 'غير مصرح / Unauthorized' ] );
        }
        global $wpdb;
        $issue_id = intval( $_POST['issue_id'] ?? 0 );
        $notes    = sanitize_textarea_field( $_POST['return_notes'] ?? '' );
        if ( ! $issue_id ) { wp_send_json_error( [ 'message' => 'معرف غير صالح / Invalid ID' ] ); }

        $issue = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rsyi_book_issues WHERE id=%d AND status='issued'", $issue_id
        ) );
        if ( ! $issue ) { wp_send_json_error( [ 'message' => 'السجل غير موجود / Record not found' ] ); }

        $wpdb->update( $wpdb->prefix . 'rsyi_book_issues', [
            'status'       => 'returned',
            'returned_at'  => current_time( 'mysql' ),
            'return_notes' => $notes,
            'updated_at'   => current_time( 'mysql' ),
        ], [ 'id' => $issue_id ] );

        $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->prefix}rsyi_books SET available_copies = available_copies + 1, updated_at=%s WHERE id=%d",
            current_time( 'mysql' ), $issue->book_id
        ) );
        wp_send_json_success( [ 'message' => 'تمت الإعادة / Book returned' ] );
    }

    // ── Get issue log (admin) ────────────────────────────────────────────────────
    static function ajax_get_issues(): void {
        check_ajax_referer( 'rsyi_sa_admin', 'nonce' );
        if ( ! current_user_can( 'rsyi_manage_library' ) ) {
            wp_send_json_error( [ 'message' => 'غير مصرح / Unauthorized' ] );
        }
        global $wpdb;
        $status = sanitize_key( $_POST['status'] ?? '' );
        $where  = [];
        $vals   = [];
        if ( $status ) { $where[] = 'i.status=%s'; $vals[] = $status; }
        $cond = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';

        $sql = "SELECT i.*, b.title_ar, b.title_en,
                       p.student_name_ar, p.student_id_number,
                       u.display_name AS issued_by_name
                FROM {$wpdb->prefix}rsyi_book_issues i
                LEFT JOIN {$wpdb->prefix}rsyi_books b ON b.id = i.book_id
                LEFT JOIN {$wpdb->prefix}rsyi_student_profiles p ON p.id = i.student_id
                LEFT JOIN {$wpdb->users} u ON u.ID = i.issued_by
                {$cond}
                ORDER BY i.issued_at DESC LIMIT 500";

        $rows = empty( $vals ) ? $wpdb->get_results( $sql ) : $wpdb->get_results( $wpdb->prepare( $sql, ...$vals ) );

        // Mark overdue
        $now = current_time( 'Y-m-d' );
        foreach ( $rows as $r ) {
            if ( $r->status === 'issued' && $r->due_date < $now ) {
                $r->status = 'overdue';
                $wpdb->update( $wpdb->prefix . 'rsyi_book_issues', [ 'status' => 'overdue' ], [ 'id' => $r->id ] );
            }
        }
        wp_send_json_success( $rows );
    }

    // ── Get books for one student ────────────────────────────────────────────────
    static function ajax_get_student_books(): void {
        check_ajax_referer( 'rsyi_sa_admin', 'nonce' );
        if ( ! current_user_can( 'rsyi_manage_library' ) ) {
            wp_send_json_error( [ 'message' => 'غير مصرح / Unauthorized' ] );
        }
        global $wpdb;
        $student_id = intval( $_POST['student_id'] ?? 0 );
        if ( ! $student_id ) { wp_send_json_error( [ 'message' => 'معرف غير صالح / Invalid ID' ] ); }

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT i.*, b.title_ar, b.title_en FROM {$wpdb->prefix}rsyi_book_issues i
             LEFT JOIN {$wpdb->prefix}rsyi_books b ON b.id = i.book_id
             WHERE i.student_id=%d ORDER BY i.issued_at DESC", $student_id
        ) );
        wp_send_json_success( $rows );
    }

    // ── Available books (portal / student) ───────────────────────────────────────
    static function ajax_get_available_books(): void {
        if ( ! is_user_logged_in() ) { wp_send_json_error(); }
        global $wpdb;
        $books = $wpdb->get_results(
            "SELECT id, title_ar, title_en, author, category, language, isbn, description, cover_image_id, available_copies, total_copies
             FROM {$wpdb->prefix}rsyi_books WHERE is_active=1 ORDER BY category, title_en"
        );
        foreach ( $books as &$b ) {
            $b->cover_url = $b->cover_image_id ? wp_get_attachment_url( $b->cover_image_id ) : '';
        }
        wp_send_json_success( $books );
    }

    // ── Helpers ──────────────────────────────────────────────────────────────────
    static function get_available_books(): array {
        global $wpdb;
        $books = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}rsyi_books WHERE is_active=1 ORDER BY category, title_en"
        );
        foreach ( $books as &$b ) {
            $b->cover_url = $b->cover_image_id ? wp_get_attachment_url( $b->cover_image_id ) : '';
        }
        return $books ?: [];
    }

    static function get_student_issues( int $student_id ): array {
        global $wpdb;
        $now  = current_time( 'Y-m-d' );
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT i.*, b.title_ar, b.title_en, b.author, b.cover_image_id
             FROM {$wpdb->prefix}rsyi_book_issues i
             LEFT JOIN {$wpdb->prefix}rsyi_books b ON b.id=i.book_id
             WHERE i.student_id=%d AND i.status IN ('issued','overdue')
             ORDER BY i.issued_at DESC", $student_id
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
