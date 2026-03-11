<?php
/**
 * Admin Template: Exams Management (v1.3.0)
 * 5 tabs: list | add | edit | results | stats
 * Requires capability: rsyi_manage_exams
 *
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

if ( ! current_user_can( 'rsyi_manage_exams' ) ) {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'ليس لديك صلاحية الوصول لهذه الصفحة.', 'rsyi-sa' ) . '</p></div>';
    return;
}

global $wpdb;

$active_tab = sanitize_key( $_GET['tab'] ?? 'list' );
$cohorts    = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}rsyi_cohorts WHERE is_active = 1 ORDER BY name ASC" );
$exams      = $wpdb->get_results(
    "SELECT e.*, u.display_name AS creator_name, c.name AS cohort_name
     FROM {$wpdb->prefix}rsyi_exams e
     LEFT JOIN {$wpdb->users} u ON u.ID = e.created_by
     LEFT JOIN {$wpdb->prefix}rsyi_cohorts c ON c.id = e.cohort_id
     ORDER BY e.exam_date DESC, e.created_at DESC LIMIT 200"
);

// Selected exam for results/edit/stats tabs
$selected_exam_id = absint( $_GET['exam_id'] ?? 0 );
$selected_exam    = null;
$exam_students    = [];
$exam_results_map = [];

if ( $selected_exam_id ) {
    $selected_exam = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}rsyi_exams WHERE id = %d",
        $selected_exam_id
    ) );
    if ( $selected_exam && $selected_exam->cohort_id ) {
        $exam_students = $wpdb->get_results( $wpdb->prepare(
            "SELECT sp.id AS profile_id, sp.arabic_full_name, sp.english_full_name
             FROM {$wpdb->prefix}rsyi_student_profiles sp
             WHERE sp.cohort_id = %d AND sp.status = 'active'
             ORDER BY sp.arabic_full_name ASC",
            (int) $selected_exam->cohort_id
        ) );
        $existing_results = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rsyi_exam_results WHERE exam_id = %d",
            $selected_exam_id
        ) );
        foreach ( $existing_results as $r ) {
            $exam_results_map[ (int) $r->student_id ] = $r;
        }
    }
}

$type_labels   = [ 'written' => 'نظري', 'practical' => 'عملي', 'project' => 'مشروع', 'oral' => 'شفهي' ];
$status_labels = [ 'published' => 'منشور', 'draft' => 'مسودة', 'closed' => 'مغلق' ];
$status_colors = [ 'published' => '#27ae60', 'draft' => '#999', 'closed' => '#e74c3c' ];

// Auto-calculate grade letter from percent
function rsyi_auto_grade( int $score, int $max ): string {
    $pct = $max > 0 ? $score / $max * 100 : 0;
    if ( $pct >= 90 ) return 'A+';
    if ( $pct >= 80 ) return 'A';
    if ( $pct >= 70 ) return 'B';
    if ( $pct >= 60 ) return 'C';
    if ( $pct >= 50 ) return 'D';
    return 'F';
}
?>
<h1 class="wp-heading-inline"><?php esc_html_e( 'الامتحانات', 'rsyi-sa' ); ?></h1>
<hr class="wp-header-end">

<!-- Tabs -->
<nav class="nav-tab-wrapper" style="margin-bottom:20px;" dir="rtl">
    <a href="<?php echo esc_url( add_query_arg( [ 'page' => 'rsyi-exams', 'tab' => 'list' ], admin_url( 'admin.php' ) ) ); ?>"
       class="nav-tab <?php echo $active_tab === 'list' ? 'nav-tab-active' : ''; ?>">
        <?php esc_html_e( 'قائمة الامتحانات', 'rsyi-sa' ); ?>
    </a>
    <?php if ( current_user_can( 'rsyi_manage_exams' ) ) : ?>
    <a href="<?php echo esc_url( add_query_arg( [ 'page' => 'rsyi-exams', 'tab' => 'add' ], admin_url( 'admin.php' ) ) ); ?>"
       class="nav-tab <?php echo $active_tab === 'add' ? 'nav-tab-active' : ''; ?>">
        <?php esc_html_e( 'إنشاء امتحان', 'rsyi-sa' ); ?>
    </a>
    <?php endif; ?>
    <?php if ( $selected_exam ) : ?>
    <?php if ( current_user_can( 'rsyi_edit_exam' ) ) : ?>
    <a href="<?php echo esc_url( add_query_arg( [ 'page' => 'rsyi-exams', 'tab' => 'edit', 'exam_id' => $selected_exam_id ], admin_url( 'admin.php' ) ) ); ?>"
       class="nav-tab <?php echo $active_tab === 'edit' ? 'nav-tab-active' : ''; ?>">
        <?php esc_html_e( 'تعديل الامتحان', 'rsyi-sa' ); ?>
    </a>
    <?php endif; ?>
    <a href="<?php echo esc_url( add_query_arg( [ 'page' => 'rsyi-exams', 'tab' => 'results', 'exam_id' => $selected_exam_id ], admin_url( 'admin.php' ) ) ); ?>"
       class="nav-tab <?php echo $active_tab === 'results' ? 'nav-tab-active' : ''; ?>">
        <?php esc_html_e( 'إدخال النتائج', 'rsyi-sa' ); ?>
    </a>
    <?php if ( current_user_can( 'rsyi_view_exam_stats' ) ) : ?>
    <a href="<?php echo esc_url( add_query_arg( [ 'page' => 'rsyi-exams', 'tab' => 'stats', 'exam_id' => $selected_exam_id ], admin_url( 'admin.php' ) ) ); ?>"
       class="nav-tab <?php echo $active_tab === 'stats' ? 'nav-tab-active' : ''; ?>">
        <?php esc_html_e( 'الإحصائيات', 'rsyi-sa' ); ?>
    </a>
    <?php endif; ?>
    <?php endif; ?>
</nav>

<!-- ── List tab ── -->
<?php if ( $active_tab === 'list' ) : ?>
<?php if ( empty( $exams ) ) : ?>
<p dir="rtl"><?php esc_html_e( 'لا توجد امتحانات بعد.', 'rsyi-sa' ); ?></p>
<?php else : ?>
<table class="wp-list-table widefat fixed striped" dir="rtl">
    <thead>
        <tr>
            <th><?php esc_html_e( 'الامتحان', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'المادة', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'الفوج', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'النوع', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'التاريخ', 'rsyi-sa' ); ?></th>
            <th style="text-align:center;"><?php esc_html_e( 'الدرجة القصوى', 'rsyi-sa' ); ?></th>
            <th style="text-align:center;"><?php esc_html_e( 'الحالة', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'إجراءات', 'rsyi-sa' ); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ( $exams as $e ) :
        $e_status = $e->status ?? 'published';
    ?>
    <tr id="exam-row-<?php echo esc_attr( $e->id ); ?>">
        <td><strong><?php echo esc_html( $e->title ); ?></strong></td>
        <td><?php echo $e->subject ? esc_html( $e->subject ) : '—'; ?></td>
        <td><?php echo $e->cohort_name ? esc_html( $e->cohort_name ) : '—'; ?></td>
        <td><?php echo esc_html( $type_labels[ $e->exam_type ?? 'written' ] ?? 'نظري' ); ?></td>
        <td><?php echo $e->exam_date ? esc_html( date_i18n( 'j M Y', strtotime( $e->exam_date ) ) ) : '—'; ?></td>
        <td style="text-align:center;"><?php echo esc_html( $e->max_score ); ?></td>
        <td style="text-align:center;">
            <span style="color:<?php echo esc_attr( $status_colors[ $e_status ] ?? '#999' ); ?>; font-weight:600; font-size:12px;">
                <?php echo esc_html( $status_labels[ $e_status ] ?? $e_status ); ?>
            </span>
        </td>
        <td style="white-space:nowrap;">
            <a href="<?php echo esc_url( add_query_arg( [ 'page' => 'rsyi-exams', 'tab' => 'results', 'exam_id' => $e->id ], admin_url( 'admin.php' ) ) ); ?>"
               class="button button-small"><?php esc_html_e( 'النتائج', 'rsyi-sa' ); ?></a>
            <?php if ( current_user_can( 'rsyi_edit_exam' ) ) : ?>
            <a href="<?php echo esc_url( add_query_arg( [ 'page' => 'rsyi-exams', 'tab' => 'edit', 'exam_id' => $e->id ], admin_url( 'admin.php' ) ) ); ?>"
               class="button button-small"><?php esc_html_e( 'تعديل', 'rsyi-sa' ); ?></a>
            <?php endif; ?>
            <?php if ( current_user_can( 'rsyi_view_exam_stats' ) ) : ?>
            <a href="<?php echo esc_url( add_query_arg( [ 'page' => 'rsyi-exams', 'tab' => 'stats', 'exam_id' => $e->id ], admin_url( 'admin.php' ) ) ); ?>"
               class="button button-small"><?php esc_html_e( 'إحصائيات', 'rsyi-sa' ); ?></a>
            <?php endif; ?>
            <?php if ( current_user_can( 'rsyi_delete_exam' ) ) : ?>
            <button class="button button-small rsyi-delete-exam" data-exam-id="<?php echo esc_attr( $e->id ); ?>"
                    style="color:#a00; border-color:#a00;">
                <?php esc_html_e( 'حذف', 'rsyi-sa' ); ?>
            </button>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<!-- ── Add exam tab ── -->
<?php elseif ( $active_tab === 'add' ) : ?>
<div style="max-width:620px; background:#fff; border:1px solid #ccd0d4; border-radius:4px; padding:24px;" dir="rtl">
    <h2 style="margin-top:0;"><?php esc_html_e( 'إنشاء امتحان جديد', 'rsyi-sa' ); ?></h2>
    <form id="rsyi-create-exam-form">
        <?php wp_nonce_field( 'rsyi_sa_admin', '_nonce' ); ?>
        <input type="hidden" name="action" value="rsyi_create_exam">

        <table class="form-table">
            <tr>
                <th><?php esc_html_e( 'عنوان الامتحان', 'rsyi-sa' ); ?></th>
                <td><input type="text" name="title" class="regular-text" required></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'المادة', 'rsyi-sa' ); ?></th>
                <td><input type="text" name="subject" class="regular-text"></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'نوع الامتحان', 'rsyi-sa' ); ?></th>
                <td>
                    <select name="exam_type">
                        <?php foreach ( $type_labels as $val => $lbl ) : ?>
                        <option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $lbl ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'الفوج', 'rsyi-sa' ); ?></th>
                <td>
                    <select name="cohort_id" style="min-width:200px;">
                        <option value=""><?php esc_html_e( '— اختر الفوج —', 'rsyi-sa' ); ?></option>
                        <?php foreach ( $cohorts as $c ) : ?>
                        <option value="<?php echo esc_attr( $c->id ); ?>"><?php echo esc_html( $c->name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'تاريخ الامتحان', 'rsyi-sa' ); ?></th>
                <td><input type="date" name="exam_date"></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'المدة (دقيقة)', 'rsyi-sa' ); ?></th>
                <td><input type="number" name="duration_min" min="1" style="width:90px;"></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'الدرجة القصوى', 'rsyi-sa' ); ?></th>
                <td><input type="number" name="max_score" value="100" min="1" style="width:90px;" required></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'درجة النجاح', 'rsyi-sa' ); ?></th>
                <td>
                    <input type="number" name="passing_score" min="0" style="width:90px;" placeholder="<?php esc_attr_e( 'الافتراضي: 50%', 'rsyi-sa' ); ?>">
                    <p class="description"><?php esc_html_e( 'اتركه فارغاً لاستخدام 50% من الدرجة القصوى.', 'rsyi-sa' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'الحالة', 'rsyi-sa' ); ?></th>
                <td>
                    <select name="status">
                        <option value="published"><?php esc_html_e( 'منشور', 'rsyi-sa' ); ?></option>
                        <option value="draft"><?php esc_html_e( 'مسودة', 'rsyi-sa' ); ?></option>
                        <option value="closed"><?php esc_html_e( 'مغلق', 'rsyi-sa' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'وصف', 'rsyi-sa' ); ?></th>
                <td><textarea name="description" rows="3" style="width:100%;"></textarea></td>
            </tr>
        </table>

        <p>
            <button type="submit" class="button button-primary"><?php esc_html_e( 'إنشاء الامتحان', 'rsyi-sa' ); ?></button>
            <span id="rsyi-exam-create-msg" style="margin-right:12px;"></span>
        </p>
    </form>
</div>

<!-- ── Edit exam tab ── -->
<?php elseif ( $active_tab === 'edit' && $selected_exam && current_user_can( 'rsyi_edit_exam' ) ) : ?>
<div style="max-width:620px; background:#fff; border:1px solid #ccd0d4; border-radius:4px; padding:24px;" dir="rtl">
    <h2 style="margin-top:0;"><?php echo esc_html( $selected_exam->title ); ?> — <?php esc_html_e( 'تعديل', 'rsyi-sa' ); ?></h2>
    <form id="rsyi-update-exam-form">
        <?php wp_nonce_field( 'rsyi_sa_admin', '_nonce' ); ?>
        <input type="hidden" name="action" value="rsyi_update_exam">
        <input type="hidden" name="exam_id" value="<?php echo esc_attr( $selected_exam->id ); ?>">

        <table class="form-table">
            <tr>
                <th><?php esc_html_e( 'عنوان الامتحان', 'rsyi-sa' ); ?></th>
                <td><input type="text" name="title" class="regular-text" value="<?php echo esc_attr( $selected_exam->title ); ?>" required></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'المادة', 'rsyi-sa' ); ?></th>
                <td><input type="text" name="subject" class="regular-text" value="<?php echo esc_attr( $selected_exam->subject ); ?>"></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'نوع الامتحان', 'rsyi-sa' ); ?></th>
                <td>
                    <select name="exam_type">
                        <?php foreach ( $type_labels as $val => $lbl ) : ?>
                        <option value="<?php echo esc_attr( $val ); ?>" <?php selected( ( $selected_exam->exam_type ?? 'written' ), $val ); ?>>
                            <?php echo esc_html( $lbl ); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'الفوج', 'rsyi-sa' ); ?></th>
                <td>
                    <select name="cohort_id" style="min-width:200px;">
                        <option value=""><?php esc_html_e( '— اختر الفوج —', 'rsyi-sa' ); ?></option>
                        <?php foreach ( $cohorts as $c ) : ?>
                        <option value="<?php echo esc_attr( $c->id ); ?>" <?php selected( $selected_exam->cohort_id, $c->id ); ?>>
                            <?php echo esc_html( $c->name ); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'تاريخ الامتحان', 'rsyi-sa' ); ?></th>
                <td><input type="date" name="exam_date" value="<?php echo esc_attr( $selected_exam->exam_date ); ?>"></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'المدة (دقيقة)', 'rsyi-sa' ); ?></th>
                <td><input type="number" name="duration_min" min="1" style="width:90px;" value="<?php echo esc_attr( $selected_exam->duration_min ); ?>"></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'الدرجة القصوى', 'rsyi-sa' ); ?></th>
                <td><input type="number" name="max_score" min="1" style="width:90px;" value="<?php echo esc_attr( $selected_exam->max_score ); ?>" required></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'درجة النجاح', 'rsyi-sa' ); ?></th>
                <td>
                    <input type="number" name="passing_score" min="0" style="width:90px;"
                           value="<?php echo esc_attr( $selected_exam->passing_score ?? '' ); ?>"
                           placeholder="<?php esc_attr_e( 'الافتراضي: 50%', 'rsyi-sa' ); ?>">
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'الحالة', 'rsyi-sa' ); ?></th>
                <td>
                    <select name="status">
                        <?php foreach ( $status_labels as $val => $lbl ) : ?>
                        <option value="<?php echo esc_attr( $val ); ?>" <?php selected( ( $selected_exam->status ?? 'published' ), $val ); ?>>
                            <?php echo esc_html( $lbl ); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'وصف', 'rsyi-sa' ); ?></th>
                <td><textarea name="description" rows="3" style="width:100%;"><?php echo esc_textarea( $selected_exam->description ); ?></textarea></td>
            </tr>
        </table>

        <p>
            <button type="submit" class="button button-primary"><?php esc_html_e( 'حفظ التعديلات', 'rsyi-sa' ); ?></button>
            <span id="rsyi-exam-update-msg" style="margin-right:12px;"></span>
        </p>
    </form>
</div>

<!-- ── Results tab ── -->
<?php elseif ( $active_tab === 'results' && $selected_exam ) : ?>
<h2 dir="rtl"><?php echo esc_html( $selected_exam->title ); ?> — <?php esc_html_e( 'إدخال النتائج', 'rsyi-sa' ); ?></h2>

<div style="margin-bottom:14px; display:flex; align-items:center; gap:10px;">
    <button type="button" class="button" id="rsyi-auto-grades-btn">
        ⚡ <?php esc_html_e( 'احسب التقديرات تلقائياً', 'rsyi-sa' ); ?>
    </button>
    <?php if ( current_user_can( 'rsyi_export_exam_results' ) ) : ?>
    <button type="button" class="button" id="rsyi-export-btn" data-exam-id="<?php echo esc_attr( $selected_exam_id ); ?>">
        ⬇ <?php esc_html_e( 'تصدير CSV', 'rsyi-sa' ); ?>
    </button>
    <?php endif; ?>
</div>

<?php if ( empty( $exam_students ) ) : ?>
<div class="notice notice-warning" dir="rtl"><p><?php esc_html_e( 'لا يوجد طلاب نشطون في فوج هذا الامتحان.', 'rsyi-sa' ); ?></p></div>
<?php else : ?>
<form id="rsyi-results-form" dir="rtl">
    <?php wp_nonce_field( 'rsyi_sa_admin', '_nonce' ); ?>
    <input type="hidden" name="action" value="rsyi_save_exam_results">
    <input type="hidden" name="exam_id" value="<?php echo esc_attr( $selected_exam_id ); ?>">

    <?php $max_score = (int) $selected_exam->max_score ?: 100;
    $passing_score = isset( $selected_exam->passing_score ) && $selected_exam->passing_score !== null
                     ? (int) $selected_exam->passing_score
                     : (int) round( $max_score * 0.5 );
    ?>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width:36px;">#</th>
                <th><?php esc_html_e( 'الطالب', 'rsyi-sa' ); ?></th>
                <th style="width:100px; text-align:center;"><?php printf( esc_html__( 'الدرجة / %d', 'rsyi-sa' ), $max_score ); ?></th>
                <th style="width:70px; text-align:center;"><?php esc_html_e( 'التقدير', 'rsyi-sa' ); ?></th>
                <th style="width:100px; text-align:center;"><?php esc_html_e( 'النتيجة', 'rsyi-sa' ); ?></th>
                <th><?php esc_html_e( 'ملاحظات', 'rsyi-sa' ); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ( $exam_students as $i => $st ) :
            $res   = $exam_results_map[ (int) $st->profile_id ] ?? null;
            $score = $res ? $res->score : '';
            $grade = $res ? $res->grade : '';
            $notes = $res ? $res->notes : '';
            $is_passing = $res && $score !== '' ? ( (int) $score >= $passing_score ? 1 : 0 ) : null;
        ?>
        <tr class="rsyi-result-row" data-max="<?php echo esc_attr( $max_score ); ?>" data-passing="<?php echo esc_attr( $passing_score ); ?>">
            <td><?php echo $i + 1; ?></td>
            <td>
                <strong><?php echo esc_html( $st->arabic_full_name ); ?></strong>
                <input type="hidden" name="student_ids[]" value="<?php echo esc_attr( $st->profile_id ); ?>">
            </td>
            <td>
                <input type="number" name="score_<?php echo esc_attr( $st->profile_id ); ?>"
                       min="0" max="<?php echo esc_attr( $max_score ); ?>"
                       value="<?php echo esc_attr( $score ); ?>"
                       class="rsyi-score-input"
                       style="width:70px; text-align:center;">
            </td>
            <td>
                <input type="text" name="grade_<?php echo esc_attr( $st->profile_id ); ?>"
                       value="<?php echo esc_attr( $grade ); ?>"
                       class="rsyi-grade-input"
                       style="width:54px; text-align:center;" maxlength="5" placeholder="A/B…">
            </td>
            <td style="text-align:center;">
                <span class="rsyi-pass-badge" style="font-size:12px; font-weight:600; padding:2px 8px; border-radius:10px;
                    <?php if ( $is_passing === null ) echo 'background:#eee; color:#999;';
                    elseif ( $is_passing ) echo 'background:#d4edda; color:#155724;';
                    else echo 'background:#f8d7da; color:#721c24;'; ?>">
                    <?php if ( $is_passing === null ) echo '—';
                    elseif ( $is_passing ) echo esc_html__( 'ناجح', 'rsyi-sa' );
                    else echo esc_html__( 'راسب', 'rsyi-sa' ); ?>
                </span>
            </td>
            <td>
                <input type="text" name="notes_<?php echo esc_attr( $st->profile_id ); ?>"
                       value="<?php echo esc_attr( $notes ); ?>" style="width:100%;">
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <p style="margin-top:16px;">
        <button type="submit" class="button button-primary button-large" id="rsyi-results-save">
            <?php esc_html_e( 'حفظ النتائج', 'rsyi-sa' ); ?>
        </button>
        <span id="rsyi-results-msg" style="margin-right:12px;"></span>
    </p>
</form>
<?php endif; ?>

<!-- ── Stats tab ── -->
<?php elseif ( $active_tab === 'stats' && $selected_exam && current_user_can( 'rsyi_view_exam_stats' ) ) : ?>
<h2 dir="rtl"><?php echo esc_html( $selected_exam->title ); ?> — <?php esc_html_e( 'الإحصائيات', 'rsyi-sa' ); ?></h2>
<div id="rsyi-stats-container" dir="rtl">
    <p style="color:#888;"><?php esc_html_e( 'جارٍ تحميل الإحصائيات...', 'rsyi-sa' ); ?></p>
</div>
<?php endif; ?>

<script>
jQuery(function($){

    // ── Create exam ────────────────────────────────────────────────────────────
    $('#rsyi-create-exam-form').on('submit', function(e){
        e.preventDefault();
        $.post(rsyiSA.ajaxUrl, $(this).serialize(), function(res){
            $('#rsyi-exam-create-msg').css('color', res.success ? 'green' : 'red').text(res.data.message);
            if(res.success) setTimeout(function(){ location.href = '?page=rsyi-exams&tab=list'; }, 1000);
        });
    });

    // ── Update exam ────────────────────────────────────────────────────────────
    $('#rsyi-update-exam-form').on('submit', function(e){
        e.preventDefault();
        $.post(rsyiSA.ajaxUrl, $(this).serialize(), function(res){
            $('#rsyi-exam-update-msg').css('color', res.success ? 'green' : 'red').text(res.data.message);
        });
    });

    // ── Delete exam ────────────────────────────────────────────────────────────
    $(document).on('click', '.rsyi-delete-exam', function(){
        var id = $(this).data('exam-id');
        if ( ! confirm('<?php echo esc_js( __( 'سيتم حذف الامتحان وجميع نتائجه. هل أنت متأكد؟', 'rsyi-sa' ) ); ?>') ) return;
        var $row = $('#exam-row-' + id);
        $.post(rsyiSA.ajaxUrl, { action: 'rsyi_delete_exam', exam_id: id, _nonce: rsyiSA.nonce }, function(res){
            if(res.success){ $row.fadeOut(400, function(){ $row.remove(); }); }
            else { alert(res.data.message); }
        });
    });

    // ── Auto-calculate grades ──────────────────────────────────────────────────
    $('#rsyi-auto-grades-btn').on('click', function(){
        $('.rsyi-result-row').each(function(){
            var $row    = $(this);
            var max     = parseInt($row.data('max')) || 100;
            var passing = parseInt($row.data('passing')) || Math.round(max * 0.5);
            var $score  = $row.find('.rsyi-score-input');
            var $grade  = $row.find('.rsyi-grade-input');
            var $badge  = $row.find('.rsyi-pass-badge');
            var s = parseInt($score.val());
            if ( isNaN(s) || $score.val() === '' ) return;
            var pct = s / max * 100;
            var letter = pct >= 90 ? 'A+' : (pct >= 80 ? 'A' : (pct >= 70 ? 'B' : (pct >= 60 ? 'C' : (pct >= 50 ? 'D' : 'F'))));
            $grade.val(letter);
            if ( s >= passing ) {
                $badge.css({'background':'#d4edda','color':'#155724'}).text('<?php echo esc_js( __( 'ناجح', 'rsyi-sa' ) ); ?>');
            } else {
                $badge.css({'background':'#f8d7da','color':'#721c24'}).text('<?php echo esc_js( __( 'راسب', 'rsyi-sa' ) ); ?>');
            }
        });
    });

    // Update pass/fail badge on score change
    $(document).on('input', '.rsyi-score-input', function(){
        var $row    = $(this).closest('.rsyi-result-row');
        var max     = parseInt($row.data('max')) || 100;
        var passing = parseInt($row.data('passing')) || Math.round(max * 0.5);
        var s       = parseInt($(this).val());
        var $badge  = $row.find('.rsyi-pass-badge');
        if ( isNaN(s) ) { $badge.css({'background':'#eee','color':'#999'}).text('—'); return; }
        if ( s >= passing ) {
            $badge.css({'background':'#d4edda','color':'#155724'}).text('<?php echo esc_js( __( 'ناجح', 'rsyi-sa' ) ); ?>');
        } else {
            $badge.css({'background':'#f8d7da','color':'#721c24'}).text('<?php echo esc_js( __( 'راسب', 'rsyi-sa' ) ); ?>');
        }
    });

    // ── Save results ────────────────────────────────────────────────────────────
    $('#rsyi-results-form').on('submit', function(e){
        e.preventDefault();
        var $btn = $('#rsyi-results-save');
        $btn.prop('disabled', true);
        $.post(rsyiSA.ajaxUrl, $(this).serialize(), function(res){
            $btn.prop('disabled', false);
            $('#rsyi-results-msg').css('color', res.success ? 'green' : 'red').text(res.data.message);
        });
    });

    // ── Export CSV ─────────────────────────────────────────────────────────────
    $('#rsyi-export-btn').on('click', function(){
        var examId = $(this).data('exam-id');
        $.post(rsyiSA.ajaxUrl, { action: 'rsyi_export_exam_results', exam_id: examId, _nonce: rsyiSA.nonce }, function(res){
            if(res.success){
                var blob = new Blob(["\uFEFF" + res.data.csv], {type:'text/csv;charset=utf-8;'});
                var url  = URL.createObjectURL(blob);
                var a    = document.createElement('a');
                a.href = url; a.download = res.data.filename; a.click();
                URL.revokeObjectURL(url);
            } else { alert(res.data.message); }
        });
    });

    // ── Load stats ─────────────────────────────────────────────────────────────
    <?php if ( $active_tab === 'stats' && $selected_exam ) : ?>
    var examId = <?php echo (int) $selected_exam_id; ?>;
    $.post(rsyiSA.ajaxUrl, { action: 'rsyi_get_exam_stats', exam_id: examId, _nonce: rsyiSA.nonce }, function(res){
        if(!res.success){ $('#rsyi-stats-container').html('<p style="color:red;">' + res.data.message + '</p>'); return; }
        var d = res.data;
        var html = '<div style="display:grid; grid-template-columns:repeat(5,1fr); gap:14px; margin-bottom:24px;">';
        html += statsCard(d.count, '<?php echo esc_js( __( 'عدد الطلاب', 'rsyi-sa' ) ); ?>', '#0073aa');
        html += statsCard(d.avg + '%', '<?php echo esc_js( __( 'المتوسط', 'rsyi-sa' ) ); ?>', '#2196F3');
        html += statsCard(d.max_val, '<?php echo esc_js( __( 'أعلى درجة', 'rsyi-sa' ) ); ?>', '#27ae60');
        html += statsCard(d.min_val, '<?php echo esc_js( __( 'أدنى درجة', 'rsyi-sa' ) ); ?>', '#e74c3c');
        html += statsCard(d.pass_pct + '%', '<?php echo esc_js( __( 'نسبة النجاح', 'rsyi-sa' ) ); ?>', d.pass_pct >= 50 ? '#27ae60' : '#e74c3c');
        html += '</div>';

        // Grade distribution
        html += '<div style="background:#fff; border:1px solid #ccd0d4; border-radius:4px; padding:16px; margin-bottom:24px;">';
        html += '<h3 style="margin-top:0;"><?php echo esc_js( __( 'توزيع التقديرات', 'rsyi-sa' ) ); ?></h3>';
        html += '<table style="width:100%; border-collapse:collapse;">';
        html += '<tr style="background:#f6f7f7;"><th style="padding:8px; text-align:center;"><?php echo esc_js( __( 'التقدير', 'rsyi-sa' ) ); ?></th><th style="padding:8px; text-align:center;"><?php echo esc_js( __( 'العدد', 'rsyi-sa' ) ); ?></th><th style="padding:8px; text-align:center;"><?php echo esc_js( __( 'النسبة', 'rsyi-sa' ) ); ?></th></tr>';
        var gradeColors = {'A+':'#155724','A':'#1e7e34','B':'#0c5460','C':'#856404','D':'#856404','F':'#721c24'};
        var gradeBg     = {'A+':'#d4edda','A':'#d4edda','B':'#d1ecf1','C':'#fff3cd','D':'#ffeeba','F':'#f8d7da'};
        $.each(d.dist, function(g, n){
            var pct = d.count > 0 ? Math.round(n/d.count*100) : 0;
            html += '<tr><td style="padding:8px; text-align:center;"><span style="background:' + (gradeBg[g]||'#eee') + '; color:' + (gradeColors[g]||'#333') + '; padding:2px 10px; border-radius:10px; font-weight:700;">' + g + '</span></td>';
            html += '<td style="padding:8px; text-align:center; font-weight:700;">' + n + '</td>';
            html += '<td style="padding:8px; text-align:center; color:#888;">' + pct + '%</td></tr>';
        });
        html += '</table></div>';

        // Ranked students
        html += '<div style="background:#fff; border:1px solid #ccd0d4; border-radius:4px; padding:16px;">';
        html += '<h3 style="margin-top:0;"><?php echo esc_js( __( 'ترتيب الطلاب', 'rsyi-sa' ) ); ?></h3>';
        html += '<table class="wp-list-table widefat fixed striped"><thead><tr>';
        html += '<th style="width:40px; text-align:center;">#</th>';
        html += '<th><?php echo esc_js( __( 'الطالب', 'rsyi-sa' ) ); ?></th>';
        html += '<th style="text-align:center;"><?php echo esc_js( __( 'الدرجة', 'rsyi-sa' ) ); ?></th>';
        html += '<th style="text-align:center;"><?php echo esc_js( __( 'النسبة%', 'rsyi-sa' ) ); ?></th>';
        html += '<th style="text-align:center;"><?php echo esc_js( __( 'التقدير', 'rsyi-sa' ) ); ?></th>';
        html += '</tr></thead><tbody>';
        $.each(d.ranked, function(i, r){
            html += '<tr><td style="text-align:center;">' + (i+1) + '</td>';
            html += '<td>' + r.name + '</td>';
            html += '<td style="text-align:center; font-weight:700;">' + r.score + '</td>';
            html += '<td style="text-align:center;">' + r.pct + '%</td>';
            html += '<td style="text-align:center;"><span style="background:' + (gradeBg[r.grade]||'#eee') + '; color:' + (gradeColors[r.grade]||'#333') + '; padding:2px 10px; border-radius:10px; font-weight:700;">' + r.grade + '</span></td>';
            html += '</tr>';
        });
        html += '</tbody></table></div>';

        $('#rsyi-stats-container').html(html);
    });

    function statsCard(val, label, color){
        return '<div style="background:#fff; border:1px solid #dee2e6; border-top:3px solid ' + color + '; border-radius:6px; padding:16px; text-align:center;">' +
               '<div style="font-size:26px; font-weight:700; color:' + color + ';">' + val + '</div>' +
               '<div style="font-size:12px; color:#888; margin-top:4px;">' + label + '</div></div>';
    }
    <?php endif; ?>
});
</script>
