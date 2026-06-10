<?php
namespace RSYI_SA\Modules;

defined( 'ABSPATH' ) || exit;

/**
 * Handles all order types:
 *  - Add Orders (إذن الاستلام)
 *  - Withdrawal Orders (إذن الصرف) with draft→pending→approved→completed workflow
 *  - Return Orders (رد الكتب)
 *  - Purchase Requests (طلبات الشراء)
 *  - Opening Balances (الأرصدة الافتتاحية)
 *  - Suppliers (الموردون)
 */
class Library_Orders {

    static function init(): void {
        $actions = [
            // Suppliers
            'rsyi_lib_save_supplier','rsyi_lib_delete_supplier','rsyi_lib_get_suppliers',
            // Add orders (receiving)
            'rsyi_lib_get_add_orders','rsyi_lib_save_add_order','rsyi_lib_delete_add_order','rsyi_lib_get_add_order','rsyi_lib_auto_pr',
            // Withdrawal orders
            'rsyi_lib_get_wd_orders','rsyi_lib_save_wd_order','rsyi_lib_submit_wd_order',
            'rsyi_lib_approve_wd_order','rsyi_lib_reject_wd_order','rsyi_lib_complete_wd_order',
            'rsyi_lib_delete_wd_order','rsyi_lib_get_wd_order',
            // Return orders
            'rsyi_lib_get_ret_orders','rsyi_lib_save_ret_order','rsyi_lib_complete_ret_order','rsyi_lib_delete_ret_order','rsyi_lib_get_ret_order',
            // Purchase requests
            'rsyi_lib_get_pr_list','rsyi_lib_save_pr','rsyi_lib_approve_pr','rsyi_lib_reject_pr',
            'rsyi_lib_convert_pr_to_add','rsyi_lib_get_pr','rsyi_lib_delete_pr','rsyi_lib_get_pr_print_data',
            // Opening balances
            'rsyi_lib_get_opening','rsyi_lib_save_opening',
            // Balance edit
            'rsyi_lib_edit_balance',
            // Print data
            'rsyi_lib_get_print_data',
            // Dashboard
            'rsyi_lib_get_dashboard',
        ];
        foreach ( $actions as $a ) {
            add_action( "wp_ajax_{$a}", [ __CLASS__, 'ajax_dispatch' ] );
        }
    }

