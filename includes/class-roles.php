<?php
/**
 * Roles & Capabilities
 *
 * Role hierarchy (highest to lowest):
 *   rsyi_dean                    – Full authority; final approval on overnight, expulsion, cohort transfer
 *   rsyi_student_affairs_mgr     – Student Affairs Manager; approves exit permits step-2, overnight step-2
 *   rsyi_student_supervisor      – Student Supervisor; approves overnight step-1, logs violations (≤10 pts)
 *   rsyi_dorm_supervisor         – Dorm Supervisor; approves exit permits step-1, prints daily PDF
 *   rsyi_senior_naval_trainer    – كبير المدربين البحريين: حضور/غياب + رفع مواد + امتحانات
 *   rsyi_naval_trainer           – المدرب البحري: حضور/غياب + رفع مواد + امتحانات
 *   rsyi_preparatory_lecturer    – المحاضر التحضيري: حضور/غياب + رفع مواد + امتحانات
 *   rsyi_student                 – Self-service portal access
 *
 * @package RSYI_StudentAffairs
 */

namespace RSYI_SA;

defined( 'ABSPATH' ) || exit;

class Roles {

    /**
     * Role definitions: role_slug => [ label, capabilities[] ]
     */
    private static array $role_definitions = [];

    public static function define(): void {
        self::$role_definitions = [

            'rsyi_dean' => [
                'label' => 'Dean / عميد',
                'caps'  => [
                    // Global read
                    'rsyi_view_all_students'       => true,
                    'rsyi_view_all_documents'      => true,
                    'rsyi_view_all_requests'       => true,
                    'rsyi_view_all_violations'     => true,
                    'rsyi_view_audit_log'          => true,
                    // Students
                    'rsyi_create_student'          => true,
                    'rsyi_edit_student'            => true,
                    'rsyi_suspend_student'         => true,
                    'rsyi_delete_student'          => true,
                    // Documents
                    'rsyi_approve_document'        => true,
                    'rsyi_reject_document'         => true,
                    // Exit permits
                    'rsyi_approve_exit_permit'     => true,
                    'rsyi_reject_exit_permit'      => true,
                    // Overnight permits
                    'rsyi_approve_overnight_permit'=> true,
                    'rsyi_reject_overnight_permit' => true,
                    // Violations
                    'rsyi_create_violation'        => true,
                    'rsyi_assign_violation_points' => true,   // up to 30
                    'rsyi_overturn_violation'      => true,
                    'rsyi_manage_violation_types'  => true,
                    // Expulsion
                    'rsyi_manage_expulsion'        => true,
                    'rsyi_approve_expulsion'       => true,
                    // Cohorts
                    'rsyi_manage_cohorts'          => true,
                    'rsyi_approve_cohort_transfer' => true,
                    // PDF
                    'rsyi_print_daily_report'      => true,
                    // Evaluations
                    'rsyi_view_evaluations'            => true,
                    'rsyi_manage_evaluation_periods'   => true,
                    'rsyi_submit_admin_evaluation'     => true,
                    // Attendance / Materials / Exams
                    'rsyi_manage_attendance'       => true,
                    'rsyi_upload_study_materials'  => true,
                    'rsyi_manage_exams'            => true,
                    // Settings & Roles
                    'rsyi_manage_settings'             => true,
                    'rsyi_manage_roles'                => true,
                ],
            ],

            'rsyi_student_affairs_mgr' => [
                'label' => 'Student Affairs Manager / مدير شؤون الطلاب',
                'caps'  => [
                    'rsyi_view_all_students'       => true,
                    'rsyi_view_all_documents'      => true,
                    'rsyi_view_all_requests'       => true,
                    'rsyi_view_all_violations'     => true,
                    'rsyi_create_student'          => true,
                    'rsyi_edit_student'            => true,
                    'rsyi_approve_document'        => true,
                    'rsyi_reject_document'         => true,
                    'rsyi_approve_exit_permit'     => true,
                    'rsyi_reject_exit_permit'      => true,
                    'rsyi_approve_overnight_permit'=> true,
                    'rsyi_reject_overnight_permit' => true,
                    'rsyi_create_violation'        => true,
                    'rsyi_assign_violation_points' => true,   // up to 20
                    'rsyi_print_daily_report'      => true,
                    // Evaluations
                    'rsyi_view_evaluations'            => true,
                    'rsyi_manage_evaluation_periods'   => true,
                    'rsyi_submit_admin_evaluation'     => true,
                ],
            ],

            'rsyi_student_supervisor' => [
                'label' => 'Student Supervisor / المشرف الأكاديمي',
                'caps'  => [
                    'rsyi_view_all_students'       => true,
                    'rsyi_view_all_documents'      => true,
                    'rsyi_view_all_requests'       => true,
                    'rsyi_create_student'          => true,
                    'rsyi_edit_student'            => true,
                    'rsyi_approve_document'        => true,
                    'rsyi_reject_document'         => true,
                    'rsyi_approve_overnight_permit'=> true,
                    'rsyi_reject_overnight_permit' => true,
                    'rsyi_create_violation'        => true,
                    'rsyi_assign_violation_points' => true,   // up to 10
                ],
            ],

            'rsyi_dorm_supervisor' => [
                'label' => 'Dorm Supervisor / مشرف السكن',
                'caps'  => [
                    'rsyi_view_all_students'       => true,
                    'rsyi_view_all_requests'       => true,
                    'rsyi_approve_exit_permit'     => true,
                    'rsyi_reject_exit_permit'      => true,
                    'rsyi_print_daily_report'      => true,
                    // Evaluations
                    'rsyi_view_evaluations'        => true,
                    'rsyi_submit_admin_evaluation' => true,
                ],
            ],

            // ── NEW: كبير المدربين البحريين ───────────────────────────
            'rsyi_senior_naval_trainer' => [
                'label' => 'Senior Naval Trainer / كبير المدربين البحريين',
                'caps'  => [
                    'rsyi_view_all_students'       => true,
                    // Attendance / Materials / Exams – core features for trainers
                    'rsyi_manage_attendance'       => true,
                    'rsyi_upload_study_materials'  => true,
                    'rsyi_manage_exams'            => true,
                ],
            ],

            // ── NEW: المدرب البحري ────────────────────────────────────
            'rsyi_naval_trainer' => [
                'label' => 'Naval Trainer / المدرب البحري',
                'caps'  => [
                    'rsyi_view_all_students'       => true,
                    'rsyi_manage_attendance'       => true,
                    'rsyi_upload_study_materials'  => true,
                    'rsyi_manage_exams'            => true,
                ],
            ],

            // ── NEW: المحاضر التحضيري ─────────────────────────────────
            'rsyi_preparatory_lecturer' => [
                'label' => 'Preparatory Lecturer / المحاضر التحضيري',
                'caps'  => [
                    'rsyi_view_all_students'       => true,
                    'rsyi_manage_attendance'       => true,
                    'rsyi_upload_study_materials'  => true,
                    'rsyi_manage_exams'            => true,
                ],
            ],

            'rsyi_student' => [
                'label' => 'Student / طالب',
                'caps'  => [
                    'rsyi_view_own_profile'        => true,
                    'rsyi_upload_own_documents'    => true,
                    'rsyi_submit_exit_permit'      => true,
                    'rsyi_submit_overnight_permit' => true,
                    'rsyi_view_own_violations'     => true,
                    'rsyi_acknowledge_warning'     => true,
                    // Evaluations
                    'rsyi_submit_peer_evaluation'  => true,
                ],
            ],
        ];
    }

