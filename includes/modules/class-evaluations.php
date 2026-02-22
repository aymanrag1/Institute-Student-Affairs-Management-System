<?php
/**
 * Evaluations Module
 *
 * Manages student supervisor evaluation system:
 *   - Evaluation Periods (tied to a cohort)
 *   - Peer Evaluations   (student rates supervisor on 5 criteria, /10 each = /50 max)
 *   - Admin Evaluations  (Student Affairs Manager / Dorm Supervisor rates supervisor on 6 criteria, /10 each = /60 max)
 *   - Aggregation Table  (combined view per period)
 *
 * Peer criteria (5):
 *   1. Self-discipline in behavior and timing
 *   2. Dealing with colleagues
 *   3. Sense of responsibility
 *   4. Decision making ability
 *   5. Problem solving ability
 *
 * Admin/Supervisor criteria (6):
 *   1. Self-discipline in behavior and timing
 *   2. Dealing with colleagues
 *   3. Sense of responsibility
 *   4. Decision making – understanding and comprehending the mission
 *   5. Problem solving – confidentiality in information transfer
 *   6. Good use of authority / privileges
 *
 * @package RSYI_StudentAffairs
 */

namespace RSYI_SA\Modules;

defined( 'ABSPATH' ) || exit;

class Evaluations {

    // ── Static criterion labels ───────────────────────────────────────────────

    public static function get_peer_criteria(): array {
        return [
            1 => __( 'Self-discipline in behavior and timing', 'rsyi-sa' ),
            2 => __( 'Dealing with colleagues', 'rsyi-sa' ),
            3 => __( 'Sense of responsibility', 'rsyi-sa' ),
            4 => __( 'Decision making ability', 'rsyi-sa' ),
            5 => __( 'Problem solving ability', 'rsyi-sa' ),
        ];
    }

    public static function get_admin_criteria(): array {
        return [
            1 => __( 'Self-discipline in behavior and timing', 'rsyi-sa' ),
            2 => __( 'Dealing with colleagues', 'rsyi-sa' ),
            3 => __( 'Sense of responsibility', 'rsyi-sa' ),
            4 => __( 'Decision making – understanding and comprehending the mission', 'rsyi-sa' ),
            5 => __( 'Problem solving – confidentiality in information transfer', 'rsyi-sa' ),
            6 => __( 'Good use of authority / privileges', 'rsyi-sa' ),
        ];
    }

    // ── Bootstrap ─────────────────────────────────────────────────────────────

    public static function init(): void {
        // Period management
        add_action( 'wp_ajax_rsyi_create_evaluation_period', [ __CLASS__, 'ajax_create_period' ] );
        add_action( 'wp_ajax_rsyi_toggle_evaluation_period', [ __CLASS__, 'ajax_toggle_period' ] );

        // Admin / supervisor evaluation
        add_action( 'wp_ajax_rsyi_save_admin_evaluation',    [ __CLASS__, 'ajax_save_admin_evaluation' ] );

        // Peer evaluation (submitted by students or student supervisors)
        add_action( 'wp_ajax_rsyi_save_peer_evaluation',     [ __CLASS__, 'ajax_save_peer_evaluation' ] );
    }

    // ── AJAX: Create evaluation period ────────────────────────────────────────

    public static function ajax_create_period(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );

        if ( ! current_user_can( 'rsyi_manage_evaluation_periods' ) ) {
            wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rsyi-sa' ) ] );
        }

        $name      = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
        $cohort_id = absint( $_POST['cohort_id'] ?? 0 );
        $start     = sanitize_text_field( wp_unslash( $_POST['start_date'] ?? '' ) );
        $end       = sanitize_text_field( wp_unslash( $_POST['end_date'] ?? '' ) );

        if ( empty( $name ) || ! $cohort_id ) {
            wp_send_json_error( [ 'message' => __( 'Name and cohort are required.', 'rsyi-sa' ) ] );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'rsyi_evaluation_periods';

        $inserted = $wpdb->insert( $table, [
            'name'       => $name,
            'cohort_id'  => $cohort_id,
            'start_date' => $start ?: null,
            'end_date'   => $end ?: null,
            'is_active'  => 1,
            'created_by' => get_current_user_id(),
        ] );

        if ( ! $inserted ) {
            wp_send_json_error( [ 'message' => __( 'Failed to create evaluation period.', 'rsyi-sa' ) ] );
        }

        \RSYI_SA\Audit_Log::log( 'evaluation_period', $wpdb->insert_id, 'create', [
            'name'      => $name,
            'cohort_id' => $cohort_id,
        ] );

        wp_send_json_success( [
            'message'   => __( 'Evaluation period created.', 'rsyi-sa' ),
            'period_id' => $wpdb->insert_id,
        ] );
    }

