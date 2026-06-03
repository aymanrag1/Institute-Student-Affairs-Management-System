<?php
namespace RSYI_SA\Modules;

defined( 'ABSPATH' ) || exit;

/**
 * FIFO transaction engine for the library warehouse.
 * All stock movement goes through this class.
 */
class Library_Transactions {

    // ── Record an ADD transaction ────────────────────────────────────────────
    static function record_add( int $book_id, int $quantity, float $unit_price, int $add_order_id, int $created_by ): void {
        global $wpdb;
        $current = self::get_current_stock( $book_id );
        $wpdb->insert( $wpdb->prefix . 'rsyi_lib_transactions', [
            'transaction_type' => 'add',
            'add_order_id'     => $add_order_id,
            'book_id'          => $book_id,
            'quantity'         => $quantity,
            'unit_price'       => $unit_price,
            'remaining_qty'    => $current + $quantity,
            'created_by'       => $created_by,
            'created_at'       => current_time( 'mysql' ),
        ] );
        self::sync_stock( $book_id );
    }

    // ── Record a WITHDRAWAL transaction ──────────────────────────────────────
    static function record_withdrawal( int $book_id, int $quantity, int $withdrawal_id, int $created_by ): bool {
        global $wpdb;
        $current = self::get_current_stock( $book_id );
        if ( $current < $quantity ) { return false; }

        // Get avg unit price from available FIFO batches
        $unit_price = self::get_avg_unit_price( $book_id );

        $wpdb->insert( $wpdb->prefix . 'rsyi_lib_transactions', [
            'transaction_type' => 'withdraw',
            'withdrawal_id'    => $withdrawal_id,
            'book_id'          => $book_id,
            'quantity'         => -$quantity,
            'unit_price'       => $unit_price,
            'remaining_qty'    => $current - $quantity,
            'created_by'       => $created_by,
            'created_at'       => current_time( 'mysql' ),
        ] );
        self::sync_stock( $book_id );
        return true;
    }

    // ── Record a RETURN transaction ──────────────────────────────────────────
    static function record_return( int $book_id, int $quantity, int $return_id, int $created_by ): void {
        global $wpdb;
        $current    = self::get_current_stock( $book_id );
        $unit_price = self::get_avg_unit_price( $book_id );
        $wpdb->insert( $wpdb->prefix . 'rsyi_lib_transactions', [
            'transaction_type' => 'return',
            'return_id'        => $return_id,
            'book_id'          => $book_id,
            'quantity'         => $quantity,
            'unit_price'       => $unit_price,
            'remaining_qty'    => $current + $quantity,
            'created_by'       => $created_by,
            'created_at'       => current_time( 'mysql' ),
        ] );
        self::sync_stock( $book_id );
    }

    // ── Record OPENING BALANCE ───────────────────────────────────────────────
    static function record_opening( int $book_id, int $quantity, float $unit_price, int $opening_id, int $created_by ): void {
        global $wpdb;
        $wpdb->insert( $wpdb->prefix . 'rsyi_lib_transactions', [
            'transaction_type' => 'opening',
            'opening_id'       => $opening_id,
            'book_id'          => $book_id,
            'quantity'         => $quantity,
            'unit_price'       => $unit_price,
            'remaining_qty'    => $quantity,
            'created_by'       => $created_by,
            'created_at'       => current_time( 'mysql' ),
        ] );
        self::sync_stock( $book_id );
    }

    // ── Reverse transactions for an add order (on delete) ───────────────────
    static function reverse_add_order( int $add_order_id, int $created_by ): void {
        global $wpdb;
        $items = $wpdb->get_results( $wpdb->prepare(
            "SELECT book_id, quantity FROM {$wpdb->prefix}rsyi_lib_transactions WHERE add_order_id=%d AND transaction_type='add'",
            $add_order_id
        ) );
        foreach ( $items as $row ) {
            $current = self::get_current_stock( $row->book_id );
            $wpdb->insert( $wpdb->prefix . 'rsyi_lib_transactions', [
                'transaction_type' => 'reversal',
                'add_order_id'     => $add_order_id,
                'book_id'          => $row->book_id,
                'quantity'         => -$row->quantity,
                'unit_price'       => 0,
                'remaining_qty'    => max( 0, $current - $row->quantity ),
                'created_by'       => $created_by,
                'created_at'       => current_time( 'mysql' ),
            ] );
            self::sync_stock( $row->book_id );
        }
    }