    /**
     * Add all custom roles (called on activation).
     */
    public static function add_roles(): void {
        self::define();
        foreach ( self::$role_definitions as $slug => $def ) {
            // Remove if exists (ensures fresh cap list on upgrade)
            remove_role( $slug );
            add_role( $slug, $def['label'], $def['caps'] );
        }

        // Grant all RSYI caps to WP Administrator as well
        $admin = get_role( 'administrator' );
        if ( $admin ) {
            self::define();
            $all_caps = [];
            foreach ( self::$role_definitions as $def ) {
                $all_caps = array_merge( $all_caps, $def['caps'] );
            }
            foreach ( array_keys( $all_caps ) as $cap ) {
                $admin->add_cap( $cap, true );
            }
        }
    }

    /**
     * Sync roles without full deactivation/activation cycle.
     * Called on plugins_loaded whenever the stored role-version differs from the current one.
     * Adds missing capabilities and registers new roles; never removes existing custom data.
     */
    public static function sync_roles(): void {
        self::define();

        foreach ( self::$role_definitions as $slug => $def ) {
            $role = get_role( $slug );
            if ( ! $role ) {
                // Role doesn't exist yet → add it fresh
                add_role( $slug, $def['label'], $def['caps'] );
            } else {
                // Role exists → add any missing capabilities
                foreach ( $def['caps'] as $cap => $grant ) {
                    if ( ! isset( $role->capabilities[ $cap ] ) ) {
                        $role->add_cap( $cap, $grant );
                    }
                }
            }
        }

        // Sync administrator as well
        $admin = get_role( 'administrator' );
        if ( $admin ) {
            $all_caps = [];
            foreach ( self::$role_definitions as $def ) {
                $all_caps = array_merge( $all_caps, $def['caps'] );
            }
            foreach ( array_keys( $all_caps ) as $cap ) {
                if ( ! isset( $admin->capabilities[ $cap ] ) ) {
                    $admin->add_cap( $cap, true );
                }
            }
        }

        // Store the version we've synced against
        update_option( 'rsyi_sa_roles_version', RSYI_SA_VERSION );
    }

    /**
     * Remove custom roles (called on deactivation).
     */
    public static function remove_roles(): void {
        $slugs = [
            'rsyi_dean',
            'rsyi_student_affairs_mgr',
            'rsyi_student_supervisor',
            'rsyi_dorm_supervisor',
            'rsyi_senior_naval_trainer',
            'rsyi_naval_trainer',
            'rsyi_preparatory_lecturer',
            'rsyi_student',
        ];
        foreach ( $slugs as $slug ) {
            remove_role( $slug );
        }
    }

    /**
     * Return all role definitions (public access for the permissions screen).
     */
    public static function get_definitions(): array {
        self::define();
        return self::$role_definitions;
    }

    /**
     * Return all unique capability keys across all roles.
     */
    public static function get_all_caps(): array {
        self::define();
        $caps = [];
        foreach ( self::$role_definitions as $def ) {
            foreach ( array_keys( $def['caps'] ) as $cap ) {
                $caps[ $cap ] = true;
            }
        }
        return array_keys( $caps );
    }

    /**
     * Return max violation points a user can assign.
     */
    public static function get_max_violation_points( \WP_User $user ): int {
        if ( $user->has_cap( 'rsyi_manage_expulsion' ) ) {
            return 30; // Dean
        }
        if ( $user->has_cap( 'rsyi_approve_exit_permit' ) && $user->has_cap( 'rsyi_approve_overnight_permit' ) ) {
            // Detect SA Manager (has exit + overnight + no dean cap)
            if ( ! $user->has_cap( 'rsyi_approve_expulsion' ) ) {
                return 20;
            }
        }
        return 10; // Supervisor default
    }
}
