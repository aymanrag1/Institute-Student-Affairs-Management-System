<?php
/**
 * Student Portal Shortcodes
 *
 * Shortcode map:
 *   [rsyi_portal_dashboard]   – student home (status, warnings, pending ack)
 *   [rsyi_portal_documents]   – upload mandatory documents
 *   [rsyi_portal_requests]    – submit & view exit/overnight permits
 *   [rsyi_portal_behavior]    – view points, acknowledge warnings
 *   [rsyi_portal_register]    – self-registration form
 *   [rsyi_portal_evaluation]  – submit peer evaluations for cohort supervisors
 *
 * @package RSYI_StudentAffairs
 */

namespace RSYI_SA\Portal;

defined( 'ABSPATH' ) || exit;

class Shortcodes {

    public static function init(): void {
        $codes = [
            'rsyi_portal_dashboard'          => 'render_dashboard',
            'rsyi_portal_documents'          => 'render_documents',
            'rsyi_portal_requests'           => 'render_requests',
            'rsyi_portal_behavior'           => 'render_behavior',
            'rsyi_portal_register'           => 'render_register',
            'rsyi_portal_evaluation'         => 'render_evaluation',
            'rsyi_portal_materials'          => 'render_materials',
            'rsyi_portal_grades'             => 'render_grades',
            'rsyi_portal_attendance_record'  => 'render_attendance_record',
            'rsyi_portal_exams'              => 'render_exams',
            'rsyi_portal_library'            => 'render_library',
            'rsyi_portal_boss_man'           => 'render_boss_man',
        ];
        foreach ( $codes as $tag => $method ) {
            add_shortcode( $tag, [ __CLASS__, $method ] );
        }
    }

    // ── Auth gate ─────────────────────────────────────────────────────────────

    private static function require_login(): bool {
        if ( is_user_logged_in() ) return true;
        wp_redirect( wp_login_url( get_permalink() ) );
        exit;
    }

    private static function require_student(): ?object {
        self::require_login();
        $profile = \RSYI_SA\Modules\Accounts::get_profile_by_user_id( get_current_user_id() );
        return $profile ?: null;
    }

    private static function render_template( string $name, array $vars = [] ): string {
        $file = RSYI_SA_PLUGIN_DIR . 'templates/portal/' . $name . '.php';
        if ( ! file_exists( $file ) ) return '';
        ob_start();
        extract( $vars, EXTR_SKIP ); // phpcs:ignore
        include $file;
        return ob_get_clean();
    }

    // ── Shortcode handlers ────────────────────────────────────────────────────

    public static function render_dashboard( $atts ): string {
        $profile = self::require_student();
        if ( ! $profile ) {
            return '<p>' . esc_html__( 'لم يتم العثور على ملفك الشخصي. يرجى التواصل مع الإدارة.', 'rsyi-sa' ) . '</p>';
        }

        $total_pts   = \RSYI_SA\Modules\Behavior::get_total_points( (int) $profile->id );
        $warnings    = \RSYI_SA\Modules\Behavior::get_pending_warnings_for_student( (int) $profile->id );
        $cohort      = \RSYI_SA\Modules\Cohorts::get_cohort( (int) $profile->cohort_id );

        return self::render_template( 'dashboard', compact( 'profile', 'total_pts', 'warnings', 'cohort' ) );
    }

    public static function render_documents( $atts ): string {
        $profile = self::require_student();
        if ( ! $profile ) return '';

        $doc_map = \RSYI_SA\Modules\Documents::get_student_documents_map( (int) $profile->id );
        $labels  = \RSYI_SA\Modules\Accounts::DOC_TYPE_LABELS;

        return self::render_template( 'documents', compact( 'profile', 'doc_map', 'labels' ) );
    }

    public static function render_requests( $atts ): string {
        $profile = self::require_student();
        if ( ! $profile ) return '';

        if ( $profile->status !== 'active' ) {
            return '<div class="rsyi-notice rsyi-notice-warning">'
                . esc_html__( 'يجب تفعيل حسابك أولاً (رفع جميع الوثائق المطلوبة).', 'rsyi-sa' )
                . '</div>';
        }

        $exit_permits      = \RSYI_SA\Modules\Requests::get_student_exit_permits( (int) $profile->id );
        $overnight_permits = \RSYI_SA\Modules\Requests::get_student_overnight_permits( (int) $profile->id );

        return self::render_template( 'requests', compact( 'profile', 'exit_permits', 'overnight_permits' ) );
    }