    // ── Get current stock (sum of all transactions) ──────────────────────────
    static function get_current_stock( int $book_id ): int {
        global $wpdb;
        $val = $wpdb->get_var( $wpdb->prepare(
            "SELECT COALESCE(SUM(quantity),0) FROM {$wpdb->prefix}rsyi_lib_transactions WHERE book_id=%d", $book_id
        ) );
        return (int) $val;
    }

    // ── Get real available stock (excluding pending/approved withdrawal reservations) ─
    static function get_real_stock( int $book_id, ?int $exclude_withdrawal_id = null ): int {
        global $wpdb;
        $reserved_sql = "SELECT COALESCE(SUM(i.quantity),0)
            FROM {$wpdb->prefix}rsyi_lib_withdrawal_order_items i
            JOIN {$wpdb->prefix}rsyi_lib_withdrawal_orders o ON o.id = i.order_id
            WHERE i.book_id = %d AND o.status IN ('pending','approved')";
        $params = [ $book_id ];
        if ( $exclude_withdrawal_id ) {
            $reserved_sql .= ' AND o.id != %d';
            $params[] = $exclude_withdrawal_id;
        }
        $reserved = (int) $wpdb->get_var( $wpdb->prepare( $reserved_sql, ...$params ) );
        return max( 0, self::get_current_stock( $book_id ) - $reserved );
    }

    // ── Get average unit price from latest add transactions ──────────────────
    static function get_avg_unit_price( int $book_id ): float {
        global $wpdb;
        $val = $wpdb->get_var( $wpdb->prepare(
            "SELECT AVG(unit_price) FROM {$wpdb->prefix}rsyi_lib_transactions
             WHERE book_id=%d AND transaction_type IN ('add','opening') AND remaining_qty > 0",
            $book_id
        ) );
        return (float) ( $val ?? 0 );
    }

    // ── Sync current_stock + available_copies on rsyi_books ─────────────────
    static function sync_stock( int $book_id ): void {
        global $wpdb;
        $stock = self::get_current_stock( $book_id );
        $wpdb->update( $wpdb->prefix . 'rsyi_books',
            [ 'current_stock' => $stock, 'available_copies' => max( 0, $stock ), 'updated_at' => current_time( 'mysql' ) ],
            [ 'id' => $book_id ]
        );
    }

    // ── Sync all books ───────────────────────────────────────────────────────
    static function sync_all_stocks(): void {
        global $wpdb;
        $books = $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}rsyi_books WHERE is_active=1" );
        foreach ( (array) $books as $bid ) {
            self::sync_stock( (int) $bid );
        }
    }

    // ── Get movement history for a book (with running balance) ──────────────
    static function get_book_movement( int $book_id ): array {
        global $wpdb;
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT t.*,
                    b.title_ar, b.title_en,
                    u.display_name AS created_by_name
             FROM {$wpdb->prefix}rsyi_lib_transactions t
             LEFT JOIN {$wpdb->prefix}rsyi_books b ON b.id = t.book_id
             LEFT JOIN {$wpdb->users} u ON u.ID = t.created_by
             WHERE t.book_id = %d
             ORDER BY t.created_at ASC", $book_id
        ) );
        return $rows ?: [];
    }

    // ── Generate order number ────────────────────────────────────────────────
    static function generate_order_number( string $prefix ): string {
        global $wpdb;
        $year  = date( 'Y' );
        $table_map = [
            'ADD' => 'rsyi_lib_add_orders',
            'WD'  => 'rsyi_lib_withdrawal_orders',
            'RET' => 'rsyi_lib_return_orders',
            'PR'  => 'rsyi_lib_purchase_requests',
        ];
        $table = $wpdb->prefix . ( $table_map[ $prefix ] ?? 'rsyi_lib_add_orders' );
        $count = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM `{$table}` WHERE YEAR(created_at)=%d", $year
        ) );
        return $prefix . '-' . $year . '-' . str_pad( $count + 1, 5, '0', STR_PAD_LEFT );
    }
}
