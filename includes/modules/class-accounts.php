<?php
/**
 * Accounts Module
 *
 * Handles student self-registration and staff-created profiles.
 * A student account starts in 'pending_docs' status and becomes
 * 'active' only when all 8 mandatory documents are approved.
 *
 * @package RSYI_StudentAffairs
 */

namespace RSYI_SA\Modules;

use RSYI_SA\Audit_Log;
use RSYI_SA\Email_Notifications;

defined( 'ABSPATH' ) || exit;

class Accounts {

    /** All 8 mandatory document types */
    public const MANDATORY_DOC_TYPES = [
        'DOC01_national_id_front',
        'DOC02_national_id_back',
        'DOC03_birth_certificate',
        'DOC04_military_certificate',
        'DOC05_highschool_certificate',
        'DOC06_graduation_certificate',
        'DOC07_police_record_foundation',
        'DOC08_police_record_authority',
    ];

    public const DOC_TYPE_LABELS = [
        'DOC01_national_id_front'        => 'بطاقة الرقم القومي (وجه)',
        'DOC02_national_id_back'         => 'بطاقة الرقم القومي (ظهر)',
        'DOC03_birth_certificate'        => 'شهادة الميلاد',
        'DOC04_military_certificate'     => 'الشهادة العسكرية',
        'DOC05_highschool_certificate'   => 'شهادة الثانوية العامة',
        'DOC06_graduation_certificate'   => 'شهادة التخرج',
        'DOC07_police_record_foundation' => 'فيش وتشبيه (التأسيس)',
        'DOC08_police_record_authority'  => 'فيش وتشبيه (الجهة)',
    ];

    public static function init(): void {
        // Self-registration form processing
        add_action( 'wp_ajax_nopriv_rsyi_register_student', [ __CLASS__, 'ajax_register_student' ] );
        // Staff-created student (admin AJAX)
        add_action( 'wp_ajax_rsyi_staff_create_student',    [ __CLASS__, 'ajax_staff_create_student' ] );
        // Profile update
        add_action( 'wp_ajax_rsyi_update_student_profile',  [ __CLASS__, 'ajax_update_profile' ] );
        // Bulk import: parse uploaded file then process batch
        add_action( 'wp_ajax_rsyi_parse_import_file',       [ __CLASS__, 'ajax_parse_import_file' ] );
        add_action( 'wp_ajax_rsyi_import_students_batch',   [ __CLASS__, 'ajax_import_students_batch' ] );
        // Download blank import template (CSV)
        add_action( 'wp_ajax_rsyi_download_import_template',[ __CLASS__, 'ajax_download_import_template' ] );
        // Delete student
        add_action( 'wp_ajax_rsyi_delete_student',           [ __CLASS__, 'ajax_delete_student' ] );
    }

    // ── Registration ─────────────────────────────────────────────────────────

    /**
     * Self-registration: create WP user + student profile.
     * AJAX endpoint: rsyi_register_student
     */
    public static function ajax_register_student(): void {
        check_ajax_referer( 'rsyi_sa_portal', '_nonce' );

        $data = self::sanitize_registration_input( $_POST );
        $errors = self::validate_registration( $data );

        if ( ! empty( $errors ) ) {
            wp_send_json_error( [ 'errors' => $errors ] );
        }

        // Create WP user
        $user_id = wp_insert_user( [
            'user_login' => $data['user_login'],
            'user_email' => $data['user_email'],
            'user_pass'  => $data['password'],
            'role'       => 'rsyi_student',
            'first_name' => $data['english_first_name'],
            'last_name'  => $data['english_last_name'],
            'display_name' => $data['english_full_name'],
        ] );

        if ( is_wp_error( $user_id ) ) {
            wp_send_json_error( [ 'errors' => [ $user_id->get_error_message() ] ] );
        }

        $profile_id = self::create_profile( $user_id, $data, 0 );

        Audit_Log::log( 'student_profile', $profile_id, 'create', [
            'method'     => 'self_registration',
            'cohort_id'  => $data['cohort_id'],
        ], $user_id );

        // Auto-login the new student so they can immediately upload documents
        wp_set_auth_cookie( $user_id, false );
        wp_set_current_user( $user_id );

        wp_send_json_success( [
            'message'    => __( 'Account created successfully. Please upload your required documents.', 'rsyi-sa' ),
            'profile_id' => $profile_id,
        ] );
    }

