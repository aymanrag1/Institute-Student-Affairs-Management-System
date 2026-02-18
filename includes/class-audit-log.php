<?php
/**
 * Audit Log
 *
 * Writes an immutable audit record for every create/update/approve/reject/execute action.
 *
 * @package RSYI_StudentAffairs
 */

namespace RSYI_SA;

defined( 'ABSPATH' ) || exit;

class Audit_Log {

    /**
     * Write a log entry.
     *
     * @param string $entity_type  e.g. 'student_profile', 'document', 'exit_permit'
     * @param int    $entity_id    Primary key of the affected record
     * @param string $action       e.g. 'create', 'approve', 'reject', 'execute', 'update'
     * @param array  $details      Arbitrary extra context (will be JSON-encoded)
     * @param int    $actor_id     WP user ID; defaults to current user
     */
    public static function log(
        string $entity_type,
        int    $entity_id,
        string $action,
        array  $details = [],
        int    $actor_id = 0
    ): void {
        global $wpdb;

        if ( $actor_id === 0 ) {
            $actor_id = get_current_user_id();
        }

        $wpdb->insert(
            $wpdb->prefix . 'rsyi_audit_log',
            [
                'actor_user_id' => $actor_id,
                'entity_type'   => sanitize_key( $entity_type ),
                'entity_id'     => $entity_id,
                'action'        => sanitize_key( $action ),
                'details_json'  => ! empty( $details ) ? wp_json_encode( $details, JSON_UNESCAPED_UNICODE ) : null,
                'ip_address'    => self::get_client_ip(),
                'user_agent'    => isset( $_SERVER['HTTP_USER_AGENT'] )
                                    ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 255 )
                                    : null,
                'created_at'    => current_time( 'mysql', true ), // UTC
            ],
            [ '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s' ]
        );
    }

    /**
     * Retrieve log entries for a specific entity.
     *
     * @return array<object>
     */
    public static function get_for_entity( string $entity_type, int $entity_id, int $limit = 50 ): array {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT l.*, u.display_name AS actor_name
                 FROM {$wpdb->prefix}rsyi_audit_log l
                 LEFT JOIN {$wpdb->users} u ON u.ID = l.actor_user_id
                 WHERE l.entity_type = %s AND l.entity_id = %d
                 ORDER BY l.created_at DESC
                 LIMIT %d",
                $entity_type,
                $entity_id,
                $limit
            )
        );
    }

    /**
     * Retrieve recent log entries (admin overview).
     *
     * @return array<object>
     */
    public static function get_recent( int $limit = 100, int $offset = 0 ): array {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT l.*, u.display_name AS actor_name
                 FROM {$wpdb->prefix}rsyi_audit_log l
                 LEFT JOIN {$wpdb->users} u ON u.ID = l.actor_user_id
                 ORDER BY l.created_at DESC
                 LIMIT %d OFFSET %d",
                $limit,
                $offset
            )
        );
    }

    /**
     * Get the client IP, aware of common proxy headers.
     */
    private static function get_client_ip(): string {
        $keys = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];
        foreach ( $keys as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
                // X-Forwarded-For can be a comma-list; take the first
                if ( str_contains( $ip, ',' ) ) {
                    $ip = trim( explode( ',', $ip )[0] );
                }
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }
}