    static function ajax_dispatch(): void {
        $action = sanitize_key( $_POST['action'] ?? $_GET['action'] ?? '' );
        $method = 'handle_' . substr( $action, strlen( 'rsyi_lib_' ) );
        if ( method_exists( __CLASS__, $method ) ) {
            static::$method();
        } else {
            wp_send_json_error( [ 'message' => 'Unknown action' ] );
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // SUPPLIERS
    // ═══════════════════════════════════════════════════════════════════════════

    static function handle_get_suppliers(): void {
        self::check_manage();
        global $wpdb;
        $rows = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}rsyi_lib_suppliers WHERE is_active=1 ORDER BY name" );
        wp_send_json_success( $rows ?: [] );
    }

    static function handle_save_supplier(): void {
        self::check_manage();
        global $wpdb;
        $id   = intval( $_POST['supplier_id'] ?? 0 );
        $data = [
            'name'       => sanitize_text_field( $_POST['name'] ?? '' ),
            'phone'      => sanitize_text_field( $_POST['phone'] ?? '' ),
            'email'      => sanitize_email( $_POST['email'] ?? '' ),
            'address'    => sanitize_textarea_field( $_POST['address'] ?? '' ),
            'notes'      => sanitize_textarea_field( $_POST['notes'] ?? '' ),
            'updated_at' => current_time( 'mysql' ),
        ];
        if ( ! $data['name'] ) { wp_send_json_error( [ 'message' => 'اسم المورد مطلوب / Supplier name required' ] ); }
        if ( $id ) {
            $wpdb->update( $wpdb->prefix . 'rsyi_lib_suppliers', $data, [ 'id' => $id ] );
            wp_send_json_success( [ 'message' => 'تم التحديث / Updated', 'id' => $id ] );
        } else {
            $data['created_at'] = current_time( 'mysql' );
            $wpdb->insert( $wpdb->prefix . 'rsyi_lib_suppliers', $data );
            wp_send_json_success( [ 'message' => 'تمت الإضافة / Added', 'id' => $wpdb->insert_id ] );
        }
    }

    static function handle_delete_supplier(): void {
        self::check_manage();
        global $wpdb;
        $id = intval( $_POST['supplier_id'] ?? 0 );
        $wpdb->update( $wpdb->prefix . 'rsyi_lib_suppliers', [ 'is_active' => 0 ], [ 'id' => $id ] );
        wp_send_json_success( [ 'message' => 'تم الحذف / Deleted' ] );
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // ADD ORDERS (RECEIVING)
    // ═══════════════════════════════════════════════════════════════════════════

    static function handle_get_add_orders(): void {
        self::check_manage();
        global $wpdb;
        $rows = $wpdb->get_results(
            "SELECT o.*, s.name AS supplier_name, u.display_name AS created_by_name
             FROM {$wpdb->prefix}rsyi_lib_add_orders o
             LEFT JOIN {$wpdb->prefix}rsyi_lib_suppliers s ON s.id = o.supplier_id
             LEFT JOIN {$wpdb->users} u ON u.ID = o.created_by
             ORDER BY o.created_at DESC LIMIT 200"
        );
        wp_send_json_success( $rows ?: [] );
    }

    static function handle_get_add_order(): void {
        self::check_manage();
        global $wpdb;
        $id   = intval( $_POST['order_id'] ?? 0 );
        $order = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}rsyi_lib_add_orders WHERE id=%d", $id ) );
        if ( ! $order ) { wp_send_json_error( [ 'message' => 'Not found' ] ); }
        $items = $wpdb->get_results( $wpdb->prepare(
            "SELECT i.*, b.title_ar, b.title_en FROM {$wpdb->prefix}rsyi_lib_add_order_items i
             LEFT JOIN {$wpdb->prefix}rsyi_books b ON b.id=i.book_id WHERE i.order_id=%d", $id
        ) );
        $order->items = $items ?: [];
        wp_send_json_success( $order );
    }

    static function handle_save_add_order(): void {
        self::check_manage();
        global $wpdb;
        $uid          = get_current_user_id();
        $id           = intval( $_POST['order_id'] ?? 0 );
        $supplier_id  = intval( $_POST['supplier_id'] ?? 0 );
        $quote_number = sanitize_text_field( $_POST['quote_number'] ?? '' );
        $notes        = sanitize_textarea_field( $_POST['notes'] ?? '' );
        $items_raw    = json_decode( stripslashes( $_POST['items'] ?? '[]' ), true );

        if ( empty( $items_raw ) ) { wp_send_json_error( [ 'message' => 'أضف كتاباً واحداً على الأقل / Add at least one book' ] ); }

        $total_qty = 0; $total_val = 0;
        $items = [];
        foreach ( $items_raw as $row ) {
            $bid  = intval( $row['book_id'] ?? 0 );
            $qty  = max( 1, intval( $row['quantity'] ?? 1 ) );
            $price= (float) ( $row['unit_price'] ?? 0 );
            $tax  = max( 0.0, (float) ( $row['tax_rate'] ?? 0 ) );
            $disc = max( 0.0, (float) ( $row['discount_rate'] ?? 0 ) );
            if ( ! $bid ) continue;
            $line = $qty * $price;
            if ( $disc > 0 ) { $line *= ( 1 - $disc / 100 ); }
            if ( $tax  > 0 ) { $line *= ( 1 + $tax  / 100 ); }
            $items[]    = [ 'book_id' => $bid, 'quantity' => $qty, 'unit_price' => $price, 'tax_rate' => $tax, 'discount_rate' => $disc ];
            $total_qty += $qty;
            $total_val += $line;
        }
        $is_new = ( $id === 0 );

        if ( $id > 0 ) {
            Library_Transactions::reverse_add_order( $id, $uid );
            $wpdb->delete( $wpdb->prefix . 'rsyi_lib_add_order_items', [ 'order_id' => $id ] );
            $wpdb->update( $wpdb->prefix . 'rsyi_lib_add_orders', [
                'supplier_id'    => $supplier_id,
                'total_quantity' => $total_qty,
                'total_value'    => $total_val,
                'tax_enabled'    => 0,
                'tax_rate'       => 0,
                'discount_rate'  => 0,
                'quote_number'   => $quote_number ?: null,
                'notes'          => $notes,
                'updated_at'     => current_time( 'mysql' ),
            ], [ 'id' => $id ] );
        } else {
            $order_num = Library_Transactions::generate_order_number( 'ADD' );
            $wpdb->insert( $wpdb->prefix . 'rsyi_lib_add_orders', [
                'order_number'   => $order_num,
                'supplier_id'    => $supplier_id,
                'total_quantity' => $total_qty,
                'total_value'    => $total_val,
                'tax_enabled'    => 0,
                'tax_rate'       => 0,
                'discount_rate'  => 0,
                'quote_number'   => $quote_number ?: null,
                'notes'          => $notes,
                'created_by'     => $uid,
                'created_at'     => current_time( 'mysql' ),
            ] );
            $id = $wpdb->insert_id;
        }
        foreach ( $items as $item ) {
            $wpdb->insert( $wpdb->prefix . 'rsyi_lib_add_order_items', array_merge( $item, [ 'order_id' => $id ] ) );
            Library_Transactions::record_add( $item['book_id'], $item['quantity'], $item['unit_price'], $id, $uid );
        }
        \RSYI_SA\Audit_Log::log( 'add_order', $id, $is_new ? 'create' : 'update', [ 'order_id' => $id, 'total_qty' => $total_qty ] );
        wp_send_json_success( [ 'message' => 'تم الحفظ / Saved', 'id' => $id ] );
    }

    static function handle_delete_add_order(): void {
        self::check_manage();
        global $wpdb;
        $id  = intval( $_POST['order_id'] ?? 0 );
        $uid = get_current_user_id();
        Library_Transactions::reverse_add_order( $id, $uid );
        $wpdb->delete( $wpdb->prefix . 'rsyi_lib_add_order_items', [ 'order_id' => $id ] );
        $wpdb->delete( $wpdb->prefix . 'rsyi_lib_add_orders', [ 'id' => $id ] );
        wp_send_json_success( [ 'message' => 'تم الحذف / Deleted' ] );
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // WITHDRAWAL ORDERS
    // ═══════════════════════════════════════════════════════════════════════════

    static function handle_get_wd_orders(): void {
        self::check_view();
        global $wpdb;
        $status = sanitize_key( $_POST['status'] ?? '' );
        $where  = $status ? $wpdb->prepare( 'WHERE o.status=%s', $status ) : '';
        $rows   = $wpdb->get_results(
            "SELECT o.*, c.name AS cohort_name, u.display_name AS created_by_name,
                    a.display_name AS approved_by_name
             FROM {$wpdb->prefix}rsyi_lib_withdrawal_orders o
             LEFT JOIN {$wpdb->prefix}rsyi_cohorts c ON c.id = o.cohort_id
             LEFT JOIN {$wpdb->users} u ON u.ID = o.created_by
             LEFT JOIN {$wpdb->users} a ON a.ID = o.approved_by
             {$where}
             ORDER BY o.created_at DESC LIMIT 300"
        );
        wp_send_json_success( $rows ?: [] );
    }

    static function handle_get_wd_order(): void {
        self::check_view();
        global $wpdb;
        $id    = intval( $_POST['order_id'] ?? 0 );
        $order = $wpdb->get_row( $wpdb->prepare(
            "SELECT o.*, c.name AS cohort_name, u.display_name AS created_by_name
             FROM {$wpdb->prefix}rsyi_lib_withdrawal_orders o
             LEFT JOIN {$wpdb->prefix}rsyi_cohorts c ON c.id = o.cohort_id
             LEFT JOIN {$wpdb->users} u ON u.ID = o.created_by
             WHERE o.id=%d", $id
        ) );
        if ( ! $order ) { wp_send_json_error( [ 'message' => 'Not found' ] ); }
        $items = $wpdb->get_results( $wpdb->prepare(
            "SELECT i.*, b.title_ar, b.title_en, b.isbn, b.current_stock,
                    p.arabic_full_name AS student_name, p.national_id_number AS student_id_number
             FROM {$wpdb->prefix}rsyi_lib_withdrawal_order_items i
             LEFT JOIN {$wpdb->prefix}rsyi_books b ON b.id=i.book_id
             LEFT JOIN {$wpdb->prefix}rsyi_student_profiles p ON p.id=i.student_id
             WHERE i.order_id=%d", $id
        ) );
        $order->items = $items ?: [];
        // Add real_stock per item
        foreach ( $order->items as &$item ) {
            $item->real_stock = Library_Transactions::get_real_stock( (int)$item->book_id, $id );
        }
        wp_send_json_success( $order );
    }

    static function handle_save_wd_order(): void {
        self::check_manage();
        global $wpdb;
        $uid        = get_current_user_id();
        $id         = intval( $_POST['order_id'] ?? 0 );
        $cohort_id  = intval( $_POST['cohort_id'] ?? 0 );
        $trainer_id = intval( $_POST['trainer_id'] ?? 0 );
        $order_type = sanitize_key( $_POST['order_type'] ?? 'normal' );
        $notes      = sanitize_textarea_field( $_POST['notes'] ?? '' );
        $items_raw  = json_decode( stripslashes( $_POST['items'] ?? '[]' ), true );

        if ( ! $cohort_id && ! $trainer_id ) { wp_send_json_error( [ 'message' => 'حدد مجموعة أو مدرب / Select cohort or trainer' ] ); }
        if ( empty( $items_raw ) ) { wp_send_json_error( [ 'message' => 'أضف كتاباً على الأقل / Add at least one book' ] ); }

        $items = [];
        foreach ( $items_raw as $row ) {
            $bid = intval( $row['book_id'] ?? 0 );
            $qty = max( 1, intval( $row['quantity'] ?? 1 ) );
            $sid = intval( $row['student_id'] ?? 0 );
            if ( $bid ) { $items[] = [ 'book_id' => $bid, 'quantity' => $qty, 'student_id' => $sid ?: null ]; }
        }

        if ( $id > 0 ) {
            $existing = $wpdb->get_row( $wpdb->prepare( "SELECT status FROM {$wpdb->prefix}rsyi_lib_withdrawal_orders WHERE id=%d", $id ) );
            if ( $existing && ! in_array( $existing->status, [ 'draft' ], true ) ) {
                wp_send_json_error( [ 'message' => 'لا يمكن تعديل الإذن بعد تقديمه / Cannot edit after submission' ] );
            }
            $wpdb->delete( $wpdb->prefix . 'rsyi_lib_withdrawal_order_items', [ 'order_id' => $id ] );
            $wpdb->update( $wpdb->prefix . 'rsyi_lib_withdrawal_orders', [
                'cohort_id'  => $cohort_id ?: null,
                'trainer_id' => $trainer_id ?: null,
                'order_type' => $order_type,
                'notes'      => $notes,
                'updated_at' => current_time( 'mysql' ),
            ], [ 'id' => $id ] );
        } else {
            $order_num = Library_Transactions::generate_order_number( 'WD' );
            $wpdb->insert( $wpdb->prefix . 'rsyi_lib_withdrawal_orders', [
                'order_number' => $order_num,
                'order_type'   => $order_type,
                'cohort_id'    => $cohort_id ?: null,
                'trainer_id'   => $trainer_id ?: null,
                'status'       => 'draft',
                'notes'        => $notes,
                'created_by'   => $uid,
                'created_at'   => current_time( 'mysql' ),
            ] );
            $id = $wpdb->insert_id;
        }
        foreach ( $items as $item ) {
            $wpdb->insert( $wpdb->prefix . 'rsyi_lib_withdrawal_order_items', array_merge( $item, [ 'order_id' => $id ] ) );
        }
        \RSYI_SA\Audit_Log::log( 'withdrawal_order', $id, 'save_draft', [ 'order_id' => $id ] );
        wp_send_json_success( [ 'message' => 'تم حفظ المسودة / Draft saved', 'id' => $id ] );
    }

    static function handle_submit_wd_order(): void {
        self::check_manage();
        global $wpdb;
        $id   = intval( $_POST['order_id'] ?? 0 );
        $uid  = get_current_user_id();
        $order = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}rsyi_lib_withdrawal_orders WHERE id=%d AND status='draft'", $id ) );
        if ( ! $order ) { wp_send_json_error( [ 'message' => 'المسودة غير موجودة / Draft not found' ] ); }

        // Check stock for each item
        $items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}rsyi_lib_withdrawal_order_items WHERE order_id=%d", $id ) );
        $errors = [];
        foreach ( $items as $item ) {
            $avail = Library_Transactions::get_real_stock( (int)$item->book_id, $id );
            if ( $avail < $item->quantity ) {
                $book = $wpdb->get_row( $wpdb->prepare( "SELECT title_ar FROM {$wpdb->prefix}rsyi_books WHERE id=%d", $item->book_id ) );
                $errors[] = ( $book->title_ar ?? "Book #{$item->book_id}" ) . ": متاح {$avail} فقط / only {$avail} available";
            }
        }
        if ( $errors ) { wp_send_json_error( [ 'message' => implode( "\n", $errors ) ] ); }

        $wpdb->update( $wpdb->prefix . 'rsyi_lib_withdrawal_orders',
            [ 'status' => 'pending', 'updated_at' => current_time( 'mysql' ) ], [ 'id' => $id ]
        );

        // Send email notification
        self::notify_withdrawal_created( $order );
        \RSYI_SA\Audit_Log::log( 'withdrawal_order', $id, 'submit', [ 'order_id' => $id ] );
        wp_send_json_success( [ 'message' => 'تم تقديم الإذن / Order submitted for approval' ] );
    }