    public static function render_behavior( $atts ): string {
        $profile = self::require_student();
        if ( ! $profile ) return '';

        global $wpdb;
        $violations  = $wpdb->get_results( $wpdb->prepare(
            "SELECT v.*, vt.name_ar AS type_ar
             FROM {$wpdb->prefix}rsyi_violations v
             JOIN {$wpdb->prefix}rsyi_violation_types vt ON vt.id = v.violation_type_id
             WHERE v.student_id = %d AND v.status = 'active'
             ORDER BY v.incident_date DESC",
            $profile->id
        ) );
        $total_pts  = \RSYI_SA\Modules\Behavior::get_total_points( (int) $profile->id );
        $warnings   = \RSYI_SA\Modules\Behavior::get_student_warnings( (int) $profile->id );

        return self::render_template( 'behavior', compact( 'profile', 'violations', 'total_pts', 'warnings' ) );
    }

    public static function render_register( $atts ): string {
        if ( is_user_logged_in() ) {
            return '<p>' . esc_html__( 'You are already logged in.', 'rsyi-sa' ) . '</p>';
        }
        $cohorts = \RSYI_SA\Modules\Cohorts::get_all_cohorts( true );
        return self::render_template( 'register', compact( 'cohorts' ) );
    }

    public static function render_evaluation( $atts ): string {
        $profile = self::require_student();
        if ( ! $profile ) {
            return '<p>' . esc_html__( 'Profile not found. Please contact administration.', 'rsyi-sa' ) . '</p>';
        }

        if ( $profile->status !== 'active' ) {
            return '<div class="rsyi-notice rsyi-notice-warning">'
                . esc_html__( 'Your account must be active to submit evaluations.', 'rsyi-sa' )
                . '</div>';
        }

        $user_id  = (int) $profile->user_id;
        $cohort_id = (int) $profile->cohort_id;

        // Active evaluation periods for this student's cohort
        global $wpdb;
        $p_table  = $wpdb->prefix . 'rsyi_evaluation_periods';
        $c_table  = $wpdb->prefix . 'rsyi_cohorts';
        $periods  = $wpdb->get_results( $wpdb->prepare(
            "SELECT ep.*, c.name AS cohort_name
             FROM {$p_table} ep
             LEFT JOIN {$c_table} c ON c.id = ep.cohort_id
             WHERE ep.cohort_id = %d AND ep.is_active = 1
             ORDER BY ep.created_at DESC",
            $cohort_id
        ) );

        // All active students in the cohort (potential evaluatees)
        $sp_table  = $wpdb->prefix . 'rsyi_student_profiles';
        $evaluatees = $wpdb->get_results( $wpdb->prepare(
            "SELECT sp.user_id, sp.english_full_name
             FROM {$sp_table} sp
             WHERE sp.cohort_id = %d AND sp.status = 'active' AND sp.user_id != %d
             ORDER BY sp.english_full_name ASC",
            $cohort_id,
            $user_id
        ) );

        // Already submitted peer evaluations (per period)
        $pe_table      = $wpdb->prefix . 'rsyi_peer_evaluations';
        $submitted_raw = $wpdb->get_results( $wpdb->prepare(
            "SELECT period_id, evaluatee_id, total FROM {$pe_table} WHERE evaluator_id = %d",
            $user_id
        ) );
        $submitted = [];
        foreach ( $submitted_raw as $s ) {
            $submitted[ (int) $s->period_id ][ (int) $s->evaluatee_id ] = (int) $s->total;
        }

        $peer_criteria = \RSYI_SA\Modules\Evaluations::get_peer_criteria();

        return self::render_template( 'evaluation', compact(
            'profile', 'periods', 'evaluatees', 'submitted', 'peer_criteria'
        ) );
    }

    // ── LMS Shortcodes ────────────────────────────────────────────────────────