    // ── AJAX: Toggle period active/inactive ───────────────────────────────────

    public static function ajax_toggle_period(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );

        if ( ! current_user_can( 'rsyi_manage_evaluation_periods' ) ) {
            wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rsyi-sa' ) ] );
        }

        $period_id = absint( $_POST['period_id'] ?? 0 );
        if ( ! $period_id ) {
            wp_send_json_error( [ 'message' => __( 'Invalid period ID.', 'rsyi-sa' ) ] );
        }

        global $wpdb;
        $table  = $wpdb->prefix . 'rsyi_evaluation_periods';
        $period = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $period_id ) );

        if ( ! $period ) {
            wp_send_json_error( [ 'message' => __( 'Period not found.', 'rsyi-sa' ) ] );
        }

        $new_state = $period->is_active ? 0 : 1;
        $wpdb->update( $table, [ 'is_active' => $new_state ], [ 'id' => $period_id ] );

        wp_send_json_success( [
            'message'   => $new_state ? __( 'Period activated.', 'rsyi-sa' ) : __( 'Period deactivated.', 'rsyi-sa' ),
            'is_active' => $new_state,
        ] );
    }

    // ── AJAX: Save admin/supervisor evaluation ────────────────────────────────

    public static function ajax_save_admin_evaluation(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );

        if ( ! current_user_can( 'rsyi_submit_admin_evaluation' ) ) {
            wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rsyi-sa' ) ] );
        }

        $period_id    = absint( $_POST['period_id'] ?? 0 );
        $evaluatee_id = absint( $_POST['evaluatee_id'] ?? 0 );
        $notes        = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );

        if ( ! $period_id || ! $evaluatee_id ) {
            wp_send_json_error( [ 'message' => __( 'Period and evaluatee are required.', 'rsyi-sa' ) ] );
        }

        $criteria = [];
        $total    = 0;
        for ( $i = 1; $i <= 6; $i++ ) {
            $val = absint( $_POST[ "criterion_{$i}" ] ?? 0 );
            // Clamp to [0,10]
            $val           = min( 10, max( 0, $val ) );
            $criteria[ $i ] = $val;
            $total         += $val;
        }

        $current_user = wp_get_current_user();
        $role         = 'unknown';
        if ( in_array( 'rsyi_student_affairs_mgr', (array) $current_user->roles, true ) ) {
            $role = 'student_affairs_mgr';
        } elseif ( in_array( 'rsyi_dorm_supervisor', (array) $current_user->roles, true ) ) {
            $role = 'dorm_supervisor';
        } elseif ( in_array( 'rsyi_dean', (array) $current_user->roles, true ) ) {
            $role = 'dean';
        } elseif ( in_array( 'administrator', (array) $current_user->roles, true ) ) {
            $role = 'administrator';
        }

        global $wpdb;
        $table = $wpdb->prefix . 'rsyi_admin_evaluations';

        // Check if this evaluator already submitted for this evaluatee in this period
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$table} WHERE period_id = %d AND evaluatee_id = %d AND evaluator_id = %d LIMIT 1",
            $period_id, $evaluatee_id, get_current_user_id()
        ) );

        $data = [
            'period_id'    => $period_id,
            'evaluatee_id' => $evaluatee_id,
            'evaluator_id' => get_current_user_id(),
            'evaluator_role' => $role,
            'criterion_1'  => $criteria[1],
            'criterion_2'  => $criteria[2],
            'criterion_3'  => $criteria[3],
            'criterion_4'  => $criteria[4],
            'criterion_5'  => $criteria[5],
            'criterion_6'  => $criteria[6],
            'total'        => $total,
            'notes'        => $notes,
        ];

        if ( $existing ) {
            $wpdb->update( $table, $data, [ 'id' => $existing ] );
            $eval_id = $existing;
            $action  = 'update';
        } else {
            $wpdb->insert( $table, $data );
            $eval_id = $wpdb->insert_id;
            $action  = 'create';
        }

        \RSYI_SA\Audit_Log::log( 'admin_evaluation', $eval_id, $action, [
            'period_id'    => $period_id,
            'evaluatee_id' => $evaluatee_id,
            'total'        => $total,
            'role'         => $role,
        ] );

        wp_send_json_success( [
            'message' => __( 'Evaluation saved successfully.', 'rsyi-sa' ),
            'total'   => $total,
        ] );
    }

    // ── AJAX: Save peer evaluation ────────────────────────────────────────────

    public static function ajax_save_peer_evaluation(): void {
        // Accept both portal nonce (students) and admin nonce (staff)
        $nonce = sanitize_text_field( wp_unslash( $_POST['_nonce'] ?? '' ) );
        if ( ! wp_verify_nonce( $nonce, 'rsyi_sa_portal' ) && ! wp_verify_nonce( $nonce, 'rsyi_sa_admin' ) ) {
            wp_send_json_error( [ 'message' => __( 'Security check failed.', 'rsyi-sa' ) ] );
        }

        if ( ! current_user_can( 'rsyi_submit_peer_evaluation' ) && ! current_user_can( 'rsyi_view_evaluations' ) ) {
            wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rsyi-sa' ) ] );
        }

        $period_id    = absint( $_POST['period_id'] ?? 0 );
        $evaluatee_id = absint( $_POST['evaluatee_id'] ?? 0 );
        $notes        = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );

        if ( ! $period_id || ! $evaluatee_id ) {
            wp_send_json_error( [ 'message' => __( 'Period and evaluatee are required.', 'rsyi-sa' ) ] );
        }

        // Cannot evaluate yourself
        if ( $evaluatee_id === get_current_user_id() ) {
            wp_send_json_error( [ 'message' => __( 'You cannot evaluate yourself.', 'rsyi-sa' ) ] );
        }

        $criteria = [];
        $total    = 0;
        for ( $i = 1; $i <= 5; $i++ ) {
            $val = absint( $_POST[ "criterion_{$i}" ] ?? 0 );
            $val           = min( 10, max( 0, $val ) );
            $criteria[ $i ] = $val;
            $total         += $val;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'rsyi_peer_evaluations';

        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$table} WHERE period_id = %d AND evaluator_id = %d AND evaluatee_id = %d LIMIT 1",
            $period_id, get_current_user_id(), $evaluatee_id
        ) );

        $data = [
            'period_id'    => $period_id,
            'evaluator_id' => get_current_user_id(),
            'evaluatee_id' => $evaluatee_id,
            'criterion_1'  => $criteria[1],
            'criterion_2'  => $criteria[2],
            'criterion_3'  => $criteria[3],
            'criterion_4'  => $criteria[4],
            'criterion_5'  => $criteria[5],
            'total'        => $total,
            'notes'        => $notes,
        ];

        if ( $existing ) {
            $wpdb->update( $table, $data, [ 'id' => $existing ] );
            $eval_id = $existing;
            $action  = 'update';
        } else {
            $wpdb->insert( $table, $data );
            $eval_id = $wpdb->insert_id;
            $action  = 'create';
        }

        \RSYI_SA\Audit_Log::log( 'peer_evaluation', $eval_id, $action, [
            'period_id'    => $period_id,
            'evaluatee_id' => $evaluatee_id,
            'total'        => $total,
        ] );

        wp_send_json_success( [
            'message' => __( 'Peer evaluation saved successfully.', 'rsyi-sa' ),
            'total'   => $total,
        ] );
    }

    // ── Static helpers (used by templates) ───────────────────────────────────

    /**
     * Get all evaluation periods, optionally filtered by cohort.
     */
    public static function get_periods( int $cohort_id = 0 ): array {
        global $wpdb;
        $p_table = $wpdb->prefix . 'rsyi_evaluation_periods';
        $c_table = $wpdb->prefix . 'rsyi_cohorts';

        if ( $cohort_id ) {
            return $wpdb->get_results( $wpdb->prepare(
                "SELECT ep.*, c.name AS cohort_name
                 FROM {$p_table} ep
                 LEFT JOIN {$c_table} c ON c.id = ep.cohort_id
                 WHERE ep.cohort_id = %d
                 ORDER BY ep.created_at DESC",
                $cohort_id
            ) );
        }

        return $wpdb->get_results(
            "SELECT ep.*, c.name AS cohort_name
             FROM {$p_table} ep
             LEFT JOIN {$c_table} c ON c.id = ep.cohort_id
             ORDER BY ep.created_at DESC"
        );
    }

    /**
     * Get all cohorts (active).
     */
    public static function get_cohorts(): array {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}rsyi_cohorts WHERE is_active = 1 ORDER BY name ASC"
        );
    }

    /**
     * Get all active student profiles in a cohort (those being evaluated).
     */
    public static function get_cohort_students( int $cohort_id ): array {
        global $wpdb;
        $sp = $wpdb->prefix . 'rsyi_student_profiles';
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT sp.*, u.display_name
             FROM {$sp} sp
             LEFT JOIN {$wpdb->users} u ON u.ID = sp.user_id
             WHERE sp.cohort_id = %d AND sp.status = 'active'
             ORDER BY sp.english_full_name ASC",
            $cohort_id
        ) );
    }

    /**
     * Build the full aggregation table for a given evaluation period.
     *
     * Returns array of rows, one per evaluatee student, with:
     *   - student info
     *   - peer_scores: sum per criterion over all peer evaluators
     *   - peer_count:  number of peer evaluators who submitted
     *   - admin_scores: criterion scores indexed by evaluator_role
     *   - grand_total per criterion
     *   - overall_total
     */
    public static function get_aggregation( int $period_id ): array {
        global $wpdb;

        $period = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rsyi_evaluation_periods WHERE id = %d",
            $period_id
        ) );

        if ( ! $period ) {
            return [];
        }

        $sp    = $wpdb->prefix . 'rsyi_student_profiles';
        $pe    = $wpdb->prefix . 'rsyi_peer_evaluations';
        $ae    = $wpdb->prefix . 'rsyi_admin_evaluations';

        // All active students in this cohort
        $students = $wpdb->get_results( $wpdb->prepare(
            "SELECT sp.id AS profile_id, sp.user_id, sp.english_full_name, sp.arabic_full_name,
                    u.display_name
             FROM {$sp} sp
             LEFT JOIN {$wpdb->users} u ON u.ID = sp.user_id
             WHERE sp.cohort_id = %d AND sp.status = 'active'
             ORDER BY sp.english_full_name ASC",
            $period->cohort_id
        ) );

        if ( empty( $students ) ) {
            return [];
        }

        // Peer evaluations: sum per evaluatee per criterion
        $peer_rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT evaluatee_id,
                    COUNT(DISTINCT evaluator_id) AS peer_count,
                    SUM(criterion_1) AS c1,
                    SUM(criterion_2) AS c2,
                    SUM(criterion_3) AS c3,
                    SUM(criterion_4) AS c4,
                    SUM(criterion_5) AS c5,
                    SUM(total)       AS peer_total
             FROM {$pe}
             WHERE period_id = %d
             GROUP BY evaluatee_id",
            $period_id
        ) );
        $peer_map = [];
        foreach ( $peer_rows as $r ) {
            $peer_map[ (int) $r->evaluatee_id ] = $r;
        }

        // Admin evaluations: one row per evaluatee per evaluator_role
        $admin_rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT ae.evaluatee_id, ae.evaluator_role,
                    ae.criterion_1, ae.criterion_2, ae.criterion_3,
                    ae.criterion_4, ae.criterion_5, ae.criterion_6,
                    ae.total,
                    u.display_name AS evaluator_name
             FROM {$ae} ae
             LEFT JOIN {$wpdb->users} u ON u.ID = ae.evaluator_id
             WHERE ae.period_id = %d",
            $period_id
        ) );
        $admin_map = [];
        foreach ( $admin_rows as $r ) {
            $admin_map[ (int) $r->evaluatee_id ][ $r->evaluator_role ] = $r;
        }

        $aggregation = [];
        foreach ( $students as $student ) {
            $uid       = (int) $student->user_id;
            $peer      = $peer_map[ $uid ] ?? null;
            $admin     = $admin_map[ $uid ] ?? [];

            // Peer sums per criterion
            $p = [
                'count' => $peer ? (int) $peer->peer_count : 0,
                1 => $peer ? (int) $peer->c1 : 0,
                2 => $peer ? (int) $peer->c2 : 0,
                3 => $peer ? (int) $peer->c3 : 0,
                4 => $peer ? (int) $peer->c4 : 0,
                5 => $peer ? (int) $peer->c5 : 0,
                'total' => $peer ? (int) $peer->peer_total : 0,
            ];

            // Admin scores per role, criteria 1-6
            $sa_mgr = $admin['student_affairs_mgr'] ?? null;
            $dorm   = $admin['dorm_supervisor'] ?? null;
            $dean   = $admin['dean'] ?? null;

            // Grand totals per criterion
            $grand = [];
            for ( $i = 1; $i <= 5; $i++ ) {
                $grand[ $i ] = $p[ $i ]
                    + ( $sa_mgr ? (int) $sa_mgr->{"criterion_{$i}"} : 0 )
                    + ( $dorm   ? (int) $dorm->{"criterion_{$i}"}   : 0 )
                    + ( $dean   ? (int) $dean->{"criterion_{$i}"}   : 0 );
            }
            $grand[6] = 0
                + ( $sa_mgr ? (int) $sa_mgr->criterion_6 : 0 )
                + ( $dorm   ? (int) $dorm->criterion_6   : 0 )
                + ( $dean   ? (int) $dean->criterion_6   : 0 );

            $overall = array_sum( $grand );

            $aggregation[] = [
                'student'    => $student,
                'peer'       => $p,
                'admin'      => [
                    'student_affairs_mgr' => $sa_mgr,
                    'dorm_supervisor'     => $dorm,
                    'dean'                => $dean,
                ],
                'grand'      => $grand,
                'overall'    => $overall,
            ];
        }

        return $aggregation;
    }
}