    /**
     * Staff-created student.
     * AJAX endpoint: rsyi_staff_create_student
     */
    public static function ajax_staff_create_student(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );

        if ( ! current_user_can( 'rsyi_create_student' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        $data = self::sanitize_registration_input( $_POST );
        $errors = self::validate_registration( $data );

        if ( ! empty( $errors ) ) {
            wp_send_json_error( [ 'errors' => $errors ] );
        }

        $user_id = wp_insert_user( [
            'user_login'   => $data['user_login'],
            'user_email'   => $data['user_email'],
            'user_pass'    => $data['password'],
            'role'         => 'rsyi_student',
            'display_name' => $data['english_full_name'],
        ] );

        if ( is_wp_error( $user_id ) ) {
            wp_send_json_error( [ 'errors' => [ $user_id->get_error_message() ] ] );
        }

        $creator_id = get_current_user_id();
        // Staff-created students become active immediately (no document requirement).
        $profile_id = self::create_profile( $user_id, $data, $creator_id, 'active' );

        Audit_Log::log( 'student_profile', $profile_id, 'create', [
            'method'    => 'staff_created',
            'cohort_id' => $data['cohort_id'],
        ], $creator_id );

        wp_send_json_success( [
            'message'    => __( 'تم إنشاء ملف الطالب بنجاح.', 'rsyi-sa' ),
            'profile_id' => $profile_id,
            'user_id'    => $user_id,
        ] );
    }

    /**
     * Update student profile fields (staff or student own).
     */
    public static function ajax_update_profile(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );

        $profile_id = (int) ( $_POST['profile_id'] ?? 0 );
        $profile    = self::get_profile_by_id( $profile_id );

        if ( ! $profile ) {
            wp_send_json_error( [ 'message' => __( 'الملف غير موجود.', 'rsyi-sa' ) ] );
        }

        $current_user = wp_get_current_user();
        $is_own_student = ( (int) $profile->user_id === (int) $current_user->ID );
        if ( ! $is_own_student && ! $current_user->has_cap( 'rsyi_edit_student' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        global $wpdb;
        $update = [];
        if ( isset( $_POST['arabic_full_name'] ) ) {
            $update['arabic_full_name'] = sanitize_text_field( wp_unslash( $_POST['arabic_full_name'] ) );
        }
        if ( isset( $_POST['english_full_name'] ) ) {
            $update['english_full_name'] = sanitize_text_field( wp_unslash( $_POST['english_full_name'] ) );
        }
        if ( isset( $_POST['phone'] ) ) {
            $update['phone'] = sanitize_text_field( wp_unslash( $_POST['phone'] ) );
        }
        if ( isset( $_POST['date_of_birth'] ) ) {
            $update['date_of_birth'] = sanitize_text_field( wp_unslash( $_POST['date_of_birth'] ) );
        }
        if ( isset( $_POST['national_id_number'] ) ) {
            $update['national_id_number'] = sanitize_text_field( wp_unslash( $_POST['national_id_number'] ) );
        }
        if ( isset( $_POST['cohort_id'] ) && current_user_can( 'rsyi_edit_student' ) ) {
            $cohort_id = absint( $_POST['cohort_id'] );
            if ( $cohort_id > 0 ) {
                $update['cohort_id'] = $cohort_id;
            }
        }

        if ( empty( $update ) ) {
            wp_send_json_error( [ 'message' => __( 'لا توجد بيانات للتحديث.', 'rsyi-sa' ) ] );
        }

        $wpdb->update(
            $wpdb->prefix . 'rsyi_student_profiles',
            $update,
            [ 'id' => $profile_id ],
            array_fill( 0, count( $update ), '%s' ),
            [ '%d' ]
        );

        Audit_Log::log( 'student_profile', $profile_id, 'update', $update );

        wp_send_json_success( [ 'message' => __( 'تم تحديث البيانات بنجاح.', 'rsyi-sa' ) ] );
    }

    /**
     * Delete a student: removes the WP user and all related custom-table records.
     * AJAX endpoint: rsyi_delete_student
     */
    public static function ajax_delete_student(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );

        if ( ! current_user_can( 'rsyi_delete_student' ) ) {
            wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rsyi-sa' ) ] );
        }

        $profile_id = absint( $_POST['profile_id'] ?? 0 );
        if ( ! $profile_id ) {
            wp_send_json_error( [ 'message' => __( 'Invalid student ID.', 'rsyi-sa' ) ] );
        }

        $profile = self::get_profile_by_id( $profile_id );
        if ( ! $profile ) {
            wp_send_json_error( [ 'message' => __( 'Student not found.', 'rsyi-sa' ) ] );
        }

        $user_id = (int) $profile->user_id;

        global $wpdb;
        $prefix = $wpdb->prefix;

        // Delete related records from custom tables
        $wpdb->delete( "{$prefix}rsyi_documents",        [ 'student_id' => $profile_id ], [ '%d' ] );
        $wpdb->delete( "{$prefix}rsyi_behavior_records", [ 'student_id' => $profile_id ], [ '%d' ] );
        $wpdb->delete( "{$prefix}rsyi_exit_permits",     [ 'student_id' => $profile_id ], [ '%d' ] );
        $wpdb->delete( "{$prefix}rsyi_overnight_permits",[ 'student_id' => $profile_id ], [ '%d' ] );
        $wpdb->delete( "{$prefix}rsyi_peer_evaluations", [ 'evaluatee_id' => $profile_id ], [ '%d' ] );
        $wpdb->delete( "{$prefix}rsyi_peer_evaluations", [ 'evaluator_id' => $profile_id ], [ '%d' ] );
        $wpdb->delete( "{$prefix}rsyi_admin_evaluations",[ 'student_id'  => $profile_id ], [ '%d' ] );

        // Delete the profile row
        $wpdb->delete( "{$prefix}rsyi_student_profiles", [ 'id' => $profile_id ], [ '%d' ] );

        // Log before deleting user (so we have a record)
        Audit_Log::log( 'student_profile', $profile_id, 'delete', [
            'deleted_by' => get_current_user_id(),
            'user_id'    => $user_id,
        ] );

        // Delete the WordPress user (reassign content to admin)
        require_once ABSPATH . 'wp-admin/includes/user.php';
        wp_delete_user( $user_id );

        wp_send_json_success( [ 'message' => __( 'Student deleted successfully.', 'rsyi-sa' ) ] );
    }

    // ── Status helpers ────────────────────────────────────────────────────────

    /**
     * Check if all 8 mandatory documents are approved; if so, activate student.
     * Called by Documents module after each approval.
     */
    public static function maybe_activate_student( int $student_profile_id ): void {
        global $wpdb;

        $approved = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT doc_type FROM {$wpdb->prefix}rsyi_documents
                 WHERE student_id = %d AND status = 'approved'",
                $student_profile_id
            )
        );