    public static function render_materials( $atts ): string {
        $profile = self::require_student();
        if ( ! $profile ) return '';

        if ( ! current_user_can( 'rsyi_view_own_materials' ) ) {
            return '<p>' . esc_html__( 'ليس لديك صلاحية عرض المواد الدراسية.', 'rsyi-sa' ) . '</p>';
        }

        global $wpdb;
        $materials = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rsyi_study_materials
             WHERE is_active = 1 AND (cohort_id = %d OR cohort_id IS NULL)
             ORDER BY subject ASC, created_at DESC",
            (int) $profile->cohort_id
        ) );

        return self::render_template( 'materials', compact( 'profile', 'materials' ) );
    }

    public static function render_grades( $atts ): string {
        $profile = self::require_student();
        if ( ! $profile ) return '';

        if ( ! current_user_can( 'rsyi_view_own_exam_results' ) ) {
            return '<p>' . esc_html__( 'ليس لديك صلاحية عرض الدرجات.', 'rsyi-sa' ) . '</p>';
        }

        global $wpdb;
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT r.*, e.title AS exam_title, e.subject, e.exam_date, e.max_score,
                    e.passing_score, e.exam_type
             FROM {$wpdb->prefix}rsyi_exam_results r
             JOIN {$wpdb->prefix}rsyi_exams e ON e.id = r.exam_id
             WHERE r.student_id = %d
             ORDER BY e.exam_date DESC, e.created_at DESC",
            (int) $profile->id
        ) );

        return self::render_template( 'grades', compact( 'profile', 'results' ) );
    }

    public static function render_attendance_record( $atts ): string {
        $profile = self::require_student();
        if ( ! $profile ) return '';

        if ( ! current_user_can( 'rsyi_view_own_attendance' ) ) {
            return '<p>' . esc_html__( 'ليس لديك صلاحية عرض سجل الحضور.', 'rsyi-sa' ) . '</p>';
        }

        global $wpdb;
        $records = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rsyi_attendance
             WHERE student_id = %d
             ORDER BY session_date DESC, created_at DESC",
            (int) $profile->id
        ) );

        return self::render_template( 'attendance-record', compact( 'profile', 'records' ) );
    }

    // ── Exam portal ───────────────────────────────────────────────────────────

    public static function render_exams( $atts ): string {
        $profile = self::require_student();
        if ( ! $profile ) return '';

        if ( ! current_user_can( 'rsyi_take_exam' ) ) {
            return '<p>' . esc_html__( 'ليس لديك صلاحية الوصول للامتحانات.', 'rsyi-sa' ) . '</p>';
        }

        // If exam_id is provided, render the exam-taking page
        $exam_id = absint( $_GET['exam_id'] ?? 0 );
        if ( $exam_id ) {
            return self::render_exam_take( $profile, $exam_id );
        }

        global $wpdb;
        $now = current_time( 'mysql' );

        // Get exams for this student's cohort OR exams with no cohort (global).
        // Also handles students with no cohort assigned (cohort_id = 0).
        $student_cohort = (int) ( $profile->cohort_id ?? 0 );
        if ( $student_cohort ) {
            $exams = $wpdb->get_results( $wpdb->prepare(
                "SELECT e.*
                 FROM {$wpdb->prefix}rsyi_exams e
                 WHERE e.is_active = 1 AND e.status = 'published'
                   AND ( e.cohort_id = %d OR e.cohort_id IS NULL )
                 ORDER BY e.created_at DESC",
                $student_cohort
            ) );
        } else {
            // Student has no cohort — show only global exams (no cohort restriction)
            $exams = $wpdb->get_results(
                "SELECT e.*
                 FROM {$wpdb->prefix}rsyi_exams e
                 WHERE e.is_active = 1 AND e.status = 'published'
                   AND ( e.cohort_id IS NULL OR e.cohort_id = 0 )
                 ORDER BY e.created_at DESC"
            );
        }

        // Get this student's results
        $results_raw = $wpdb->get_results( $wpdb->prepare(
            "SELECT exam_id, score, grade, is_passing, submitted_at
             FROM {$wpdb->prefix}rsyi_exam_results
             WHERE student_id = %d",
            (int) $profile->id
        ) );
        $results_map = [];
        foreach ( $results_raw as $r ) {
            $results_map[ (int) $r->exam_id ] = $r;
        }

        return self::render_template( 'exam-list', compact( 'profile', 'exams', 'results_map', 'now' ) );
    }

    private static function render_exam_take( object $profile, int $exam_id ): string {
        global $wpdb;
        $now = current_time( 'mysql' );

        $exam = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rsyi_exams WHERE id = %d AND is_active = 1 AND status = 'published'",
            $exam_id
        ) );

        if ( ! $exam ) {
            return '<div class="rsyi-notice rsyi-notice-error">' . esc_html__( 'الامتحان غير موجود أو غير متاح.', 'rsyi-sa' ) . '</div>';
        }

        // Check cohort match
        if ( $exam->cohort_id && (int) $exam->cohort_id !== (int) $profile->cohort_id ) {
            return '<div class="rsyi-notice rsyi-notice-error">' . esc_html__( 'هذا الامتحان ليس لفوجك.', 'rsyi-sa' ) . '</div>';
        }

        // Check already submitted
        $submitted = $wpdb->get_row( $wpdb->prepare(
            "SELECT score, grade, is_passing FROM {$wpdb->prefix}rsyi_exam_results WHERE exam_id = %d AND student_id = %d LIMIT 1",
            $exam_id, (int) $profile->id
        ) );
        if ( $submitted ) {
            return self::render_template( 'exam-submitted', compact( 'exam', 'submitted', 'profile' ) );
        }

        // Check timing
        if ( $exam->starts_at && $now < $exam->starts_at ) {
            return '<div class="rsyi-notice rsyi-notice-warning" dir="rtl">' .
                esc_html__( 'الامتحان لم يبدأ بعد. يبدأ في: ', 'rsyi-sa' ) .
                esc_html( date_i18n( 'j M Y H:i', strtotime( $exam->starts_at ) ) ) . '</div>';
        }
        if ( $exam->ends_at && $now > $exam->ends_at ) {
            return '<div class="rsyi-notice rsyi-notice-error" dir="rtl">' .
                esc_html__( 'انتهى وقت الامتحان.', 'rsyi-sa' ) . '</div>';
        }

        // Get questions (without correct answers)
        $questions = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, question_number, question_text, question_type, options, marks, image_id, explanation
             FROM {$wpdb->prefix}rsyi_exam_questions
             WHERE exam_id = %d ORDER BY question_number ASC",
            $exam_id
        ) );

        foreach ( $questions as $q ) {
            $q->image_url = $q->image_id ? wp_get_attachment_image_url( (int) $q->image_id, 'medium' ) : null;
            if ( $q->options && in_array( $q->question_type, [ 'ordering' ], true ) ) {
                $opts = json_decode( $q->options, true );
                if ( is_array( $opts ) ) {
                    $shuffled = array_map( fn( $o ) => [ 'text' => $o['text'] ], $opts );
                    shuffle( $shuffled );
                    $q->options_display = $shuffled;
                }
            }
            if ( $q->options && $q->question_type === 'matching' ) {
                $opts = json_decode( $q->options, true );
                if ( is_array( $opts ) ) {
                    $matches = array_column( $opts, 'match' );
                    shuffle( $matches );
                    $q->shuffled_matches = $matches;
                    $q->premises         = array_column( $opts, 'premise' );
                }
            }
        }

        return self::render_template( 'exam-take', compact( 'exam', 'questions', 'profile', 'now' ) );
    }

    public static function render_library( $atts ): string {
        $profile = self::require_student();
        if ( ! $profile ) {
            return '<div class="rsyi-notice rsyi-notice-error">يجب تسجيل الدخول لعرض المكتبة / Please log in to view the library.</div>';
        }
        if ( ! current_user_can( 'rsyi_view_library' ) ) {
            return '<div class="rsyi-notice rsyi-notice-error">غير مصرح / Unauthorized</div>';
        }
        $books     = \RSYI_SA\Modules\Library::get_available_books();
        $my_issues = \RSYI_SA\Modules\Library::get_student_issues( $profile->id );
        return self::render_template( 'library', compact( 'books', 'my_issues' ) );
    }

    public static function render_boss_man( $atts ): string {
        self::require_login();
        global $wpdb;
        $profile = \RSYI_SA\Modules\Accounts::get_profile_by_user_id( get_current_user_id() );
        if ( ! $profile || ! $profile->is_boss_man ) {
            return '<div style="background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:20px;text-align:center;direction:rtl;">
                <strong>⛔ ليس لديك صلاحية الوصول لهذه الصفحة</strong><br>
                <small>هذه الصفحة مخصصة لحكمدار الدفعة فقط</small>
            </div>';
        }

        $cohort_id = (int) $profile->cohort_id;
        $cohort    = \RSYI_SA\Modules\Cohorts::get_cohort( $cohort_id );
        $students  = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, arabic_full_name, english_full_name FROM {$wpdb->prefix}rsyi_student_profiles
             WHERE cohort_id = %d AND status = 'active' ORDER BY arabic_full_name ASC",
            $cohort_id
        ) );
        $courses = $wpdb->get_results(
            "SELECT id, name_ar, name_en FROM {$wpdb->prefix}rsyi_courses WHERE is_active = 1 ORDER BY name_ar ASC"
        );

        return self::render_template( 'boss-man', compact( 'profile', 'cohort', 'students', 'courses' ) );
    }
}