    static function handle_approve_wd_order(): void {
        if ( ! current_user_can( 'rsyi_lib_approve_withdrawal' ) && ! current_user_can( 'manage_options' ) ) { wp_send_json_error( [ 'message' => 'غير مصرح / Unauthorized' ] ); }
        check_ajax_referer( 'rsyi_sa_admin', 'nonce' );
        global $wpdb;
        $id  = intval( $_POST['order_id'] ?? 0 );
        $uid = get_current_user_id();
        $order = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}rsyi_lib_withdrawal_orders WHERE id=%d AND status='pending'", $id ) );
        if ( ! $order ) { wp_send_json_error( [ 'message' => 'الإذن غير موجود أو ليس في انتظار الموافقة / Order not pending' ] ); }

        $wpdb->update( $wpdb->prefix . 'rsyi_lib_withdrawal_orders', [
            'status'      => 'approved',
            'approved_by' => $uid,
            'approved_at' => current_time( 'mysql' ),
            'updated_at'  => current_time( 'mysql' ),
        ], [ 'id' => $id ] );
        \RSYI_SA\Audit_Log::log( 'withdrawal_order', $id, 'approve', [ 'order_id' => $id ] );
        wp_send_json_success( [ 'message' => 'تم الاعتماد / Approved' ] );
    }

    static function handle_reject_wd_order(): void {
        if ( ! current_user_can( 'rsyi_lib_approve_withdrawal' ) && ! current_user_can( 'manage_options' ) ) { wp_send_json_error( [ 'message' => 'غير مصرح / Unauthorized' ] ); }
        check_ajax_referer( 'rsyi_sa_admin', 'nonce' );
        global $wpdb;
        $id = intval( $_POST['order_id'] ?? 0 );
        $wpdb->update( $wpdb->prefix . 'rsyi_lib_withdrawal_orders',
            [ 'status' => 'rejected', 'updated_at' => current_time( 'mysql' ) ], [ 'id' => $id ]
        );
        wp_send_json_success( [ 'message' => 'تم الرفض / Rejected' ] );
    }

    static function handle_complete_wd_order(): void {
        self::check_manage();
        global $wpdb;
        $id    = intval( $_POST['order_id'] ?? 0 );
        $uid   = get_current_user_id();
        $order = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}rsyi_lib_withdrawal_orders WHERE id=%d AND status='approved'", $id ) );
        if ( ! $order ) { wp_send_json_error( [ 'message' => 'الإذن غير معتمد / Order not approved' ] ); }

        $items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}rsyi_lib_withdrawal_order_items WHERE order_id=%d", $id ) );
        $errors = [];
        foreach ( $items as $item ) {
            $ok = Library_Transactions::record_withdrawal( (int)$item->book_id, (int)$item->quantity, $id, $uid );
            if ( ! $ok ) {
                $book = $wpdb->get_row( $wpdb->prepare( "SELECT title_ar FROM {$wpdb->prefix}rsyi_books WHERE id=%d", $item->book_id ) );
                $errors[] = "رصيد غير كافٍ: " . ( $book->title_ar ?? "Book #{$item->book_id}" );
            }
        }
        if ( $errors ) { wp_send_json_error( [ 'message' => implode( "\n", $errors ) ] ); }

        $wpdb->update( $wpdb->prefix . 'rsyi_lib_withdrawal_orders',
            [ 'status' => 'completed', 'updated_at' => current_time( 'mysql' ) ], [ 'id' => $id ]
        );
        wp_send_json_success( [ 'message' => 'تم الصرف / Completed' ] );
    }

    static function handle_delete_wd_order(): void {
        self::check_manage();
        global $wpdb;
        $id = intval( $_POST['order_id'] ?? 0 );
        $order = $wpdb->get_row( $wpdb->prepare( "SELECT status FROM {$wpdb->prefix}rsyi_lib_withdrawal_orders WHERE id=%d", $id ) );
        if ( $order && ! in_array( $order->status, [ 'draft', 'rejected' ], true ) ) {
            wp_send_json_error( [ 'message' => 'لا يمكن حذف الإذن / Cannot delete this order' ] );
        }
        $wpdb->delete( $wpdb->prefix . 'rsyi_lib_withdrawal_order_items', [ 'order_id' => $id ] );
        $wpdb->delete( $wpdb->prefix . 'rsyi_lib_withdrawal_orders', [ 'id' => $id ] );
        wp_send_json_success( [ 'message' => 'تم الحذف / Deleted' ] );
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // RETURN ORDERS
    // ═══════════════════════════════════════════════════════════════════════════

    static function handle_get_ret_orders(): void {
        self::check_view();
        global $wpdb;
        $rows = $wpdb->get_results(
            "SELECT o.*, c.name AS cohort_name, u.display_name AS created_by_name
             FROM {$wpdb->prefix}rsyi_lib_return_orders o
             LEFT JOIN {$wpdb->prefix}rsyi_cohorts c ON c.id = o.cohort_id
             LEFT JOIN {$wpdb->users} u ON u.ID = o.created_by
             ORDER BY o.created_at DESC LIMIT 200"
        );
        wp_send_json_success( $rows ?: [] );
    }

    static function handle_get_ret_order(): void {
        self::check_view();
        global $wpdb;
        $id    = intval( $_POST['order_id'] ?? 0 );
        $order = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}rsyi_lib_return_orders WHERE id=%d", $id ) );
        if ( ! $order ) { wp_send_json_error( [ 'message' => 'Not found' ] ); }
        $items = $wpdb->get_results( $wpdb->prepare(
            "SELECT i.*, b.title_ar, b.title_en FROM {$wpdb->prefix}rsyi_lib_return_order_items i
             LEFT JOIN {$wpdb->prefix}rsyi_books b ON b.id=i.book_id WHERE i.order_id=%d", $id
        ) );
        $order->items = $items ?: [];
        wp_send_json_success( $order );
    }

    static function handle_save_ret_order(): void {
        self::check_manage();
        global $wpdb;
        $uid       = get_current_user_id();
        $id        = intval( $_POST['order_id'] ?? 0 );
        $cohort_id = intval( $_POST['cohort_id'] ?? 0 );
        $trainer_id = intval( $_POST['trainer_id'] ?? 0 );
        $notes     = sanitize_textarea_field( $_POST['notes'] ?? '' );
        $items_raw = json_decode( stripslashes( $_POST['items'] ?? '[]' ), true );
        if ( empty( $items_raw ) ) { wp_send_json_error( [ 'message' => 'أضف كتاباً على الأقل / Add at least one book' ] ); }

        $items = [];
        foreach ( $items_raw as $row ) {
            $bid  = intval( $row['book_id'] ?? 0 );
            $qty  = max( 1, intval( $row['quantity'] ?? 1 ) );
            $cond = sanitize_key( $row['condition_v'] ?? 'good' );
            if ( $bid ) { $items[] = [ 'book_id' => $bid, 'quantity' => $qty, 'condition_v' => $cond ]; }
        }

        if ( $id > 0 ) {
            $wpdb->delete( $wpdb->prefix . 'rsyi_lib_return_order_items', [ 'order_id' => $id ] );
            $wpdb->update( $wpdb->prefix . 'rsyi_lib_return_orders', [
                'cohort_id'  => $cohort_id ?: null,
                'trainer_id' => $trainer_id ?: null,
                'notes'      => $notes,
                'updated_at' => current_time( 'mysql' ),
            ], [ 'id' => $id ] );
        } else {
            $order_num = Library_Transactions::generate_order_number( 'RET' );
            $wpdb->insert( $wpdb->prefix . 'rsyi_lib_return_orders', [
                'order_number' => $order_num,
                'cohort_id'    => $cohort_id ?: null,
                'trainer_id'   => $trainer_id ?: null,
                'status'       => 'pending',
                'notes'        => $notes,
                'created_by'   => $uid,
                'created_at'   => current_time( 'mysql' ),
            ] );
            $id = $wpdb->insert_id;
        }
        foreach ( $items as $item ) {
            $wpdb->insert( $wpdb->prefix . 'rsyi_lib_return_order_items', array_merge( $item, [ 'order_id' => $id ] ) );
        }
        wp_send_json_success( [ 'message' => 'تم الحفظ / Saved', 'id' => $id ] );
    }

    static function handle_complete_ret_order(): void {
        self::check_manage();
        global $wpdb;
        $id  = intval( $_POST['order_id'] ?? 0 );
        $uid = get_current_user_id();
        $order = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}rsyi_lib_return_orders WHERE id=%d AND status='pending'", $id ) );
        if ( ! $order ) { wp_send_json_error( [ 'message' => 'Order not found / Not pending' ] ); }
        $items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}rsyi_lib_return_order_items WHERE order_id=%d", $id ) );
        foreach ( $items as $item ) {
            Library_Transactions::record_return( (int)$item->book_id, (int)$item->quantity, $id, $uid );
        }
        $wpdb->update( $wpdb->prefix . 'rsyi_lib_return_orders',
            [ 'status' => 'completed', 'updated_at' => current_time( 'mysql' ) ], [ 'id' => $id ]
        );
        wp_send_json_success( [ 'message' => 'تمت عملية الرد / Return completed' ] );
    }

    static function handle_delete_ret_order(): void {
        self::check_manage();
        global $wpdb;
        $id = intval( $_POST['order_id'] ?? 0 );
        $wpdb->delete( $wpdb->prefix . 'rsyi_lib_return_order_items', [ 'order_id' => $id ] );
        $wpdb->delete( $wpdb->prefix . 'rsyi_lib_return_orders', [ 'id' => $id ] );
        wp_send_json_success( [ 'message' => 'تم الحذف / Deleted' ] );
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PURCHASE REQUESTS
    // ═══════════════════════════════════════════════════════════════════════════

    static function handle_get_pr_list(): void {
        self::check_view();
        global $wpdb;
        $rows = $wpdb->get_results(
            "SELECT r.*, u.display_name AS requested_by_name, a.display_name AS approved_by_name
             FROM {$wpdb->prefix}rsyi_lib_purchase_requests r
             LEFT JOIN {$wpdb->users} u ON u.ID = r.requested_by
             LEFT JOIN {$wpdb->users} a ON a.ID = r.approved_by
             ORDER BY r.created_at DESC LIMIT 200"
        );
        wp_send_json_success( $rows ?: [] );
    }

    static function handle_get_pr(): void {
        self::check_manage();
        global $wpdb;
        $id  = intval( $_POST['pr_id'] ?? 0 );
        $pr  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}rsyi_lib_purchase_requests WHERE id=%d", $id ) );
        if ( ! $pr ) { wp_send_json_error( [ 'message' => 'Not found' ] ); }
        $items = $wpdb->get_results( $wpdb->prepare(
            "SELECT i.*, b.title_ar, b.title_en, b.isbn, b.current_stock
             FROM {$wpdb->prefix}rsyi_lib_purchase_request_items i
             LEFT JOIN {$wpdb->prefix}rsyi_books b ON b.id = i.book_id
             WHERE i.request_id = %d
             ORDER BY i.id", $id
        ) ) ?: [];
        if ( $items ) {
            $book_ids  = array_unique( array_map( fn( $r ) => (int) $r->book_id, $items ) );
            $in_clause = implode( ',', array_fill( 0, count( $book_ids ), '%d' ) );
            // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
            $price_rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT aoi.book_id, aoi.unit_price
                 FROM {$wpdb->prefix}rsyi_lib_add_order_items aoi
                 INNER JOIN {$wpdb->prefix}rsyi_lib_add_orders ao ON ao.id = aoi.order_id
                 WHERE aoi.book_id IN ($in_clause)
                 ORDER BY ao.created_at DESC",
                ...$book_ids
            ) ) ?: [];
            $price_map = [];
            foreach ( $price_rows as $r ) {
                if ( ! isset( $price_map[ (int) $r->book_id ] ) ) {
                    $price_map[ (int) $r->book_id ] = (float) $r->unit_price;
                }
            }
            foreach ( $items as $item ) {
                $item->last_purchase_price = $price_map[ (int) $item->book_id ] ?? 0;
            }
        }
        $pr->items = $items;
        wp_send_json_success( $pr );
    }

    static function handle_get_pr_print_data(): void {
        self::check_manage();
        global $wpdb;
        $id = intval( $_POST['pr_id'] ?? 0 );
        $pr = $wpdb->get_row( $wpdb->prepare(
            "SELECT r.*, u.display_name AS requested_by_name, a.display_name AS approved_by_name
             FROM {$wpdb->prefix}rsyi_lib_purchase_requests r
             LEFT JOIN {$wpdb->users} u ON u.ID = r.requested_by
             LEFT JOIN {$wpdb->users} a ON a.ID = r.approved_by
             WHERE r.id=%d", $id
        ) );
        if ( ! $pr ) { wp_send_json_error( [ 'message' => 'Not found' ] ); }

        // Simple items query — no correlated subquery to avoid silent MySQL failures
        $items = $wpdb->get_results( $wpdb->prepare(
            "SELECT i.*, b.title_ar, b.title_en, b.isbn, b.current_stock
             FROM {$wpdb->prefix}rsyi_lib_purchase_request_items i
             LEFT JOIN {$wpdb->prefix}rsyi_books b ON b.id = i.book_id
             WHERE i.request_id = %d
             ORDER BY i.id", $id
        ) ) ?: [];

        // Attach last purchase price per book via a separate bulk query
        if ( $items ) {
            $book_ids   = array_unique( array_map( fn( $r ) => (int) $r->book_id, $items ) );
            $in_clause  = implode( ',', array_fill( 0, count( $book_ids ), '%d' ) );
            // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
            $price_rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT aoi.book_id, aoi.unit_price
                 FROM {$wpdb->prefix}rsyi_lib_add_order_items aoi
                 INNER JOIN {$wpdb->prefix}rsyi_lib_add_orders ao ON ao.id = aoi.order_id
                 WHERE aoi.book_id IN ($in_clause)
                 ORDER BY ao.created_at DESC",
                ...$book_ids
            ) ) ?: [];
            $price_map = [];
            foreach ( $price_rows as $r ) {
                if ( ! isset( $price_map[ (int) $r->book_id ] ) ) {
                    $price_map[ (int) $r->book_id ] = (float) $r->unit_price;
                }
            }
            foreach ( $items as $item ) {
                $item->last_purchase_price = $price_map[ (int) $item->book_id ] ?? 0;
            }
        }

        $ci_users = get_users( [ 'role' => 'rsyi_senior_naval_trainer', 'number' => 1, 'fields' => [ 'ID', 'display_name' ] ] );
        $ci_id    = $ci_users ? (int) $ci_users[0]->ID : 0;
        wp_send_json_success( [
            'pr'                    => $pr,
            'items'                 => $items,
            'logo'                  => get_option( 'rsyi_logo_url', '' ),
            'institute_name'        => get_option( 'rsyi_institute_name', 'Red Sea Yachting Institute' ),
            'institute_name_en'     => get_option( 'rsyi_institute_name_en', 'Red Sea Yachting Institute' ),
            'chief_instructor_name' => $ci_id ? get_userdata( $ci_id )->display_name : '',
            'chief_instructor_sig'  => $ci_id ? get_user_meta( $ci_id, 'rsyi_chief_signature', true ) : '',
        ] );
    }

    static function handle_save_pr(): void {
        self::check_manage();
        global $wpdb;
        $uid       = get_current_user_id();
        $id        = intval( $_POST['pr_id'] ?? 0 );
        $notes     = sanitize_textarea_field( $_POST['notes'] ?? '' );
        $items_raw = json_decode( stripslashes( $_POST['items'] ?? '[]' ), true );

        $items = [];
        foreach ( (array) $items_raw as $row ) {
            $bid   = intval( $row['book_id'] ?? 0 );
            $qty   = max( 1, intval( $row['quantity'] ?? 1 ) );
            $price = max( 0, (float) ( $row['unit_price'] ?? 0 ) );
            if ( $bid ) { $items[] = [ 'book_id' => $bid, 'quantity' => $qty, 'unit_price' => $price, 'notes' => sanitize_text_field( $row['notes'] ?? '' ) ]; }
        }
        // Validate AFTER filtering so a row with book_id=0 doesn't sneak through and wipe real items
        if ( empty( $items ) ) { wp_send_json_error( [ 'message' => 'أضف كتاباً على الأقل / Add at least one book' ] ); }

        if ( $id > 0 ) {
            $pr = $wpdb->get_row( $wpdb->prepare( "SELECT status FROM {$wpdb->prefix}rsyi_lib_purchase_requests WHERE id=%d", $id ) );
            if ( $pr && $pr->status !== 'pending' ) { wp_send_json_error( [ 'message' => 'لا يمكن تعديل طلب معالَج / Cannot edit processed request' ] ); }
            $wpdb->delete( $wpdb->prefix . 'rsyi_lib_purchase_request_items', [ 'request_id' => $id ] );
            $wpdb->update( $wpdb->prefix . 'rsyi_lib_purchase_requests', [ 'notes' => $notes, 'updated_at' => current_time( 'mysql' ) ], [ 'id' => $id ] );
        } else {
            $req_num = Library_Transactions::generate_order_number( 'PR' );
            $ci_id   = self::get_chief_instructor_id();
            $wpdb->insert( $wpdb->prefix . 'rsyi_lib_purchase_requests', [
                'request_number' => $req_num,
                'status'         => 'pending',
                'notes'          => $notes,
                'requested_by'   => $ci_id ?: $uid,
                'created_at'     => current_time( 'mysql' ),
            ] );
            $id = $wpdb->insert_id;
            if ( ! $id ) { wp_send_json_error( [ 'message' => 'فشل إنشاء الطلب / Failed to create PR' ] ); }
        }
        $saved = 0;
        foreach ( $items as $item ) {
            if ( $wpdb->insert( $wpdb->prefix . 'rsyi_lib_purchase_request_items', array_merge( $item, [ 'request_id' => $id ] ) ) ) {
                $saved++;
            }
        }
        if ( ! $saved ) {
            wp_send_json_error( [ 'message' => 'فشل حفظ عناصر الطلب — ' . $wpdb->last_error . ' / Failed to save items. Run DB migration.' ] );
        }
        wp_send_json_success( [ 'message' => 'تم الحفظ / Saved', 'id' => $id ] );
    }

    static function handle_approve_pr(): void {
        if ( ! current_user_can( 'rsyi_lib_approve_purchase' ) && ! current_user_can( 'manage_options' ) ) { wp_send_json_error( [ 'message' => 'فقط العميد يعتمد طلبات الشراء / Only Dean can approve purchase requests' ] ); }
        check_ajax_referer( 'rsyi_sa_admin', 'nonce' );
        global $wpdb;
        $id  = intval( $_POST['pr_id'] ?? 0 );
        $uid = get_current_user_id();
        $wpdb->update( $wpdb->prefix . 'rsyi_lib_purchase_requests', [
            'status'      => 'approved',
            'approved_by' => $uid,
            'approved_at' => current_time( 'mysql' ),
            'updated_at'  => current_time( 'mysql' ),
        ], [ 'id' => $id ] );
        wp_send_json_success( [ 'message' => 'تم الاعتماد / Approved' ] );
    }

    static function handle_reject_pr(): void {
        if ( ! current_user_can( 'rsyi_lib_approve_purchase' ) && ! current_user_can( 'manage_options' ) ) { wp_send_json_error( [ 'message' => 'غير مصرح / Unauthorized' ] ); }
        check_ajax_referer( 'rsyi_sa_admin', 'nonce' );
        global $wpdb;
        $id = intval( $_POST['pr_id'] ?? 0 );
        $wpdb->update( $wpdb->prefix . 'rsyi_lib_purchase_requests',
            [ 'status' => 'rejected', 'updated_at' => current_time( 'mysql' ) ], [ 'id' => $id ]
        );
        wp_send_json_success( [ 'message' => 'تم الرفض / Rejected' ] );
    }

    static function handle_delete_pr(): void {
        self::check_manage();
        global $wpdb;
        $id = intval( $_POST['pr_id'] ?? 0 );
        $pr = $wpdb->get_row( $wpdb->prepare( "SELECT status FROM {$wpdb->prefix}rsyi_lib_purchase_requests WHERE id=%d", $id ) );
        if ( $pr && ! in_array( $pr->status, [ 'pending', 'rejected' ], true ) ) {
            wp_send_json_error( [ 'message' => 'لا يمكن الحذف / Cannot delete' ] );
        }
        $wpdb->delete( $wpdb->prefix . 'rsyi_lib_purchase_request_items', [ 'request_id' => $id ] );
        $wpdb->delete( $wpdb->prefix . 'rsyi_lib_purchase_requests', [ 'id' => $id ] );
        wp_send_json_success( [ 'message' => 'تم الحذف / Deleted' ] );
    }

    static function handle_convert_pr_to_add(): void {
        self::check_manage();
        global $wpdb;
        $pr_id = intval( $_POST['pr_id'] ?? 0 );
        $pr    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}rsyi_lib_purchase_requests WHERE id=%d AND status='approved'", $pr_id ) );
        if ( ! $pr ) { wp_send_json_error( [ 'message' => 'طلب الشراء غير معتمد / Purchase request not approved' ] ); }
        $items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}rsyi_lib_purchase_request_items WHERE request_id=%d", $pr_id ) );
        $uid   = get_current_user_id();

        $order_num = Library_Transactions::generate_order_number( 'ADD' );
        $wpdb->insert( $wpdb->prefix . 'rsyi_lib_add_orders', [
            'order_number'   => $order_num,
            'total_quantity' => array_sum( array_column( (array)$items, 'quantity' ) ),
            'total_value'    => 0,
            'from_pr_id'     => $pr_id,
            'created_by'     => $uid,
            'created_at'     => current_time( 'mysql' ),
        ] );
        $add_order_id = $wpdb->insert_id;

        foreach ( (array)$items as $item ) {
            $wpdb->insert( $wpdb->prefix . 'rsyi_lib_add_order_items', [
                'order_id' => $add_order_id, 'book_id' => $item->book_id, 'quantity' => $item->quantity, 'unit_price' => 0
            ] );
            Library_Transactions::record_add( (int)$item->book_id, (int)$item->quantity, 0, $add_order_id, $uid );
        }
        $wpdb->update( $wpdb->prefix . 'rsyi_lib_purchase_requests',
            [ 'status' => 'completed', 'updated_at' => current_time( 'mysql' ) ], [ 'id' => $pr_id ]
        );
        wp_send_json_success( [ 'message' => 'تم إنشاء إذن الاستلام / Add order created', 'add_order_id' => $add_order_id ] );
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // OPENING BALANCES
    // ═══════════════════════════════════════════════════════════════════════════

    static function handle_get_opening(): void {
        self::check_manage();
        global $wpdb;
        $rows = $wpdb->get_results(
            "SELECT ob.*, b.title_ar, b.title_en
             FROM {$wpdb->prefix}rsyi_lib_opening_balances ob
             LEFT JOIN {$wpdb->prefix}rsyi_books b ON b.id=ob.book_id
             ORDER BY ob.created_at DESC"
        );
        wp_send_json_success( $rows ?: [] );
    }

    static function handle_save_opening(): void {
        self::check_manage();
        global $wpdb;
        $uid       = get_current_user_id();
        $book_id   = intval( $_POST['book_id'] ?? 0 );
        $quantity  = max( 0, intval( $_POST['quantity'] ?? 0 ) );
        $unit_price = (float) ( $_POST['unit_price'] ?? 0 );
        $notes     = sanitize_textarea_field( $_POST['notes'] ?? '' );
        if ( ! $book_id ) { wp_send_json_error( [ 'message' => 'حدد الكتاب / Select a book' ] ); }

        $existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}rsyi_lib_opening_balances WHERE book_id=%d", $book_id ) );
        if ( $existing ) { wp_send_json_error( [ 'message' => 'الرصيد الافتتاحي مسجل مسبقاً / Opening balance already exists' ] ); }

        $wpdb->insert( $wpdb->prefix . 'rsyi_lib_opening_balances', [
            'book_id'    => $book_id,
            'quantity'   => $quantity,
            'unit_price' => $unit_price,
            'notes'      => $notes,
            'created_by' => $uid,
            'created_at' => current_time( 'mysql' ),
        ] );
        $opening_id = $wpdb->insert_id;
        Library_Transactions::record_opening( $book_id, $quantity, $unit_price, $opening_id, $uid );
        wp_send_json_success( [ 'message' => 'تم تسجيل الرصيد الافتتاحي / Opening balance saved' ] );
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // BALANCE ADJUSTMENT (Admin direct edit)
    // ═══════════════════════════════════════════════════════════════════════════

    static function handle_edit_balance(): void {
        self::check_manage();
        global $wpdb;
        $book_id   = intval( $_POST['book_id'] ?? 0 );
        $new_stock = max( 0, intval( $_POST['new_stock'] ?? 0 ) );
        $notes     = sanitize_textarea_field( $_POST['notes'] ?? '' );
        $uid       = get_current_user_id();

        $book = $wpdb->get_row( $wpdb->prepare( "SELECT id, title_ar, current_stock FROM {$wpdb->prefix}rsyi_books WHERE id=%d", $book_id ) );
        if ( ! $book ) { wp_send_json_error( [ 'message' => 'العنصر غير موجود / Item not found' ] ); }

        $old_stock = (int) $book->current_stock;
        $wpdb->update( $wpdb->prefix . 'rsyi_books', [ 'current_stock' => $new_stock ], [ 'id' => $book_id ] );

        // Record adjustment transaction
        $wpdb->insert( $wpdb->prefix . 'rsyi_lib_transactions', [
            'book_id'          => $book_id,
            'transaction_type' => 'adjustment',
            'quantity'         => $new_stock - $old_stock,
            'unit_price'       => 0.00,
            'remaining_qty'    => $new_stock,
            'notes'            => $notes ?: "تعديل يدوي / Manual adjustment: {$old_stock} → {$new_stock}",
            'created_by'       => $uid,
            'created_at'       => current_time( 'mysql' ),
        ] );

        \RSYI_SA\Audit_Log::log( 'book', $book_id, 'edit_balance', [
            'old_stock' => $old_stock,
            'new_stock' => $new_stock,
            'notes'     => $notes,
        ] );

        wp_send_json_success( [ 'message' => "تم تعديل الرصيد / Balance updated: {$old_stock} → {$new_stock}" ] );
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PRINT DATA
    // ═══════════════════════════════════════════════════════════════════════════

    static function handle_get_print_data(): void {
        self::check_view();
        global $wpdb;
        $type = sanitize_key( $_POST['type'] ?? '' );
        $id   = intval( $_POST['order_id'] ?? 0 );
        $data = [];

        if ( $type === 'add' ) {
            $data['order'] = $wpdb->get_row( $wpdb->prepare(
                "SELECT o.*, s.name AS supplier_name, u.display_name AS created_by_name
                 FROM {$wpdb->prefix}rsyi_lib_add_orders o
                 LEFT JOIN {$wpdb->prefix}rsyi_lib_suppliers s ON s.id=o.supplier_id
                 LEFT JOIN {$wpdb->users} u ON u.ID=o.created_by WHERE o.id=%d", $id
            ) );
            $data['items'] = $wpdb->get_results( $wpdb->prepare(
                "SELECT i.*, b.title_ar, b.title_en, b.isbn FROM {$wpdb->prefix}rsyi_lib_add_order_items i
                 LEFT JOIN {$wpdb->prefix}rsyi_books b ON b.id=i.book_id WHERE i.order_id=%d", $id
            ) );
        } elseif ( $type === 'withdrawal' ) {
            $data['order'] = $wpdb->get_row( $wpdb->prepare(
                "SELECT o.*, c.name AS cohort_name, u.display_name AS created_by_name, a.display_name AS approved_by_name
                 FROM {$wpdb->prefix}rsyi_lib_withdrawal_orders o
                 LEFT JOIN {$wpdb->prefix}rsyi_cohorts c ON c.id=o.cohort_id
                 LEFT JOIN {$wpdb->users} u ON u.ID=o.created_by
                 LEFT JOIN {$wpdb->users} a ON a.ID=o.approved_by WHERE o.id=%d", $id
            ) );
            $data['items'] = $wpdb->get_results( $wpdb->prepare(
                "SELECT i.*, b.title_ar, b.title_en, b.isbn,
                    (SELECT aoi.unit_price
                     FROM {$wpdb->prefix}rsyi_lib_add_order_items aoi
                     JOIN {$wpdb->prefix}rsyi_lib_add_orders ao ON ao.id=aoi.order_id
                     WHERE aoi.book_id=i.book_id
                     ORDER BY ao.created_at DESC LIMIT 1) AS last_purchase_price,
                    (SELECT aoi.tax_rate
                     FROM {$wpdb->prefix}rsyi_lib_add_order_items aoi
                     JOIN {$wpdb->prefix}rsyi_lib_add_orders ao ON ao.id=aoi.order_id
                     WHERE aoi.book_id=i.book_id
                     ORDER BY ao.created_at DESC LIMIT 1) AS last_tax_rate,
                    (SELECT aoi.discount_rate
                     FROM {$wpdb->prefix}rsyi_lib_add_order_items aoi
                     JOIN {$wpdb->prefix}rsyi_lib_add_orders ao ON ao.id=aoi.order_id
                     WHERE aoi.book_id=i.book_id
                     ORDER BY ao.created_at DESC LIMIT 1) AS last_discount_rate
                 FROM {$wpdb->prefix}rsyi_lib_withdrawal_order_items i
                 LEFT JOIN {$wpdb->prefix}rsyi_books b ON b.id=i.book_id WHERE i.order_id=%d", $id
            ) );
            // Always get Chief Instructor from role
            $ci_users = get_users( [ 'role' => 'rsyi_senior_naval_trainer', 'number' => 1, 'fields' => [ 'ID', 'display_name' ] ] );
            $ci_id    = $ci_users ? (int) $ci_users[0]->ID : 0;
            $data['chief_instructor_name'] = $ci_id ? get_userdata( $ci_id )->display_name : '';
            $data['chief_instructor_sig']  = $ci_id ? get_user_meta( $ci_id, 'rsyi_chief_signature', true ) : '';
        } elseif ( $type === 'return' ) {
            $data['order'] = $wpdb->get_row( $wpdb->prepare(
                "SELECT o.*, c.name AS cohort_name, u.display_name AS created_by_name
                 FROM {$wpdb->prefix}rsyi_lib_return_orders o
                 LEFT JOIN {$wpdb->prefix}rsyi_cohorts c ON c.id=o.cohort_id
                 LEFT JOIN {$wpdb->users} u ON u.ID=o.created_by WHERE o.id=%d", $id
            ) );
            $data['items'] = $wpdb->get_results( $wpdb->prepare(
                "SELECT i.*, b.title_ar, b.title_en FROM {$wpdb->prefix}rsyi_lib_return_order_items i
                 LEFT JOIN {$wpdb->prefix}rsyi_books b ON b.id=i.book_id WHERE i.order_id=%d", $id
            ) );
        }
        $data['logo']              = get_option( 'rsyi_logo_url', '' );
        $data['institute_name']    = get_option( 'rsyi_institute_name', 'Red Sea Yachting Institute' );
        $data['institute_name_en'] = get_option( 'rsyi_institute_name_en', 'Red Sea Yachting Institute' );
        wp_send_json_success( $data );
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // DASHBOARD DATA
    // ═══════════════════════════════════════════════════════════════════════════

    static function handle_get_dashboard(): void {
        self::check_view();
        global $wpdb;
        Library_Transactions::sync_all_stocks();
        $data = [
            'total_books'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}rsyi_books WHERE is_active=1" ),
            'total_stock'      => (int) $wpdb->get_var( "SELECT COALESCE(SUM(current_stock),0) FROM {$wpdb->prefix}rsyi_books WHERE is_active=1" ),
            'low_stock'        => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}rsyi_books WHERE is_active=1 AND current_stock <= min_stock AND min_stock > 0" ),
            'zero_stock'       => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}rsyi_books WHERE is_active=1 AND current_stock=0" ),
            'pending_wd'       => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}rsyi_lib_withdrawal_orders WHERE status='pending'" ),
            'pending_pr'       => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}rsyi_lib_purchase_requests WHERE status='pending'" ),
            'today_withdrawals'=> (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}rsyi_lib_withdrawal_orders WHERE DATE(created_at)=%s", current_time( 'Y-m-d' ) ) ),
            'today_adds'       => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}rsyi_lib_add_orders WHERE DATE(created_at)=%s", current_time( 'Y-m-d' ) ) ),
            'low_stock_books'  => $wpdb->get_results( "SELECT id, title_ar, title_en, current_stock, min_stock FROM {$wpdb->prefix}rsyi_books WHERE is_active=1 AND current_stock <= min_stock AND min_stock > 0 ORDER BY current_stock ASC LIMIT 10" ),
        ];
        wp_send_json_success( $data );
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════════════════════

    // ═══════════════════════════════════════════════════════════════════════════
    // AUTO-GENERATE PURCHASE REQUEST
    // ═══════════════════════════════════════════════════════════════════════════

    static function handle_auto_pr(): void {
        self::check_manage();
        global $wpdb;
        $books = $wpdb->get_results(
            "SELECT id, title_ar, current_stock, max_stock
             FROM {$wpdb->prefix}rsyi_books
             WHERE is_active=1 AND max_stock > 0 AND current_stock < max_stock
             ORDER BY title_ar"
        );
        if ( empty( $books ) ) {
            wp_send_json_error( [ 'message' => 'لا توجد عناصر تحتاج لشراء / No items need purchasing' ] );
        }
        $items = [];
        foreach ( $books as $b ) {
            $qty = (int) $b->max_stock - (int) $b->current_stock;
            if ( $qty > 0 ) {
                $items[] = [ 'book_id' => (int) $b->id, 'quantity' => $qty, 'notes' => '' ];
            }
        }
        if ( empty( $items ) ) {
            wp_send_json_error( [ 'message' => 'لا توجد عناصر تحتاج لشراء / No items need purchasing' ] );
        }
        $uid     = get_current_user_id();
        $ci_id   = self::get_chief_instructor_id();
        $req_num = Library_Transactions::generate_order_number( 'PR' );
        $result  = $wpdb->insert( $wpdb->prefix . 'rsyi_lib_purchase_requests', [
            'request_number' => $req_num,
            'status'         => 'pending',
            'notes'          => 'Auto-generated / توليد تلقائي',
            'requested_by'   => $ci_id ?: $uid,
            'created_at'     => current_time( 'mysql' ),
        ] );
        if ( ! $result || ! ( $id = $wpdb->insert_id ) ) {
            wp_send_json_error( [ 'message' => 'فشل إنشاء الطلب — ' . $wpdb->last_error . ' / Failed to create PR' ] );
        }
        $saved = 0;
        foreach ( $items as $item ) {
            if ( $wpdb->insert( $wpdb->prefix . 'rsyi_lib_purchase_request_items', array_merge( $item, [ 'request_id' => $id ] ) ) ) {
                $saved++;
            }
        }
        if ( ! $saved ) {
            $wpdb->delete( $wpdb->prefix . 'rsyi_lib_purchase_requests', [ 'id' => $id ] );
            wp_send_json_error( [ 'message' => 'فشل حفظ عناصر الطلب — ' . $wpdb->last_error . ' / Failed to save PR items. Check DB migration.' ] );
        }
        \RSYI_SA\Audit_Log::log( 'purchase_request', $id, 'auto_create', [ 'items_count' => $saved ] );
        wp_send_json_success( [
            'message' => sprintf( 'تم إنشاء طلب شراء بـ %d عنصر / Created PR with %d items', $saved, $saved ),
            'id'      => $id,
            'count'   => $saved,
        ] );
    }

    private static function get_chief_instructor_id(): int {
        $users = get_users( [ 'role' => 'rsyi_senior_naval_trainer', 'number' => 1, 'fields' => [ 'ID' ] ] );
        return $users ? (int) $users[0]->ID : 0;
    }

    private static function check_manage(): void {
        check_ajax_referer( 'rsyi_sa_admin', 'nonce' );
        if ( ! current_user_can( 'rsyi_lib_manage_warehouse' ) && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'غير مصرح / Unauthorized' ] );
        }
    }

    private static function check_view(): void {
        check_ajax_referer( 'rsyi_sa_admin', 'nonce' );
        if ( ! current_user_can( 'rsyi_lib_view_warehouse' ) && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'غير مصرح / Unauthorized' ] );
        }
    }

    private static function notify_withdrawal_created( object $order ): void {
        $emails = [];
        $notify_email = get_option( 'rsyi_notify_email', '' );
        if ( $notify_email ) { $emails[] = $notify_email; }

        $users = get_users( [ 'role__in' => [ 'rsyi_senior_naval_trainer', 'rsyi_dean' ], 'fields' => [ 'user_email' ] ] );
        foreach ( $users as $u ) { $emails[] = $u->user_email; }
        $emails = array_unique( array_filter( $emails ) );
        if ( empty( $emails ) ) return;

        global $wpdb;
        $items = $wpdb->get_results( $wpdb->prepare(
            "SELECT i.quantity, b.title_ar, b.title_en, b.isbn
             FROM {$wpdb->prefix}rsyi_lib_withdrawal_order_items i
             LEFT JOIN {$wpdb->prefix}rsyi_books b ON b.id = i.book_id
             WHERE i.order_id = %d", $order->id
        ) ) ?: [];

        $order_num  = esc_html( $order->order_number ?? '' );
        $date       = date_i18n( 'd/m/Y H:i', strtotime( $order->created_at ?? 'now' ) );
        $cohort     = esc_html( $order->cohort_name ?? '' );
        $notes      = esc_html( $order->notes ?? '' );
        $admin_url  = admin_url( 'admin.php?page=rsyi-library' );
        $inst_name  = esc_html( get_option( 'rsyi_institute_name', 'Red Sea Yachting Institute' ) );

        $items_rows_html = '';
        $items_rows_text = '';
        foreach ( $items as $item ) {
            $title = esc_html( $item->title_ar ?: $item->title_en ?: '' );
            $isbn  = esc_html( $item->isbn ?: '—' );
            $qty   = (int) $item->quantity;
            $items_rows_html .= "<tr>
                <td style='padding:7px 10px;border:1px solid #ddd;'>{$title}</td>
                <td style='padding:7px 10px;border:1px solid #ddd;text-align:center;'>{$isbn}</td>
                <td style='padding:7px 10px;border:1px solid #ddd;text-align:center;'>{$qty}</td>
            </tr>";
            $items_rows_text .= "  - {$title}  (ISBN: {$isbn})  × {$qty}\n";
        }

        $subject = "إذن صرف جديد / New Withdrawal Order: {$order_num}";

        $items_table_html = $items_rows_html ? "
        <h3 style='margin:20px 0 8px;font-size:14px;color:#555;'>الأصناف / Items</h3>
        <table style='width:100%;border-collapse:collapse;font-size:13px;'>
          <thead><tr style='background:#f0f4f8;'>
            <th style='padding:7px 10px;border:1px solid #ddd;text-align:right;'>الصنف / Item</th>
            <th style='padding:7px 10px;border:1px solid #ddd;'>ISBN</th>
            <th style='padding:7px 10px;border:1px solid #ddd;width:70px;'>الكمية</th>
          </tr></thead>
          <tbody>{$items_rows_html}</tbody>
        </table>" : '';

        $cohort_row  = $cohort ? "<tr><td style='padding:6px 12px;background:#fafafa;font-weight:600;width:160px;'>المجموعة / Cohort</td><td style='padding:6px 12px;border-bottom:1px solid #eee;'>{$cohort}</td></tr>" : '';
        $notes_row   = $notes  ? "<tr><td style='padding:6px 12px;background:#fafafa;font-weight:600;'>ملاحظات / Notes</td><td style='padding:6px 12px;border-bottom:1px solid #eee;'>{$notes}</td></tr>" : '';

        $html_body = "<!DOCTYPE html><html dir='rtl'><head><meta charset='UTF-8'></head>
<body style='font-family:Arial,sans-serif;background:#f4f4f4;padding:24px;'>
<div style='max-width:620px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1);'>
  <div style='background:#0073aa;padding:18px 24px;text-align:center;'>
    <h2 style='margin:0;color:#fff;font-size:17px;'>⚓ {$inst_name}</h2>
    <p style='margin:6px 0 0;color:#cce4f4;font-size:13px;'>إذن صرف جديد / New Withdrawal Order</p>
  </div>
  <div style='padding:24px;'>
    <p style='margin:0 0 18px;font-size:14px;line-height:1.7;'>
      تم إنشاء إذن صرف جديد يحتاج إلى <strong>اعتمادكم</strong>.<br>
      <span style='color:#666;'>A new withdrawal order has been created and requires your approval.</span>
    </p>
    <table style='width:100%;border-collapse:collapse;font-size:14px;margin-bottom:4px;'>
      <tr><td style='padding:6px 12px;background:#fafafa;font-weight:600;width:160px;'>رقم الإذن / Order No.</td><td style='padding:6px 12px;border-bottom:1px solid #eee;'><strong>{$order_num}</strong></td></tr>
      <tr><td style='padding:6px 12px;background:#fafafa;font-weight:600;'>التاريخ / Date</td><td style='padding:6px 12px;border-bottom:1px solid #eee;'>{$date}</td></tr>
      {$cohort_row}{$notes_row}
    </table>
    {$items_table_html}
    <div style='margin-top:28px;text-align:center;'>
      <a href='{$admin_url}' style='background:#0073aa;color:#fff;padding:13px 32px;text-decoration:none;border-radius:5px;font-size:14px;font-weight:bold;display:inline-block;'>
        👁 عرض الإذن واعتماده / View &amp; Approve
      </a>
    </div>
    <p style='margin-top:20px;font-size:11px;color:#aaa;text-align:center;'>
      {$admin_url}
    </p>
  </div>
</div>
</body></html>";

        $text_body = "إذن صرف جديد / New Withdrawal Order: {$order_num}\n"
            . str_repeat( '-', 40 ) . "\n"
            . "رقم الإذن: {$order_num}\n"
            . "التاريخ:   {$date}\n"
            . ( $cohort ? "المجموعة: {$cohort}\n" : '' )
            . ( $notes  ? "ملاحظات:  {$notes}\n"  : '' )
            . ( $items_rows_text ? "\nالأصناف:\n{$items_rows_text}" : '' )
            . "\n" . str_repeat( '-', 40 ) . "\n"
            . "رابط الاعتماد:\n{$admin_url}\n";

        $headers = [ 'Content-Type: text/html; charset=UTF-8' ];
        foreach ( $emails as $email ) {
            wp_mail( $email, $subject, $html_body, $headers );
        }
    }

    // ── Public helpers for templates ─────────────────────────────────────────
    static function get_cohorts(): array {
        global $wpdb;
        return $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}rsyi_cohorts WHERE is_active=1 ORDER BY name" ) ?: [];
    }

    static function get_trainers(): array {
        return get_users( [ 'role__in' => [ 'rsyi_naval_trainer', 'rsyi_senior_naval_trainer' ], 'fields' => [ 'ID', 'display_name' ] ] );
    }

    static function get_active_books(): array {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT id, title_ar, title_en, current_stock, min_stock, isbn, subject, grade_level FROM {$wpdb->prefix}rsyi_books WHERE is_active=1 ORDER BY title_ar"
        ) ?: [];
    }
}