        $missing = array_diff( self::MANDATORY_DOC_TYPES, $approved );
        if ( ! empty( $missing ) ) {
            return;
        }

        // All approved – activate
        $wpdb->update(
            $wpdb->prefix . 'rsyi_student_profiles',
            [ 'status' => 'active' ],
            [ 'id' => $student_profile_id ],
            [ '%s' ],
            [ '%d' ]
        );

        $profile = self::get_profile_by_id( $student_profile_id );
        if ( $profile ) {
            Email_Notifications::all_documents_approved( (int) $profile->user_id );
            Audit_Log::log( 'student_profile', $student_profile_id, 'activate', [
                'trigger' => 'all_documents_approved',
            ] );
        }
    }

    // ── CRUD helpers ─────────────────────────────────────────────────────────

    private static function create_profile( int $user_id, array $data, int $creator_id, string $initial_status = 'pending_docs' ): int {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'rsyi_student_profiles',
            [
                'user_id'           => $user_id,
                'cohort_id'         => $data['cohort_id'],
                'arabic_full_name'  => $data['arabic_full_name'],
                'english_full_name' => $data['english_full_name'],
                'national_id_number'=> $data['national_id_number'] ?? '',
                'date_of_birth'     => $data['date_of_birth']      ?? null,
                'phone'             => $data['phone']              ?? null,
                'status'            => $initial_status,
                'created_by'        => $creator_id ?: null,
            ],
            [ '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d' ]
        );
        return (int) $wpdb->insert_id;
    }

    public static function get_profile_by_user_id( int $user_id ): ?object {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}rsyi_student_profiles WHERE user_id = %d",
                $user_id
            )
        );
    }

    public static function get_profile_by_id( int $id ): ?object {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}rsyi_student_profiles WHERE id = %d",
                $id
            )
        );
    }

    public static function get_all_students( array $args = [] ): array {
        global $wpdb;
        $where  = '1=1';
        $params = [];

        if ( ! empty( $args['cohort_id'] ) ) {
            $where   .= ' AND sp.cohort_id = %d';
            $params[] = (int) $args['cohort_id'];
        }
        if ( ! empty( $args['status'] ) ) {
            $where   .= ' AND sp.status = %s';
            $params[] = sanitize_key( $args['status'] );
        }
        if ( ! empty( $args['search'] ) ) {
            $like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $where   .= ' AND (sp.arabic_full_name LIKE %s OR sp.english_full_name LIKE %s OR u.user_email LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $limit  = isset( $args['per_page'] ) ? (int) $args['per_page'] : 20;
        $offset = isset( $args['page'] )     ? ( (int) $args['page'] - 1 ) * $limit : 0;
        $params[] = $limit;
        $params[] = $offset;

        $sql = "SELECT sp.*, u.user_email, u.user_login, c.name AS cohort_name
                FROM {$wpdb->prefix}rsyi_student_profiles sp
                JOIN {$wpdb->users} u ON u.ID = sp.user_id
                LEFT JOIN {$wpdb->prefix}rsyi_cohorts c ON c.id = sp.cohort_id
                WHERE {$where}
                ORDER BY sp.created_at DESC
                LIMIT %d OFFSET %d";

        return ! empty( $params )
            ? $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) )
            : $wpdb->get_results( $sql );
    }

    // ── Input helpers ─────────────────────────────────────────────────────────

    private static function sanitize_registration_input( array $post ): array {
        return [
            'user_login'         => sanitize_user( wp_unslash( $post['user_login']         ?? '' ) ),
            'user_email'         => sanitize_email( wp_unslash( $post['user_email']         ?? '' ) ),
            'password'           => wp_unslash( $post['password']           ?? '' ),
            'arabic_full_name'   => sanitize_text_field( wp_unslash( $post['arabic_full_name']   ?? '' ) ),
            'english_full_name'  => sanitize_text_field( wp_unslash( $post['english_full_name']  ?? '' ) ),
            'english_first_name' => sanitize_text_field( wp_unslash( $post['english_first_name'] ?? '' ) ),
            'english_last_name'  => sanitize_text_field( wp_unslash( $post['english_last_name']  ?? '' ) ),
            'national_id_number' => sanitize_text_field( wp_unslash( $post['national_id_number'] ?? '' ) ),
            'date_of_birth'      => sanitize_text_field( wp_unslash( $post['date_of_birth']      ?? '' ) ),
            'phone'              => sanitize_text_field( wp_unslash( $post['phone']              ?? '' ) ),
            'cohort_id'          => (int) ( $post['cohort_id'] ?? 0 ),
        ];
    }

    private static function validate_registration( array $data ): array {
        $errors = [];
        if ( empty( $data['user_login'] ) )        $errors[] = __( 'اسم المستخدم مطلوب.',         'rsyi-sa' );
        if ( empty( $data['user_email'] ) )         $errors[] = __( 'البريد الإلكتروني مطلوب.',    'rsyi-sa' );
        if ( ! is_email( $data['user_email'] ) )    $errors[] = __( 'البريد الإلكتروني غير صالح.', 'rsyi-sa' );
        if ( empty( $data['password'] ) || strlen( $data['password'] ) < 8 ) {
            $errors[] = __( 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.', 'rsyi-sa' );
        }
        if ( empty( $data['arabic_full_name'] ) )   $errors[] = __( 'الاسم العربي الكامل مطلوب.', 'rsyi-sa' );
        if ( empty( $data['english_full_name'] ) )  $errors[] = __( 'الاسم الإنجليزي الكامل مطلوب.', 'rsyi-sa' );
        if ( $data['cohort_id'] <= 0 )              $errors[] = __( 'يرجى اختيار الفوج.',           'rsyi-sa' );
        if ( username_exists( $data['user_login'] ) ) $errors[] = __( 'اسم المستخدم موجود بالفعل.', 'rsyi-sa' );
        if ( email_exists( $data['user_email'] ) )    $errors[] = __( 'البريد الإلكتروني مسجل بالفعل.', 'rsyi-sa' );
        return $errors;
    }

    // ── Bulk Import ───────────────────────────────────────────────────────────

    /**
     * Step 1: Upload & parse the file (Excel/CSV) – return rows as JSON.
     * AJAX endpoint: rsyi_parse_import_file
     */
    public static function ajax_parse_import_file(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );

        if ( ! current_user_can( 'rsyi_create_student' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        if ( empty( $_FILES['import_file'] ) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK ) {
            wp_send_json_error( [ 'message' => __( 'فشل رفع الملف.', 'rsyi-sa' ) ] );
        }

        $file     = $_FILES['import_file'];
        $tmp      = $file['tmp_name'];
        $ext      = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );

        if ( $ext === 'csv' ) {
            $rows = self::parse_csv( $tmp );
        } elseif ( in_array( $ext, [ 'xlsx', 'xls' ], true ) ) {
            $rows = self::parse_xlsx( $tmp );
        } else {
            wp_send_json_error( [ 'message' => __( 'صيغة الملف غير مدعومة. استخدم .xlsx أو .csv', 'rsyi-sa' ) ] );
        }

        // Strip header row (first row contains column names)
        if ( ! empty( $rows ) ) {
            array_shift( $rows );
        }

        // Remove completely empty rows
        $rows = array_values( array_filter( $rows, function ( $r ) {
            return array_filter( $r, fn( $v ) => trim( $v ) !== '' );
        } ) );

        wp_send_json_success( [ 'rows' => $rows, 'total' => count( $rows ) ] );
    }

    /**
     * Step 2: Create WP accounts for a batch of rows.
     * AJAX endpoint: rsyi_import_students_batch
     *
     * Expects: cohort_id, rows (JSON array of arrays)
     */
    public static function ajax_import_students_batch(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );

        if ( ! current_user_can( 'rsyi_create_student' ) ) {
            wp_send_json_error( [ 'message' => __( 'صلاحية غير كافية.', 'rsyi-sa' ) ] );
        }

        $cohort_id  = (int) ( $_POST['cohort_id'] ?? 0 );
        $rows_json  = wp_unslash( $_POST['rows'] ?? '[]' );
        $rows       = json_decode( $rows_json, true );
        $creator_id = get_current_user_id();

        if ( ! is_array( $rows ) || $cohort_id <= 0 ) {
            wp_send_json_error( [ 'message' => __( 'بيانات غير صالحة.', 'rsyi-sa' ) ] );
        }

        $results = [];

        foreach ( $rows as $i => $row ) {
            // Columns: arabic_name, english_name, email, password, national_id, dob, phone
            $arabic_name  = sanitize_text_field( $row[0] ?? '' );
            $english_name = sanitize_text_field( $row[1] ?? '' );
            $email        = sanitize_email( $row[2] ?? '' );
            $password     = trim( $row[3] ?? '' );
            $national_id  = sanitize_text_field( $row[4] ?? '' );
            $dob          = sanitize_text_field( $row[5] ?? '' );
            $phone        = sanitize_text_field( $row[6] ?? '' );

            // Validate required fields
            if ( empty( $arabic_name ) || empty( $english_name ) || ! is_email( $email ) ) {
                $results[] = [
                    'row'     => $i,
                    'name'    => $arabic_name ?: '—',
                    'email'   => $email,
                    'success' => false,
                    'message' => __( 'بيانات ناقصة أو بريد إلكتروني غير صالح.', 'rsyi-sa' ),
                ];
                continue;
            }

            if ( email_exists( $email ) ) {
                $results[] = [
                    'row'     => $i,
                    'name'    => $arabic_name,
                    'email'   => $email,
                    'success' => false,
                    'message' => __( 'البريد الإلكتروني مسجل بالفعل.', 'rsyi-sa' ),
                ];
                continue;
            }

            // Derive login from email prefix, ensure unique
            $base_login = sanitize_user( strtolower( explode( '@', $email )[0] ) );
            $login      = $base_login;
            $suffix     = 1;
            while ( username_exists( $login ) ) {
                $login = $base_login . $suffix++;
            }

            // Auto-generate password if blank
            if ( empty( $password ) ) {
                $password = wp_generate_password( 12, true, false );
            }

            $user_id = wp_insert_user( [
                'user_login'   => $login,
                'user_email'   => $email,
                'user_pass'    => $password,
                'role'         => 'rsyi_student',
                'display_name' => $english_name,
            ] );

            if ( is_wp_error( $user_id ) ) {
                $results[] = [
                    'row'     => $i,
                    'name'    => $arabic_name,
                    'email'   => $email,
                    'success' => false,
                    'message' => $user_id->get_error_message(),
                ];
                continue;
            }

            $data = [
                'user_login'         => $login,
                'user_email'         => $email,
                'password'           => $password,
                'arabic_full_name'   => $arabic_name,
                'english_full_name'  => $english_name,
                'english_first_name' => '',
                'english_last_name'  => '',
                'national_id_number' => $national_id,
                'date_of_birth'      => $dob,
                'phone'              => $phone,
                'cohort_id'          => $cohort_id,
            ];

            // Bulk-imported students are active by default (staff-initiated).
            $profile_id = self::create_profile( $user_id, $data, $creator_id, 'active' );

            Audit_Log::log( 'student_profile', $profile_id, 'create', [
                'method'    => 'excel_import',
                'cohort_id' => $cohort_id,
            ], $creator_id );

            $results[] = [
                'row'        => $i,
                'name'       => $arabic_name,
                'email'      => $email,
                'success'    => true,
                'profile_id' => $profile_id,
                'message'    => __( 'تم إنشاء الحساب بنجاح.', 'rsyi-sa' ),
            ];
        }

        wp_send_json_success( [ 'results' => $results ] );
    }

    /**
     * Download a blank CSV template that users fill in Excel.
     * AJAX endpoint: rsyi_download_import_template (GET)
     */
    public static function ajax_download_import_template(): void {
        check_ajax_referer( 'rsyi_sa_admin', '_nonce' );

        if ( ! current_user_can( 'rsyi_create_student' ) ) {
            wp_die( __( 'صلاحية غير كافية.', 'rsyi-sa' ) );
        }

        $headers = [
            'الاسم العربي الكامل',
            'الاسم الإنجليزي الكامل',
            'البريد الإلكتروني',
            'كلمة المرور',
            'رقم الهوية القومية',
            'تاريخ الميلاد (YYYY-MM-DD)',
            'رقم الهاتف',
        ];

        $sample = [
            'محمد أحمد علي',
            'Mohamed Ahmed Ali',
            'mohamed@example.com',
            'Pass@1234',
            '12345678901234',
            '2000-05-15',
            '01012345678',
        ];

        header( 'Content-Type: text/csv; charset=UTF-8' );
        header( 'Content-Disposition: attachment; filename="rsyi_students_template.csv"' );
        header( 'Cache-Control: no-cache, no-store, must-revalidate' );

        // UTF-8 BOM so Excel opens it correctly with Arabic
        echo "\xEF\xBB\xBF";

        $out = fopen( 'php://output', 'w' );
        fputcsv( $out, $headers );
        fputcsv( $out, $sample );
        fclose( $out );
        exit;
    }

    // ── File parsers ──────────────────────────────────────────────────────────

    /**
     * Parse a CSV file and return array of rows (each row = array of values).
     */
    private static function parse_csv( string $file_path ): array {
        $rows = [];
        $handle = fopen( $file_path, 'r' );
        if ( $handle === false ) {
            return $rows;
        }

        // Consume UTF-8 BOM if present
        $bom = fread( $handle, 3 );
        if ( $bom !== "\xEF\xBB\xBF" ) {
            rewind( $handle );
        }

        while ( ( $data = fgetcsv( $handle ) ) !== false ) {
            $rows[] = array_map( 'trim', $data );
        }
        fclose( $handle );
        return $rows;
    }

    /**
     * Parse an .xlsx file using ZipArchive + SimpleXML (no external library needed).
     * Returns array of rows.
     */
    private static function parse_xlsx( string $file_path ): array {
        if ( ! class_exists( 'ZipArchive' ) ) {
            return [];
        }

        $zip = new \ZipArchive();
        if ( $zip->open( $file_path ) !== true ) {
            return [];
        }

        // Shared strings table
        $shared_strings = [];
        $ss_xml = $zip->getFromName( 'xl/sharedStrings.xml' );
        if ( $ss_xml ) {
            $ss = simplexml_load_string( $ss_xml );
            if ( $ss ) {
                foreach ( $ss->si as $si ) {
                    // A shared string may have plain <t> or rich-text <r><t> children
                    $text = '';
                    if ( isset( $si->t ) ) {
                        $text = (string) $si->t;
                    } else {
                        foreach ( $si->r as $r ) {
                            $text .= (string) $r->t;
                        }
                    }
                    $shared_strings[] = $text;
                }
            }
        }

        // First worksheet
        $sheet_xml = $zip->getFromName( 'xl/worksheets/sheet1.xml' );
        $zip->close();

        if ( ! $sheet_xml ) {
            return [];
        }

        $sheet = simplexml_load_string( $sheet_xml );
        if ( ! $sheet ) {
            return [];
        }

        $rows = [];
        foreach ( $sheet->sheetData->row as $row ) {
            $row_data = [];
            foreach ( $row->c as $cell ) {
                $cell_type  = (string) $cell['t'];
                $cell_value = isset( $cell->v ) ? (string) $cell->v : '';

                if ( $cell_type === 's' ) {
                    // Shared string index
                    $cell_value = $shared_strings[ (int) $cell_value ] ?? '';
                } elseif ( $cell_type === 'inlineStr' ) {
                    $cell_value = isset( $cell->is->t ) ? (string) $cell->is->t : '';
                }

                $row_data[] = trim( $cell_value );
            }
            $rows[] = $row_data;
        }

        return $rows;
    }
}
