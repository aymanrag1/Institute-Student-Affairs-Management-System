<?php
/**
 * Admin Template: Exams Management (v1.3.3)
 * Tabs: list | add | edit | questions | results | stats
 * Supports: starts_at/ends_at, 7 question types, auto-grading, show_results, allow_regrade
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
     ORDER BY e.created_at DESC LIMIT 200"
);

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

$q_type_labels = [
    'mcq'          => 'اختيار متعدد — إجابة واحدة',
    'multi_select' => 'اختيار متعدد — أكثر من إجابة',
    'true_false'   => 'صح / خطأ',
    'dropdown'     => 'قائمة منسدلة',
    'fill_blank'   => 'إكمال الناقص',
    'numeric'      => 'إجابة عددية',
    'short_answer' => 'إجابة قصيرة',
    'essay'        => 'مقالة / إنشاء',
    'matching'     => 'توصيل / مطابقة',
    'ordering'     => 'ترتيب العناصر',
    'drag_drop'    => 'سحب وإفلات',
    'image_choice' => 'اختيار يعتمد على صورة',
    'hotspot'      => 'Hotspot — ضغط على صورة',
    'coding'       => 'سؤال برمجي',
    'file_upload'  => 'رفع ملف',
    'audio'        => 'سؤال صوتي',
];

// Format datetime for datetime-local input
function rsyi_dt_local( ?string $dt ): string {
    if ( ! $dt ) return '';
    return substr( str_replace( ' ', 'T', $dt ), 0, 16 );
}
?>
<h1 class="wp-heading-inline"><?php esc_html_e( 'الامتحانات', 'rsyi-sa' ); ?></h1>
<?php if ( current_user_can( 'manage_options' ) ) : ?>
<button type="button" id="rsyi-db-migrate-btn" class="page-title-action"
        style="margin-right:10px; background:#d63638; color:#fff; border-color:#d63638;">
    🔧 <?php esc_html_e( 'إصلاح قاعدة البيانات', 'rsyi-sa' ); ?>
</button>
<span id="rsyi-db-migrate-msg" style="margin-right:8px; font-weight:600;"></span>
<?php endif; ?>
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
    <?php if ( current_user_can( 'rsyi_manage_exams' ) ) : ?>
    <a href="<?php echo esc_url( add_query_arg( [ 'page' => 'rsyi-exams', 'tab' => 'questions', 'exam_id' => $selected_exam_id ], admin_url( 'admin.php' ) ) ); ?>"
       class="nav-tab <?php echo $active_tab === 'questions' ? 'nav-tab-active' : ''; ?>">
        <?php esc_html_e( 'أسئلة الامتحان', 'rsyi-sa' ); ?>
    </a>
    <?php endif; ?>
    <a href="<?php echo esc_url( add_query_arg( [ 'page' => 'rsyi-exams', 'tab' => 'results', 'exam_id' => $selected_exam_id ], admin_url( 'admin.php' ) ) ); ?>"
       class="nav-tab <?php echo $active_tab === 'results' ? 'nav-tab-active' : ''; ?>">
        <?php esc_html_e( 'النتائج', 'rsyi-sa' ); ?>
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
            <th><?php esc_html_e( 'يبدأ', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'ينتهي', 'rsyi-sa' ); ?></th>
            <th style="text-align:center;"><?php esc_html_e( 'الدرجة', 'rsyi-sa' ); ?></th>
            <th style="text-align:center;"><?php esc_html_e( 'التصحيح', 'rsyi-sa' ); ?></th>
            <th style="text-align:center;"><?php esc_html_e( 'الحالة', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'إجراءات', 'rsyi-sa' ); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ( $exams as $e ) :
        $e_status    = $e->status ?? 'published';
        $now         = current_time( 'mysql' );
        $is_open     = $e->starts_at && $e->ends_at && $now >= $e->starts_at && $now <= $e->ends_at;
        $not_started = $e->starts_at && $now < $e->starts_at;
        $ended       = $e->ends_at && $now > $e->ends_at;
    ?>
    <tr id="exam-row-<?php echo esc_attr( $e->id ); ?>">
        <td>
            <strong><?php echo esc_html( $e->title ); ?></strong>
            <?php if ( $is_open ) : ?>
            <span style="background:#d4edda; color:#155724; font-size:10px; padding:1px 6px; border-radius:8px; font-weight:600;">مفتوح</span>
            <?php elseif ( $not_started ) : ?>
            <span style="background:#fff3cd; color:#856404; font-size:10px; padding:1px 6px; border-radius:8px; font-weight:600;">لم يبدأ</span>
            <?php elseif ( $ended ) : ?>
            <span style="background:#f8d7da; color:#721c24; font-size:10px; padding:1px 6px; border-radius:8px; font-weight:600;">انتهى</span>
            <?php endif; ?>
        </td>
        <td><?php echo $e->subject ? esc_html( $e->subject ) : '—'; ?></td>
        <td><?php echo $e->cohort_name ? esc_html( $e->cohort_name ) : '—'; ?></td>
        <td><?php echo esc_html( $type_labels[ $e->exam_type ?? 'written' ] ?? 'نظري' ); ?></td>
        <td style="font-size:12px;">
            <?php echo $e->starts_at ? esc_html( date_i18n( 'j M Y H:i', strtotime( $e->starts_at ) ) ) : '—'; ?>
        </td>
        <td style="font-size:12px;">
            <?php echo $e->ends_at ? esc_html( date_i18n( 'j M Y H:i', strtotime( $e->ends_at ) ) ) : '—'; ?>
        </td>
        <td style="text-align:center;"><?php echo esc_html( $e->max_score ); ?></td>
        <td style="text-align:center; font-size:11px;">
            <?php echo $e->auto_grade ? '<span style="color:#27ae60;">تلقائي</span>' : '<span style="color:#e67e22;">يدوي</span>'; ?>
        </td>
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
<?php include __DIR__ . '/partials/exam-form.php'; ?>

<!-- ── Edit exam tab ── -->
<?php elseif ( $active_tab === 'edit' && $selected_exam && current_user_can( 'rsyi_edit_exam' ) ) : ?>
<?php include __DIR__ . '/partials/exam-form.php'; ?>

<!-- ── Questions tab ── -->
<?php elseif ( $active_tab === 'questions' && $selected_exam && current_user_can( 'rsyi_manage_exams' ) ) : ?>
<h2 dir="rtl">
    <?php echo esc_html( $selected_exam->title ); ?> —
    <?php esc_html_e( 'أسئلة الامتحان', 'rsyi-sa' ); ?>
</h2>

<!-- Question form -->
<div id="rsyi-q-form-wrap" style="background:#fff; border:1px solid #ccd0d4; border-radius:4px; padding:20px; max-width:760px; margin-bottom:20px; display:none;" dir="rtl">
    <h3 id="rsyi-q-form-title" style="margin-top:0;"><?php esc_html_e( 'إضافة سؤال جديد', 'rsyi-sa' ); ?></h3>
    <input type="hidden" id="rsyi-q-id" value="">
    <input type="hidden" id="rsyi-q-exam-id" value="<?php echo esc_attr( $selected_exam_id ); ?>">

    <table class="form-table" style="margin:0;">
        <tr>
            <th style="width:130px;"><?php esc_html_e( 'رقم السؤال', 'rsyi-sa' ); ?></th>
            <td><input type="number" id="rsyi-q-number" min="1" value="1" style="width:70px; text-align:center;"></td>
        </tr>
        <tr>
            <th><?php esc_html_e( 'نوع السؤال', 'rsyi-sa' ); ?></th>
            <td>
                <select id="rsyi-q-type" style="min-width:220px;">
                    <?php foreach ( $q_type_labels as $val => $lbl ) : ?>
                    <option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $lbl ); ?></option>
                    <?php endforeach; ?>
                </select>
                <span style="margin-right:8px; font-size:12px; color:#0073aa;" id="rsyi-q-type-hint"></span>
            </td>
        </tr>
        <tr>
            <th><?php esc_html_e( 'نص السؤال', 'rsyi-sa' ); ?></th>
            <td><textarea id="rsyi-q-text" rows="3" style="width:100%; min-width:460px;" required></textarea></td>
        </tr>

        <!-- ── MCQ options ── -->
        <tr id="rsyi-q-mcq-row" style="display:none;">
            <th><?php esc_html_e( 'الخيارات', 'rsyi-sa' ); ?></th>
            <td>
                <div id="rsyi-mcq-options"></div>
                <button type="button" id="rsyi-mcq-add-option" class="button button-small" style="margin-top:8px;">
                    + <?php esc_html_e( 'إضافة خيار', 'rsyi-sa' ); ?>
                </button>
                <p class="description"><?php esc_html_e( 'ضع علامة ✓ على الإجابة الصحيحة.', 'rsyi-sa' ); ?></p>
            </td>
        </tr>

        <!-- ── True/False ── -->
        <tr id="rsyi-q-tf-row" style="display:none;">
            <th><?php esc_html_e( 'الإجابة الصحيحة', 'rsyi-sa' ); ?></th>
            <td>
                <label style="margin-left:16px;"><input type="radio" name="rsyi_tf_answer" value="true"> صح ✓</label>
                <label><input type="radio" name="rsyi_tf_answer" value="false"> خطأ ✗</label>
            </td>
        </tr>

        <!-- ── Matching pairs ── -->
        <tr id="rsyi-q-matching-row" style="display:none;">
            <th><?php esc_html_e( 'أزواج التوصيل', 'rsyi-sa' ); ?></th>
            <td>
                <div id="rsyi-matching-pairs"></div>
                <button type="button" id="rsyi-matching-add-pair" class="button button-small" style="margin-top:8px;">
                    + <?php esc_html_e( 'إضافة زوج', 'rsyi-sa' ); ?>
                </button>
                <p class="description"><?php esc_html_e( 'العمود الأول: العبارة — العمود الثاني: المطابق الصحيح.', 'rsyi-sa' ); ?></p>
            </td>
        </tr>

        <!-- ── Fill blank ── -->
        <tr id="rsyi-q-fill-row" style="display:none;">
            <th><?php esc_html_e( 'الإجابة الصحيحة', 'rsyi-sa' ); ?></th>
            <td>
                <input type="text" id="rsyi-q-fill-answer" style="width:100%; max-width:380px;" placeholder="<?php esc_attr_e( 'اكتب الإجابة المقبولة...', 'rsyi-sa' ); ?>">
                <p class="description"><?php esc_html_e( 'التصحيح لا يفرق بين الحروف الكبيرة والصغيرة.', 'rsyi-sa' ); ?></p>
            </td>
        </tr>

        <!-- ── Short answer / Essay (reference) ── -->
        <tr id="rsyi-q-ref-row" style="display:none;">
            <th><?php esc_html_e( 'نموذج الإجابة', 'rsyi-sa' ); ?></th>
            <td>
                <textarea id="rsyi-q-ref-answer" rows="3" style="width:100%;" placeholder="<?php esc_attr_e( 'للمصحح فقط — لا تصحيح تلقائي.', 'rsyi-sa' ); ?>"></textarea>
            </td>
        </tr>

        <!-- ── Ordering ── -->
        <tr id="rsyi-q-ordering-row" style="display:none;">
            <th><?php esc_html_e( 'العناصر بالترتيب الصحيح', 'rsyi-sa' ); ?></th>
            <td>
                <div id="rsyi-ordering-items"></div>
                <button type="button" id="rsyi-ordering-add-item" class="button button-small" style="margin-top:8px;">
                    + <?php esc_html_e( 'إضافة عنصر', 'rsyi-sa' ); ?>
                </button>
                <p class="description"><?php esc_html_e( 'أضف العناصر بالترتيب الصحيح — يُعرض للطالب مخلوطاً.', 'rsyi-sa' ); ?></p>
            </td>
        </tr>

        <!-- ── Multi-select ── -->
        <tr id="rsyi-q-multiselect-row" style="display:none;">
            <th><?php esc_html_e( 'الخيارات', 'rsyi-sa' ); ?></th>
            <td>
                <div id="rsyi-multiselect-options"></div>
                <button type="button" id="rsyi-multiselect-add-option" class="button button-small" style="margin-top:8px;">
                    + <?php esc_html_e( 'إضافة خيار', 'rsyi-sa' ); ?>
                </button>
                <p class="description"><?php esc_html_e( 'ضع ✓ على كل الإجابات الصحيحة (قد تكون أكثر من واحدة).', 'rsyi-sa' ); ?></p>
            </td>
        </tr>

        <!-- ── Dropdown ── -->
        <tr id="rsyi-q-dropdown-row" style="display:none;">
            <th><?php esc_html_e( 'الخيارات', 'rsyi-sa' ); ?></th>
            <td>
                <div id="rsyi-dropdown-options"></div>
                <button type="button" id="rsyi-dropdown-add-option" class="button button-small" style="margin-top:8px;">
                    + <?php esc_html_e( 'إضافة خيار', 'rsyi-sa' ); ?>
                </button>
                <p class="description"><?php esc_html_e( 'ضع علامة ✓ على الإجابة الصحيحة — يُعرض للطالب كقائمة منسدلة.', 'rsyi-sa' ); ?></p>
            </td>
        </tr>

        <!-- ── Numeric ── -->
        <tr id="rsyi-q-numeric-row" style="display:none;">
            <th><?php esc_html_e( 'الإجابة الصحيحة', 'rsyi-sa' ); ?></th>
            <td>
                <input type="number" id="rsyi-q-numeric-answer" step="any" placeholder="مثال: 42.5" style="width:140px;">
                <br>
                <label style="margin-top:8px; display:inline-block;">
                    <?php esc_html_e( 'هامش الخطأ المسموح:', 'rsyi-sa' ); ?>
                    <input type="number" id="rsyi-q-numeric-tolerance" min="0" step="any" value="0" style="width:80px; margin-right:6px;">
                </label>
                <p class="description"><?php esc_html_e( '0 = تطابق تام. مثال: إجابة 10 وهامش 0.5 يقبل 9.5 → 10.5', 'rsyi-sa' ); ?></p>
            </td>
        </tr>

        <!-- ── Drag & Drop (same structure as ordering) ── -->
        <tr id="rsyi-q-dragdrop-row" style="display:none;">
            <th><?php esc_html_e( 'العناصر بالترتيب الصحيح', 'rsyi-sa' ); ?></th>
            <td>
                <div id="rsyi-dragdrop-items"></div>
                <button type="button" id="rsyi-dragdrop-add-item" class="button button-small" style="margin-top:8px;">
                    + <?php esc_html_e( 'إضافة عنصر', 'rsyi-sa' ); ?>
                </button>
                <p class="description"><?php esc_html_e( 'الطالب يُرتّب العناصر بالسحب والإفلات.', 'rsyi-sa' ); ?></p>
            </td>
        </tr>

        <!-- ── Image Choice (MCQ variant with prominent image) ── -->
        <tr id="rsyi-q-imagechoice-row" style="display:none;">
            <th><?php esc_html_e( 'الخيارات', 'rsyi-sa' ); ?></th>
            <td>
                <div id="rsyi-imagechoice-options"></div>
                <button type="button" id="rsyi-imagechoice-add-option" class="button button-small" style="margin-top:8px;">
                    + <?php esc_html_e( 'إضافة خيار', 'rsyi-sa' ); ?>
                </button>
                <p class="description"><?php esc_html_e( 'أضف صورة للسؤال من حقل الصورة أعلاه — ضع ✓ على الإجابة الصحيحة.', 'rsyi-sa' ); ?></p>
            </td>
        </tr>

        <!-- ── Hotspot ── -->
        <tr id="rsyi-q-hotspot-row" style="display:none;">
            <th><?php esc_html_e( 'مناطق الضغط', 'rsyi-sa' ); ?></th>
            <td>
                <p class="description" style="color:#0073aa;"><?php esc_html_e( 'أضف صورة من حقل الصورة أعلاه، ثم حدد المناطق (x%, y%, عرض%, ارتفاع%).', 'rsyi-sa' ); ?></p>
                <div id="rsyi-hotspot-regions"></div>
                <button type="button" id="rsyi-hotspot-add-region" class="button button-small" style="margin-top:8px;">
                    + <?php esc_html_e( 'إضافة منطقة', 'rsyi-sa' ); ?>
                </button>
            </td>
        </tr>

        <!-- ── Coding ── -->
        <tr id="rsyi-q-coding-row" style="display:none;">
            <th><?php esc_html_e( 'إعدادات الكود', 'rsyi-sa' ); ?></th>
            <td>
                <label><?php esc_html_e( 'لغة البرمجة:', 'rsyi-sa' ); ?>
                    <select id="rsyi-q-coding-lang" style="margin-right:8px;">
                        <option value="python">Python</option>
                        <option value="javascript">JavaScript</option>
                        <option value="java">Java</option>
                        <option value="c">C</option>
                        <option value="cpp">C++</option>
                        <option value="sql">SQL</option>
                        <option value="html">HTML/CSS</option>
                        <option value="other">أخرى</option>
                    </select>
                </label>
                <br>
                <label style="margin-top:8px; display:block;"><?php esc_html_e( 'كود البداية (اختياري):', 'rsyi-sa' ); ?></label>
                <textarea id="rsyi-q-coding-starter" rows="4" style="width:100%; font-family:monospace; margin-top:4px;" placeholder="# اكتب كود البداية هنا..."></textarea>
                <p class="description"><?php esc_html_e( 'التصحيح يدوي من المدرس.', 'rsyi-sa' ); ?></p>
            </td>
        </tr>

        <!-- ── File Upload ── -->
        <tr id="rsyi-q-fileupload-row" style="display:none;">
            <th><?php esc_html_e( 'إعدادات الرفع', 'rsyi-sa' ); ?></th>
            <td>
                <label><?php esc_html_e( 'أنواع الملفات المسموحة:', 'rsyi-sa' ); ?>
                    <input type="text" id="rsyi-q-fileupload-types" value="pdf,doc,docx,jpg,png" style="width:220px; margin-right:8px;">
                </label>
                <br>
                <label style="margin-top:8px; display:inline-block;"><?php esc_html_e( 'الحجم الأقصى (MB):', 'rsyi-sa' ); ?>
                    <input type="number" id="rsyi-q-fileupload-maxmb" value="10" min="1" max="100" style="width:70px; margin-right:8px;">
                </label>
                <p class="description"><?php esc_html_e( 'الطالب يرفع ملفاً — التصحيح يدوي.', 'rsyi-sa' ); ?></p>
            </td>
        </tr>

        <!-- ── Audio ── -->
        <tr id="rsyi-q-audio-row" style="display:none;">
            <th><?php esc_html_e( 'الملف الصوتي', 'rsyi-sa' ); ?></th>
            <td>
                <input type="url" id="rsyi-q-audio-url" style="width:100%; max-width:460px;" placeholder="<?php esc_attr_e( 'رابط ملف الصوت (MP3/OGG)...', 'rsyi-sa' ); ?>">
                <p class="description"><?php esc_html_e( 'الطالب يستمع للتسجيل ثم يكتب إجابته — التصحيح يدوي.', 'rsyi-sa' ); ?></p>
            </td>
        </tr>

        <!-- ── Explanation ── -->
        <tr>
            <th><?php esc_html_e( 'شرح الإجابة (اختياري)', 'rsyi-sa' ); ?></th>
            <td><textarea id="rsyi-q-explanation" rows="2" style="width:100%;" placeholder="<?php esc_attr_e( 'يُعرض للطالب بعد التصحيح...', 'rsyi-sa' ); ?>"></textarea></td>
        </tr>

        <!-- ── Image ── -->
        <tr>
            <th><?php esc_html_e( 'صورة (اختياري)', 'rsyi-sa' ); ?></th>
            <td>
                <input type="hidden" id="rsyi-q-image-id" value="">
                <div id="rsyi-q-image-preview" style="margin-bottom:8px; display:none;">
                    <img id="rsyi-q-image-thumb" src="" style="max-width:200px; max-height:150px; border:1px solid #ddd; border-radius:4px; display:block; margin-bottom:6px;">
                    <button type="button" id="rsyi-q-remove-image" class="button button-small" style="color:#a00; border-color:#a00;">
                        ✕ <?php esc_html_e( 'إزالة الصورة', 'rsyi-sa' ); ?>
                    </button>
                </div>
                <button type="button" id="rsyi-q-select-image" class="button">
                    🖼 <?php esc_html_e( 'اختر صورة', 'rsyi-sa' ); ?>
                </button>
            </td>
        </tr>

        <!-- ── Marks ── -->
        <tr>
            <th><?php esc_html_e( 'الدرجة', 'rsyi-sa' ); ?></th>
            <td><input type="number" id="rsyi-q-marks" min="0" step="0.5" value="1" style="width:80px; text-align:center;"></td>
        </tr>
    </table>

    <p style="margin-top:16px;">
        <button type="button" id="rsyi-q-save" class="button button-primary"><?php esc_html_e( 'حفظ السؤال', 'rsyi-sa' ); ?></button>
        <button type="button" id="rsyi-q-cancel" class="button" style="margin-right:8px;"><?php esc_html_e( 'إلغاء', 'rsyi-sa' ); ?></button>
        <span id="rsyi-q-msg" style="margin-right:10px;"></span>
    </p>
</div>

<p>
    <button type="button" id="rsyi-q-add-btn" class="button button-primary">
        + <?php esc_html_e( 'إضافة سؤال جديد', 'rsyi-sa' ); ?>
    </button>
</p>

<div id="rsyi-questions-list" dir="rtl">
    <p style="color:#888;"><?php esc_html_e( 'جارٍ تحميل الأسئلة…', 'rsyi-sa' ); ?></p>
</div>

<script>
jQuery(function($){

    var examId  = <?php echo (int) $selected_exam_id; ?>;
    var nonce   = rsyiSA.nonce;
    var ajaxUrl = rsyiSA.ajaxUrl;
    var mediaFrame;

    var qTypeHints = {
        mcq:           '✅ تصحيح تلقائي — إجابة صحيحة واحدة',
        multi_select:  '✅ تصحيح تلقائي — أكثر من إجابة صحيحة',
        true_false:    '✅ تصحيح تلقائي — صح أو خطأ',
        dropdown:      '✅ تصحيح تلقائي — قائمة منسدلة',
        fill_blank:    '✅ تصحيح تلقائي — مطابقة نصية',
        numeric:       '✅ تصحيح تلقائي — رقمي مع هامش خطأ',
        matching:      '✅ تصحيح تلقائي — توصيل العناصر',
        ordering:      '✅ تصحيح تلقائي — ترتيب بالسحب',
        drag_drop:     '✅ تصحيح تلقائي — سحب وإفلات',
        image_choice:  '✅ تصحيح تلقائي — اختيار من صورة',
        hotspot:       '✅ تصحيح تلقائي — ضغط على منطقة',
        short_answer:  '✏️ تصحيح يدوي — إجابة قصيرة',
        essay:         '✏️ تصحيح يدوي — مقالة',
        coding:        '✏️ تصحيح يدوي — كود برمجي',
        file_upload:   '✏️ تصحيح يدوي — رفع ملف',
        audio:         '✏️ تصحيح يدوي — سؤال صوتي',
    };

    // All type-specific row IDs
    var allTypeRows = [
        '#rsyi-q-mcq-row', '#rsyi-q-tf-row', '#rsyi-q-matching-row',
        '#rsyi-q-fill-row', '#rsyi-q-ref-row', '#rsyi-q-ordering-row',
        '#rsyi-q-multiselect-row', '#rsyi-q-dropdown-row', '#rsyi-q-numeric-row',
        '#rsyi-q-dragdrop-row', '#rsyi-q-imagechoice-row', '#rsyi-q-hotspot-row',
        '#rsyi-q-coding-row', '#rsyi-q-fileupload-row', '#rsyi-q-audio-row'
    ].join(', ');

    // ── Question type change ──────────────────────────────────────────────────
    function updateTypeFields(type) {
        $(allTypeRows).hide();
        $('#rsyi-q-type-hint').text(qTypeHints[type] || '');
        var map = {
            mcq:          '#rsyi-q-mcq-row',
            multi_select: '#rsyi-q-multiselect-row',
            true_false:   '#rsyi-q-tf-row',
            dropdown:     '#rsyi-q-dropdown-row',
            fill_blank:   '#rsyi-q-fill-row',
            numeric:      '#rsyi-q-numeric-row',
            short_answer: '#rsyi-q-ref-row',
            essay:        '#rsyi-q-ref-row',
            matching:     '#rsyi-q-matching-row',
            ordering:     '#rsyi-q-ordering-row',
            drag_drop:    '#rsyi-q-dragdrop-row',
            image_choice: '#rsyi-q-imagechoice-row',
            hotspot:      '#rsyi-q-hotspot-row',
            coding:       '#rsyi-q-coding-row',
            file_upload:  '#rsyi-q-fileupload-row',
            audio:        '#rsyi-q-audio-row',
        };
        if (map[type]) $(map[type]).show();
    }

    $('#rsyi-q-type').on('change', function(){ updateTypeFields($(this).val()); });
    updateTypeFields($('#rsyi-q-type').val());

    // ── Generic option-list builder (used by MCQ, multi_select, dropdown, image_choice)
    function makeOptRow(containerId, radioName, text, isCorrect, isCheckbox) {
        var container = $('#' + containerId);
        var idx = container.children().length;
        var inputType = isCheckbox ? 'checkbox' : 'radio';
        var html = '<div class="rsyi-opt-row" style="display:flex;align-items:center;gap:8px;margin-bottom:6px;" data-idx="' + idx + '">' +
            '<input type="' + inputType + '" name="' + radioName + '" value="' + idx + '"' + (isCorrect ? ' checked' : '') + ' title="الإجابة الصحيحة" style="cursor:pointer;flex-shrink:0;">' +
            '<input type="text" class="rsyi-opt-text regular-text" value="' + $('<div>').text(text||'').html() + '" placeholder="نص الخيار..." style="flex:1;">' +
            '<button type="button" class="button button-small rsyi-opt-remove" style="color:#a00;border-color:#a00;">✕</button>' +
            '</div>';
        container.append(html);
    }

    // MCQ
    function addMcqOption(t,c){ makeOptRow('rsyi-mcq-options','rsyi_mcq_correct',t,c,false); }
    $('#rsyi-mcq-add-option').on('click', function(){ addMcqOption('',false); });
    $(document).on('click','#rsyi-mcq-options .rsyi-opt-remove',function(){ $(this).closest('.rsyi-opt-row').remove(); });

    // Multi-select
    function addMsOption(t,c){ makeOptRow('rsyi-multiselect-options','rsyi_ms_correct',t,c,true); }
    $('#rsyi-multiselect-add-option').on('click', function(){ addMsOption('',false); });
    $(document).on('click','#rsyi-multiselect-options .rsyi-opt-remove',function(){ $(this).closest('.rsyi-opt-row').remove(); });

    // Dropdown
    function addDdOption(t,c){ makeOptRow('rsyi-dropdown-options','rsyi_dd_correct',t,c,false); }
    $('#rsyi-dropdown-add-option').on('click', function(){ addDdOption('',false); });
    $(document).on('click','#rsyi-dropdown-options .rsyi-opt-remove',function(){ $(this).closest('.rsyi-opt-row').remove(); });

    // Image choice
    function addIcOption(t,c){ makeOptRow('rsyi-imagechoice-options','rsyi_ic_correct',t,c,false); }
    $('#rsyi-imagechoice-add-option').on('click', function(){ addIcOption('',false); });
    $(document).on('click','#rsyi-imagechoice-options .rsyi-opt-remove',function(){ $(this).closest('.rsyi-opt-row').remove(); });

    // ── Matching helpers ──────────────────────────────────────────────────────
    function addMatchingPair(premise, match) {
        var html = '<div class="rsyi-match-pair" style="display:flex;gap:8px;margin-bottom:6px;">' +
            '<input type="text" class="rsyi-premise regular-text" value="' + $('<div>').text(premise||'').html() + '" placeholder="العبارة..." style="flex:1;">' +
            '<span style="line-height:32px;font-size:16px;color:#0073aa;">↔</span>' +
            '<input type="text" class="rsyi-match regular-text" value="' + $('<div>').text(match||'').html() + '" placeholder="المطابق الصحيح..." style="flex:1;">' +
            '<button type="button" class="button button-small rsyi-match-remove" style="color:#a00;border-color:#a00;">✕</button>' +
            '</div>';
        $('#rsyi-matching-pairs').append(html);
    }
    $('#rsyi-matching-add-pair').on('click', function(){ addMatchingPair('',''); });
    $(document).on('click','.rsyi-match-remove',function(){ $(this).closest('.rsyi-match-pair').remove(); });

    // ── Generic ordered-item list (Ordering + Drag&Drop) ─────────────────────
    function makeOrderItem(containerId, text) {
        var idx = $('#' + containerId + ' .rsyi-order-item').length + 1;
        var html = '<div class="rsyi-order-item" style="display:flex;gap:8px;margin-bottom:6px;align-items:center;">' +
            '<span style="font-weight:700;color:#888;width:20px;text-align:center;">' + idx + '</span>' +
            '<input type="text" class="rsyi-order-text regular-text" value="' + $('<div>').text(text||'').html() + '" placeholder="نص العنصر..." style="flex:1;">' +
            '<button type="button" class="button button-small rsyi-order-remove" style="color:#a00;border-color:#a00;">✕</button>' +
            '</div>';
        $('#' + containerId).append(html);
    }
    function addOrderingItem(t){ makeOrderItem('rsyi-ordering-items',t); }
    function addDragDropItem(t){ makeOrderItem('rsyi-dragdrop-items',t); }
    $('#rsyi-ordering-add-item').on('click', function(){ addOrderingItem(''); });
    $('#rsyi-dragdrop-add-item').on('click', function(){ addDragDropItem(''); });
    $(document).on('click','.rsyi-order-remove',function(){ $(this).closest('.rsyi-order-item').remove(); });

    // ── Hotspot region helpers ────────────────────────────────────────────────
    function addHotspotRegion(label, x, y, w, h, isCorrect) {
        var idx = $('#rsyi-hotspot-regions .rsyi-hs-region').length;
        var html = '<div class="rsyi-hs-region" style="display:flex;gap:6px;align-items:center;margin-bottom:6px;flex-wrap:wrap;">' +
            '<input type="radio" name="rsyi_hs_correct" value="' + idx + '"' + (isCorrect?' checked':'') + ' title="المنطقة الصحيحة">' +
            '<input type="text" class="rsyi-hs-label" value="' + $('<div>').text(label||'').html() + '" placeholder="اسم المنطقة" style="width:100px;">' +
            'X%:<input type="number" class="rsyi-hs-x" value="' + (x||0) + '" min="0" max="100" step="0.1" style="width:55px;">' +
            'Y%:<input type="number" class="rsyi-hs-y" value="' + (y||0) + '" min="0" max="100" step="0.1" style="width:55px;">' +
            'W%:<input type="number" class="rsyi-hs-w" value="' + (w||10) + '" min="1" max="100" step="0.1" style="width:55px;">' +
            'H%:<input type="number" class="rsyi-hs-h" value="' + (h||10) + '" min="1" max="100" step="0.1" style="width:55px;">' +
            '<button type="button" class="button button-small rsyi-hs-remove" style="color:#a00;border-color:#a00;">✕</button>' +
            '</div>';
        $('#rsyi-hotspot-regions').append(html);
    }
    $('#rsyi-hotspot-add-region').on('click', function(){ addHotspotRegion('','',0,0,10,10,false); });
    $(document).on('click','.rsyi-hs-remove',function(){ $(this).closest('.rsyi-hs-region').remove(); });

    // ── Build options / correct_answer from UI ────────────────────────────────
    function getOptionsAndAnswer() {
        var type = $('#rsyi-q-type').val();
        var options = null, correct_answer = null;

        switch(type) {
            case 'mcq':
            case 'image_choice': {
                var containerSel = (type==='mcq') ? '#rsyi-mcq-options' : '#rsyi-imagechoice-options';
                var radioName    = (type==='mcq') ? 'rsyi_mcq_correct' : 'rsyi_ic_correct';
                var correctIdx   = parseInt($('input[name="'+radioName+'"]:checked').val());
                var opts = [];
                $(containerSel + ' .rsyi-opt-row').each(function(i){
                    opts.push({ text: $(this).find('.rsyi-opt-text').val(), correct: (i===correctIdx) });
                });
                options = JSON.stringify(opts);
                break;
            }
            case 'multi_select': {
                var opts = [], correctIdxs = [];
                $('#rsyi-multiselect-options .rsyi-opt-row').each(function(i){
                    var checked = $(this).find('input[type=checkbox]').is(':checked');
                    opts.push({ text: $(this).find('.rsyi-opt-text').val(), correct: checked });
                    if (checked) correctIdxs.push(i);
                });
                options        = JSON.stringify(opts);
                correct_answer = JSON.stringify(correctIdxs);
                break;
            }
            case 'dropdown': {
                var correctIdx = parseInt($('input[name="rsyi_dd_correct"]:checked').val()) || 0;
                var opts = [];
                $('#rsyi-dropdown-options .rsyi-opt-row').each(function(){
                    opts.push({ text: $(this).find('.rsyi-opt-text').val() });
                });
                options        = JSON.stringify(opts);
                correct_answer = String(correctIdx);
                break;
            }
            case 'true_false':
                correct_answer = $('input[name="rsyi_tf_answer"]:checked').val() || 'true';
                break;
            case 'fill_blank':
                correct_answer = $('#rsyi-q-fill-answer').val();
                break;
            case 'numeric':
                correct_answer = $('#rsyi-q-numeric-answer').val();
                options        = JSON.stringify({ tolerance: parseFloat($('#rsyi-q-numeric-tolerance').val()) || 0 });
                break;
            case 'short_answer':
            case 'essay':
            case 'audio':
                correct_answer = $('#rsyi-q-ref-answer').val();
                if (type==='audio') {
                    options = JSON.stringify({ audio_url: $('#rsyi-q-audio-url').val() });
                }
                break;
            case 'matching': {
                var pairs = [];
                $('#rsyi-matching-pairs .rsyi-match-pair').each(function(){
                    pairs.push({ premise: $(this).find('.rsyi-premise').val(), match: $(this).find('.rsyi-match').val() });
                });
                options = JSON.stringify(pairs);
                break;
            }
            case 'ordering': {
                var items = [];
                $('#rsyi-ordering-items .rsyi-order-item').each(function(i){
                    items.push({ text: $(this).find('.rsyi-order-text').val(), order: i+1 });
                });
                options = JSON.stringify(items);
                break;
            }
            case 'drag_drop': {
                var items = [];
                $('#rsyi-dragdrop-items .rsyi-order-item').each(function(i){
                    items.push({ text: $(this).find('.rsyi-order-text').val(), order: i+1 });
                });
                options = JSON.stringify(items);
                break;
            }
            case 'hotspot': {
                var regions = [];
                var correctRegion = parseInt($('input[name="rsyi_hs_correct"]:checked').val()) || 0;
                $('#rsyi-hotspot-regions .rsyi-hs-region').each(function(i){
                    regions.push({
                        label: $(this).find('.rsyi-hs-label').val(),
                        x: parseFloat($(this).find('.rsyi-hs-x').val())||0,
                        y: parseFloat($(this).find('.rsyi-hs-y').val())||0,
                        w: parseFloat($(this).find('.rsyi-hs-w').val())||10,
                        h: parseFloat($(this).find('.rsyi-hs-h').val())||10
                    });
                });
                options        = JSON.stringify({ regions: regions });
                correct_answer = String(correctRegion);
                break;
            }
            case 'coding':
                options = JSON.stringify({
                    language:     $('#rsyi-q-coding-lang').val(),
                    starter_code: $('#rsyi-q-coding-starter').val()
                });
                break;
            case 'file_upload':
                options = JSON.stringify({
                    allowed_types: $('#rsyi-q-fileupload-types').val() || 'pdf,doc,docx',
                    max_mb:        parseInt($('#rsyi-q-fileupload-maxmb').val()) || 10
                });
                break;
        }

        return { options: options, correct_answer: correct_answer };
    }

    // ── Reset form ────────────────────────────────────────────────────────────
    function resetForm() {
        $('#rsyi-q-id').val('');
        $('#rsyi-q-number').val( $('#rsyi-questions-list table tbody tr').length + 1 );
        $('#rsyi-q-text').val('');
        $('#rsyi-q-type').val('essay').trigger('change');
        $('#rsyi-q-marks').val('1');
        $('#rsyi-q-explanation').val('');
        $('#rsyi-q-image-id').val('');
        $('#rsyi-q-image-preview').hide();
        $('#rsyi-q-fill-answer').val('');
        $('#rsyi-q-ref-answer').val('');
        $('#rsyi-q-numeric-answer').val('');
        $('#rsyi-q-numeric-tolerance').val('0');
        $('#rsyi-q-audio-url').val('');
        $('#rsyi-q-coding-lang').val('python');
        $('#rsyi-q-coding-starter').val('');
        $('#rsyi-q-fileupload-types').val('pdf,doc,docx,jpg,png');
        $('#rsyi-q-fileupload-maxmb').val('10');
        $('#rsyi-mcq-options, #rsyi-multiselect-options, #rsyi-dropdown-options, #rsyi-imagechoice-options').empty();
        $('#rsyi-matching-pairs, #rsyi-ordering-items, #rsyi-dragdrop-items, #rsyi-hotspot-regions').empty();
        $('input[name="rsyi_tf_answer"], input[name="rsyi_hs_correct"]').prop('checked', false);
        $('#rsyi-q-form-title').text('<?php echo esc_js( __( 'إضافة سؤال جديد', 'rsyi-sa' ) ); ?>');
        $('#rsyi-q-msg').text('');
    }

    // ── Populate form for editing ─────────────────────────────────────────────
    function populateForm(q) {
        $('#rsyi-q-id').val(q.id);
        $('#rsyi-q-number').val(q.question_number);
        $('#rsyi-q-text').val(q.question_text);
        $('#rsyi-q-marks').val(q.marks);
        $('#rsyi-q-explanation').val(q.explanation || '');
        $('#rsyi-q-type').val(q.question_type || 'essay').trigger('change');
        $('#rsyi-q-form-title').text('<?php echo esc_js( __( 'تعديل السؤال', 'rsyi-sa' ) ); ?> #' + q.question_number);

        if (q.image_url) {
            $('#rsyi-q-image-id').val(q.image_id);
            $('#rsyi-q-image-thumb').attr('src', q.image_url);
            $('#rsyi-q-image-preview').show();
        } else {
            $('#rsyi-q-image-id').val('');
            $('#rsyi-q-image-preview').hide();
        }

        var type = q.question_type || 'essay';
        var opts = null;
        try { opts = q.options ? JSON.parse(q.options) : null; } catch(e) {}

        switch(type) {
            case 'mcq':
                $('#rsyi-mcq-options').empty();
                if (Array.isArray(opts)) opts.forEach(function(o){ addMcqOption(o.text, o.correct); });
                break;
            case 'multi_select':
                $('#rsyi-multiselect-options').empty();
                if (Array.isArray(opts)) opts.forEach(function(o){ addMsOption(o.text, o.correct); });
                break;
            case 'dropdown':
                $('#rsyi-dropdown-options').empty();
                if (Array.isArray(opts)) {
                    var correctDd = parseInt(q.correct_answer) || 0;
                    opts.forEach(function(o,i){ addDdOption(o.text, i===correctDd); });
                }
                break;
            case 'true_false':
                $('input[name="rsyi_tf_answer"][value="' + (q.correct_answer||'true') + '"]').prop('checked', true);
                break;
            case 'fill_blank':
                $('#rsyi-q-fill-answer').val(q.correct_answer || '');
                break;
            case 'numeric':
                $('#rsyi-q-numeric-answer').val(q.correct_answer || '');
                if (opts && opts.tolerance !== undefined) $('#rsyi-q-numeric-tolerance').val(opts.tolerance);
                break;
            case 'short_answer':
            case 'essay':
                $('#rsyi-q-ref-answer').val(q.correct_answer || '');
                break;
            case 'matching':
                $('#rsyi-matching-pairs').empty();
                if (Array.isArray(opts)) opts.forEach(function(p){ addMatchingPair(p.premise, p.match); });
                break;
            case 'ordering':
                $('#rsyi-ordering-items').empty();
                if (Array.isArray(opts)) {
                    opts.sort(function(a,b){ return a.order-b.order; });
                    opts.forEach(function(o){ addOrderingItem(o.text); });
                }
                break;
            case 'drag_drop':
                $('#rsyi-dragdrop-items').empty();
                if (Array.isArray(opts)) {
                    opts.sort(function(a,b){ return a.order-b.order; });
                    opts.forEach(function(o){ addDragDropItem(o.text); });
                }
                break;
            case 'image_choice':
                $('#rsyi-imagechoice-options').empty();
                if (Array.isArray(opts)) opts.forEach(function(o){ addIcOption(o.text, o.correct); });
                break;
            case 'hotspot':
                $('#rsyi-hotspot-regions').empty();
                if (opts && Array.isArray(opts.regions)) {
                    var correctHs = parseInt(q.correct_answer) || 0;
                    opts.regions.forEach(function(r,i){
                        addHotspotRegion(r.label, r.x, r.y, r.w, r.h, i===correctHs);
                    });
                }
                break;
            case 'coding':
                if (opts) {
                    $('#rsyi-q-coding-lang').val(opts.language || 'python');
                    $('#rsyi-q-coding-starter').val(opts.starter_code || '');
                }
                break;
            case 'file_upload':
                if (opts) {
                    $('#rsyi-q-fileupload-types').val(opts.allowed_types || 'pdf,doc,docx,jpg,png');
                    $('#rsyi-q-fileupload-maxmb').val(opts.max_mb || 10);
                }
                break;
            case 'audio':
                $('#rsyi-q-ref-answer').val(q.correct_answer || '');
                if (opts) $('#rsyi-q-audio-url').val(opts.audio_url || '');
                break;
        }

        $('#rsyi-q-msg').text('');
    }

    // ── Load questions list ───────────────────────────────────────────────────
    var typeLabels = <?php echo wp_json_encode( $q_type_labels ); ?>;

    function loadQuestions() {
        $.post( ajaxUrl, { action: 'rsyi_get_questions', exam_id: examId, _nonce: nonce }, function(res) {
            if ( ! res.success ) { $('#rsyi-questions-list').html('<p style="color:red;">' + res.data.message + '</p>'); return; }
            var rows = res.data;
            if ( ! rows.length ) {
                $('#rsyi-questions-list').html('<p style="color:#888;"><?php echo esc_js( __( 'لا توجد أسئلة بعد.', 'rsyi-sa' ) ); ?></p>');
                return;
            }
            var html = '<table class="wp-list-table widefat fixed striped"><thead><tr>';
            html += '<th style="width:40px; text-align:center;">#</th>';
            html += '<th style="width:80px; text-align:center;"><?php echo esc_js( __( 'النوع', 'rsyi-sa' ) ); ?></th>';
            html += '<th><?php echo esc_js( __( 'السؤال', 'rsyi-sa' ) ); ?></th>';
            html += '<th style="width:60px; text-align:center;"><?php echo esc_js( __( 'الدرجة', 'rsyi-sa' ) ); ?></th>';
            html += '<th style="width:120px;"><?php echo esc_js( __( 'إجراءات', 'rsyi-sa' ) ); ?></th>';
            html += '</tr></thead><tbody>';
            $.each( rows, function(i, q) {
                var typeLbl = typeLabels[q.question_type] || q.question_type;
                html += '<tr id="q-row-' + q.id + '">';
                html += '<td style="text-align:center; font-weight:700;">' + q.question_number + '</td>';
                html += '<td style="text-align:center; font-size:11px; color:#0073aa;">' + typeLbl + '</td>';
                html += '<td>' + $('<div>').text(q.question_text).html().replace(/\n/g,'<br>').substring(0,200) + '</td>';
                html += '<td style="text-align:center;">' + parseFloat(q.marks) + '</td>';
                html += '<td><button class="button button-small rsyi-q-edit" data-q=\'' + JSON.stringify(q).replace(/'/g,"&#39;") + '\'><?php echo esc_js( __( 'تعديل', 'rsyi-sa' ) ); ?></button> ';
                html += '<button class="button button-small rsyi-q-delete" data-id="' + q.id + '" style="color:#a00; border-color:#a00;"><?php echo esc_js( __( 'حذف', 'rsyi-sa' ) ); ?></button></td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
            // Total marks
            var total = rows.reduce(function(s, q){ return s + parseFloat(q.marks); }, 0);
            html += '<p dir="rtl" style="color:#0073aa; font-weight:600; margin-top:8px;"><?php echo esc_js( __( 'مجموع الدرجات:', 'rsyi-sa' ) ); ?> ' + total.toFixed(2) + '</p>';
            $('#rsyi-questions-list').html(html);
        });
    }

    loadQuestions();

    $('#rsyi-q-add-btn').on('click', function(){
        resetForm();
        $('#rsyi-q-form-wrap').slideDown(200);
        $('html, body').animate({scrollTop: $('#rsyi-q-form-wrap').offset().top - 40}, 300);
    });

    $('#rsyi-q-cancel').on('click', function(){ $('#rsyi-q-form-wrap').slideUp(200); });

    $(document).on('click', '.rsyi-q-edit', function(){
        var q = $(this).data('q');
        populateForm(q);
        $('#rsyi-q-form-wrap').slideDown(200);
        $('html, body').animate({scrollTop: $('#rsyi-q-form-wrap').offset().top - 40}, 300);
    });

    $(document).on('click', '.rsyi-q-delete', function(){
        if ( ! confirm('<?php echo esc_js( __( 'حذف هذا السؤال نهائياً؟', 'rsyi-sa' ) ); ?>') ) return;
        var id = $(this).data('id');
        $.post( ajaxUrl, { action: 'rsyi_delete_question', question_id: id, _nonce: nonce }, function(res){
            if ( res.success ) { $('#q-row-' + id).fadeOut(300, function(){ $(this).remove(); }); }
            else { alert(res.data.message); }
        });
    });

    // ── Media uploader ────────────────────────────────────────────────────────
    $('#rsyi-q-select-image').on('click', function(e){
        e.preventDefault();
        if ( mediaFrame ) { mediaFrame.open(); return; }
        mediaFrame = wp.media({ title: '<?php echo esc_js( __( 'اختر صورة السؤال', 'rsyi-sa' ) ); ?>', button: { text: '<?php echo esc_js( __( 'اختر', 'rsyi-sa' ) ); ?>' }, multiple: false, library: { type: 'image' } });
        mediaFrame.on('select', function(){
            var att = mediaFrame.state().get('selection').first().toJSON();
            $('#rsyi-q-image-id').val(att.id);
            $('#rsyi-q-image-thumb').attr('src', att.sizes && att.sizes.medium ? att.sizes.medium.url : att.url);
            $('#rsyi-q-image-preview').show();
        });
        mediaFrame.open();
    });
    $('#rsyi-q-remove-image').on('click', function(){ $('#rsyi-q-image-id').val(''); $('#rsyi-q-image-preview').hide(); });

    // ── Save question ─────────────────────────────────────────────────────────
    $('#rsyi-q-save').on('click', function(){
        var text = $.trim($('#rsyi-q-text').val());
        if ( ! text ) { $('#rsyi-q-msg').css('color','red').text('<?php echo esc_js( __( 'نص السؤال مطلوب.', 'rsyi-sa' ) ); ?>'); return; }

        var oa = getOptionsAndAnswer();

        var data = {
            action:          'rsyi_save_question',
            _nonce:          nonce,
            exam_id:         examId,
            question_id:     $('#rsyi-q-id').val(),
            question_number: $('#rsyi-q-number').val(),
            question_text:   text,
            question_type:   $('#rsyi-q-type').val(),
            options:         oa.options || '',
            correct_answer:  oa.correct_answer || '',
            explanation:     $('#rsyi-q-explanation').val(),
            image_id:        $('#rsyi-q-image-id').val() || 0,
            marks:           $('#rsyi-q-marks').val(),
        };

        var $btn = $(this).prop('disabled', true);
        $.post( ajaxUrl, data, function(res){
            $btn.prop('disabled', false);
            $('#rsyi-q-msg').css('color', res.success ? 'green' : 'red').text(res.data.message);
            if ( res.success ) {
                setTimeout(function(){ $('#rsyi-q-form-wrap').slideUp(200); loadQuestions(); }, 600);
            }
        });
    });

});
</script>

<!-- ── Results tab ── -->
<?php elseif ( $active_tab === 'results' && $selected_exam ) : ?>
<h2 dir="rtl"><?php echo esc_html( $selected_exam->title ); ?> — <?php esc_html_e( 'النتائج', 'rsyi-sa' ); ?></h2>

<?php
$max_score    = (int) ( $selected_exam->max_score ?: 100 );
$passing_score = isset( $selected_exam->passing_score ) && $selected_exam->passing_score !== null
                 ? (int) $selected_exam->passing_score
                 : (int) round( $max_score * 0.5 );
$is_auto_grade   = ! empty( $selected_exam->auto_grade );
$allow_regrade   = ! empty( $selected_exam->allow_regrade );

// Count submissions
$submissions_count = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}rsyi_exam_answers WHERE exam_id = %d",
    $selected_exam_id
) );
?>

<div style="display:flex; gap:10px; margin-bottom:16px; flex-wrap:wrap; align-items:center;" dir="rtl">
    <?php if ( $is_auto_grade ) : ?>
    <button type="button" class="button button-primary" id="rsyi-auto-grade-btn"
            data-exam-id="<?php echo esc_attr( $selected_exam_id ); ?>">
        ⚡ <?php esc_html_e( 'تصحيح تلقائي الآن', 'rsyi-sa' ); ?>
        <?php if ( $submissions_count ) : ?>
        <span style="background:rgba(255,255,255,0.3); padding:0 6px; border-radius:10px; font-size:11px;"><?php echo esc_html( $submissions_count ); ?></span>
        <?php endif; ?>
    </button>
    <?php endif; ?>

    <button type="button" class="button" id="rsyi-auto-grades-btn">
        📊 <?php esc_html_e( 'احسب التقديرات تلقائياً', 'rsyi-sa' ); ?>
    </button>

    <?php if ( current_user_can( 'rsyi_export_exam_results' ) ) : ?>
    <button type="button" class="button" id="rsyi-export-btn" data-exam-id="<?php echo esc_attr( $selected_exam_id ); ?>">
        ⬇ <?php esc_html_e( 'تصدير CSV', 'rsyi-sa' ); ?>
    </button>
    <?php endif; ?>

    <span id="rsyi-auto-grade-msg" style="color:#27ae60; font-weight:600;"></span>
</div>

<div style="background:#f8f9fa; border:1px solid #dee2e6; border-radius:4px; padding:10px 14px; margin-bottom:16px; font-size:12px;" dir="rtl">
    <?php if ( $is_auto_grade ) : ?>
    <span style="color:#27ae60;">✓ <?php esc_html_e( 'التصحيح التلقائي مفعّل', 'rsyi-sa' ); ?></span>
    <?php else : ?>
    <span style="color:#e67e22;">✏ <?php esc_html_e( 'التصحيح اليدوي', 'rsyi-sa' ); ?></span>
    <?php endif; ?>
    &nbsp;|&nbsp;
    <?php if ( $allow_regrade ) : ?>
    <span style="color:#0073aa;">🔄 <?php esc_html_e( 'إعادة التصحيح مسموحة', 'rsyi-sa' ); ?></span>
    <?php else : ?>
    <span style="color:#999;">🔒 <?php esc_html_e( 'إعادة التصحيح مقيّدة', 'rsyi-sa' ); ?></span>
    <?php endif; ?>
    &nbsp;|&nbsp;
    <?php if ( ! empty( $selected_exam->show_results ) ) : ?>
    <span style="color:#27ae60;">👁 <?php esc_html_e( 'النتيجة تظهر للطالب', 'rsyi-sa' ); ?></span>
    <?php else : ?>
    <span style="color:#999;">🙈 <?php esc_html_e( 'النتيجة مخفية عن الطالب', 'rsyi-sa' ); ?></span>
    <?php endif; ?>
</div>

<?php if ( empty( $exam_students ) ) : ?>
<div class="notice notice-warning" dir="rtl"><p><?php esc_html_e( 'لا يوجد طلاب نشطون في فوج هذا الامتحان.', 'rsyi-sa' ); ?></p></div>
<?php else : ?>
<form id="rsyi-results-form" dir="rtl">
    <?php wp_nonce_field( 'rsyi_sa_admin', '_nonce' ); ?>
    <input type="hidden" name="action" value="rsyi_save_exam_results">
    <input type="hidden" name="exam_id" value="<?php echo esc_attr( $selected_exam_id ); ?>">

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width:34px;">#</th>
                <th><?php esc_html_e( 'الطالب', 'rsyi-sa' ); ?></th>
                <th style="width:40px; text-align:center;"><?php esc_html_e( 'سلّم؟', 'rsyi-sa' ); ?></th>
                <th style="width:100px; text-align:center;"><?php printf( esc_html__( 'الدرجة / %d', 'rsyi-sa' ), $max_score ); ?></th>
                <th style="width:65px; text-align:center;"><?php esc_html_e( 'التقدير', 'rsyi-sa' ); ?></th>
                <th style="width:90px; text-align:center;"><?php esc_html_e( 'النتيجة', 'rsyi-sa' ); ?></th>
                <th><?php esc_html_e( 'ملاحظات', 'rsyi-sa' ); ?></th>
                <?php if ( $allow_regrade ) : ?>
                <th style="width:80px; text-align:center;"><?php esc_html_e( 'إعادة تصحيح', 'rsyi-sa' ); ?></th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ( $exam_students as $i => $st ) :
            $res        = $exam_results_map[ (int) $st->profile_id ] ?? null;
            $score      = $res ? $res->score : '';
            $grade      = $res ? $res->grade : '';
            $notes      = $res ? $res->notes : '';
            $submitted  = $res && $res->submitted_at;
            $is_passing = ( $res && $score !== '' && $score !== null ) ? ( (int) $score >= $passing_score ? 1 : 0 ) : null;
            $regraded   = $res && $res->regraded_by;
        ?>
        <tr class="rsyi-result-row"
            data-max="<?php echo esc_attr( $max_score ); ?>"
            data-passing="<?php echo esc_attr( $passing_score ); ?>"
            data-student="<?php echo esc_attr( $st->profile_id ); ?>"
            data-exam="<?php echo esc_attr( $selected_exam_id ); ?>">
            <td><?php echo $i + 1; ?></td>
            <td>
                <strong><?php echo esc_html( $st->arabic_full_name ); ?></strong>
                <input type="hidden" name="student_ids[]" value="<?php echo esc_attr( $st->profile_id ); ?>">
                <?php if ( $regraded ) : ?>
                <br><span style="font-size:10px; color:#e67e22;">🔄 أُعيد تصحيحه</span>
                <?php elseif ( $res && $res->auto_graded ) : ?>
                <br><span style="font-size:10px; color:#0073aa;">⚡ تلقائي</span>
                <?php endif; ?>
            </td>
            <td style="text-align:center;">
                <?php if ( $submitted ) : ?>
                <span title="<?php echo esc_attr( date_i18n( 'j M H:i', strtotime( $res->submitted_at ) ) ); ?>" style="color:#27ae60; font-size:16px;">✓</span>
                <?php else : ?>
                <span style="color:#bbb; font-size:14px;">—</span>
                <?php endif; ?>
            </td>
            <td>
                <input type="number" name="score_<?php echo esc_attr( $st->profile_id ); ?>"
                       min="0" max="<?php echo esc_attr( $max_score ); ?>"
                       value="<?php echo esc_attr( $score ?? '' ); ?>"
                       class="rsyi-score-input"
                       <?php echo ( ! $allow_regrade && $res ) ? 'readonly style="width:70px; text-align:center; background:#f8f9fa;"' : 'style="width:70px; text-align:center;"'; ?>>
            </td>
            <td>
                <input type="text" name="grade_<?php echo esc_attr( $st->profile_id ); ?>"
                       value="<?php echo esc_attr( $grade ); ?>"
                       class="rsyi-grade-input"
                       style="width:50px; text-align:center;" maxlength="5"
                       <?php echo ( ! $allow_regrade && $res ) ? 'readonly' : ''; ?>>
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
                       value="<?php echo esc_attr( $notes ); ?>" style="width:100%;"
                       <?php echo ( ! $allow_regrade && $res ) ? 'readonly' : ''; ?>>
            </td>
            <?php if ( $allow_regrade ) : ?>
            <td style="text-align:center;">
                <button type="button" class="button button-small rsyi-regrade-btn"
                        data-student="<?php echo esc_attr( $st->profile_id ); ?>">
                    🔄
                </button>
            </td>
            <?php endif; ?>
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

    // ── DB Migration (manual fix button) ──────────────────────────────────────
    $('#rsyi-db-migrate-btn').on('click', function(){
        var $btn = $(this).prop('disabled', true).text('⏳ ...');
        var $msg = $('#rsyi-db-migrate-msg');
        $.post(rsyiSA.ajaxUrl, {
            action: 'rsyi_run_db_migration',
            _nonce: rsyiSA.nonce
        }, function(res){
            $btn.prop('disabled', false).html('🔧 <?php echo esc_js( __( 'إصلاح قاعدة البيانات', 'rsyi-sa' ) ); ?>');
            $msg.css('color', res.success ? 'green' : 'red').text(res.data.message);
        }).fail(function(){
            $btn.prop('disabled', false).html('🔧 <?php echo esc_js( __( 'إصلاح قاعدة البيانات', 'rsyi-sa' ) ); ?>');
            $msg.css('color','red').text('<?php echo esc_js( __( 'خطأ في الاتصال', 'rsyi-sa' ) ); ?>');
        });
    });

    // ── Create exam ────────────────────────────────────────────────────────────
    $('#rsyi-create-exam-form').on('submit', function(e){
        e.preventDefault();
        var $btn = $(this).find('[type=submit]').prop('disabled', true);
        $.post(rsyiSA.ajaxUrl, $(this).serialize(), function(res){
            $btn.prop('disabled', false);
            $('#rsyi-exam-create-msg').css('color', res.success ? 'green' : 'red').text(res.data.message);
            if(res.success) setTimeout(function(){ location.href = '?page=rsyi-exams&tab=list'; }, 1000);
        });
    });

    // ── Update exam ────────────────────────────────────────────────────────────
    $('#rsyi-update-exam-form').on('submit', function(e){
        e.preventDefault();
        var $btn = $(this).find('[type=submit]').prop('disabled', true);
        $.post(rsyiSA.ajaxUrl, $(this).serialize(), function(res){
            $btn.prop('disabled', false);
            $('#rsyi-exam-update-msg').css('color', res.success ? 'green' : 'red').text(res.data.message);
        });
    });

    // ── Delete exam ────────────────────────────────────────────────────────────
    $(document).on('click', '.rsyi-delete-exam', function(){
        var id = $(this).data('exam-id');
        if ( ! confirm('<?php echo esc_js( __( 'سيتم حذف الامتحان وجميع نتائجه وأسئلته وإجاباته. هل أنت متأكد؟', 'rsyi-sa' ) ); ?>') ) return;
        var $row = $('#exam-row-' + id);
        $.post(rsyiSA.ajaxUrl, { action: 'rsyi_delete_exam', exam_id: id, _nonce: rsyiSA.nonce }, function(res){
            if(res.success){ $row.fadeOut(400, function(){ $row.remove(); }); }
            else { alert(res.data.message); }
        });
    });

    // ── Auto-calculate grades (client-side) ────────────────────────────────────
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

    // Update badge on score change
    $(document).on('input', '.rsyi-score-input', function(){
        var $row    = $(this).closest('.rsyi-result-row');
        var max     = parseInt($row.data('max')) || 100;
        var passing = parseInt($row.data('passing')) || Math.round(max * 0.5);
        var s       = parseInt($(this).val());
        var $badge  = $row.find('.rsyi-pass-badge');
        if ( isNaN(s) ) { $badge.css({'background':'#eee','color':'#999'}).text('—'); return; }
        if ( s >= passing ) { $badge.css({'background':'#d4edda','color':'#155724'}).text('<?php echo esc_js( __( 'ناجح', 'rsyi-sa' ) ); ?>'); }
        else { $badge.css({'background':'#f8d7da','color':'#721c24'}).text('<?php echo esc_js( __( 'راسب', 'rsyi-sa' ) ); ?>'); }
    });

    // ── Auto-grade via server ──────────────────────────────────────────────────
    $('#rsyi-auto-grade-btn').on('click', function(){
        var examId = $(this).data('exam-id');
        if ( ! confirm('<?php echo esc_js( __( 'سيتم تصحيح إجابات جميع الطلاب تلقائياً. هل تريد المتابعة؟', 'rsyi-sa' ) ); ?>') ) return;
        var $btn = $(this).prop('disabled', true).text('...');
        $.post(rsyiSA.ajaxUrl, { action: 'rsyi_auto_grade_exam', exam_id: examId, _nonce: rsyiSA.nonce }, function(res){
            $btn.prop('disabled', false).html('⚡ <?php echo esc_js( __( 'تصحيح تلقائي الآن', 'rsyi-sa' ) ); ?>');
            if(res.success){ $('#rsyi-auto-grade-msg').text(res.data.message); setTimeout(function(){ location.reload(); }, 1200); }
            else { alert(res.data.message); }
        });
    });

    // ── Re-grade individual student ────────────────────────────────────────────
    $(document).on('click', '.rsyi-regrade-btn', function(){
        var $row      = $(this).closest('.rsyi-result-row');
        var examId    = $row.data('exam');
        var studentId = $row.data('student');
        var score     = $row.find('.rsyi-score-input').val();
        var notes     = $row.find('input[name^="notes_"]').val();
        $.post(rsyiSA.ajaxUrl, {
            action: 'rsyi_regrade_result', _nonce: rsyiSA.nonce,
            exam_id: examId, student_id: studentId, score: score, notes: notes
        }, function(res){
            if(res.success){ $('#rsyi-results-msg').css('color','green').text(res.data.message); }
            else { alert(res.data.message); }
        });
    });

    // ── Save results (manual entry) ────────────────────────────────────────────
    $('#rsyi-results-form').on('submit', function(e){
        e.preventDefault();
        var $btn = $('#rsyi-results-save').prop('disabled', true);
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
    var examIdStats = <?php echo (int) $selected_exam_id; ?>;
    var gradeColors = {'A+':'#155724','A':'#1e7e34','B':'#0c5460','C':'#856404','D':'#856404','F':'#721c24'};
    var gradeBg     = {'A+':'#d4edda','A':'#d4edda','B':'#d1ecf1','C':'#fff3cd','D':'#ffeeba','F':'#f8d7da'};

    $.post(rsyiSA.ajaxUrl, { action: 'rsyi_get_exam_stats', exam_id: examIdStats, _nonce: rsyiSA.nonce }, function(res){
        if(!res.success){ $('#rsyi-stats-container').html('<p style="color:red;">' + res.data.message + '</p>'); return; }
        var d = res.data;
        var html = '<div style="display:grid; grid-template-columns:repeat(5,1fr); gap:14px; margin-bottom:24px;">';
        html += statsCard(d.count, '<?php echo esc_js( __( 'عدد الطلاب', 'rsyi-sa' ) ); ?>', '#0073aa');
        html += statsCard(d.avg + '%', '<?php echo esc_js( __( 'المتوسط', 'rsyi-sa' ) ); ?>', '#2196F3');
        html += statsCard(d.max_val, '<?php echo esc_js( __( 'أعلى درجة', 'rsyi-sa' ) ); ?>', '#27ae60');
        html += statsCard(d.min_val, '<?php echo esc_js( __( 'أدنى درجة', 'rsyi-sa' ) ); ?>', '#e74c3c');
        html += statsCard(d.pass_pct + '%', '<?php echo esc_js( __( 'نسبة النجاح', 'rsyi-sa' ) ); ?>', d.pass_pct >= 50 ? '#27ae60' : '#e74c3c');
        html += '</div>';

        html += '<div style="background:#fff; border:1px solid #ccd0d4; border-radius:4px; padding:16px; margin-bottom:24px;">';
        html += '<h3 style="margin-top:0;"><?php echo esc_js( __( 'توزيع التقديرات', 'rsyi-sa' ) ); ?></h3>';
        html += '<table style="width:100%; border-collapse:collapse;"><tr style="background:#f6f7f7;"><th style="padding:8px; text-align:center;"><?php echo esc_js( __( 'التقدير', 'rsyi-sa' ) ); ?></th><th style="padding:8px; text-align:center;"><?php echo esc_js( __( 'العدد', 'rsyi-sa' ) ); ?></th><th style="padding:8px; text-align:center;"><?php echo esc_js( __( 'النسبة', 'rsyi-sa' ) ); ?></th></tr>';
        $.each(d.dist, function(g, n){
            var pct = d.count > 0 ? Math.round(n/d.count*100) : 0;
            html += '<tr><td style="padding:8px; text-align:center;"><span style="background:'+(gradeBg[g]||'#eee')+'; color:'+(gradeColors[g]||'#333')+'; padding:2px 10px; border-radius:10px; font-weight:700;">'+g+'</span></td>';
            html += '<td style="padding:8px; text-align:center; font-weight:700;">'+n+'</td>';
            html += '<td style="padding:8px; text-align:center; color:#888;">'+pct+'%</td></tr>';
        });
        html += '</table></div>';

        html += '<div style="background:#fff; border:1px solid #ccd0d4; border-radius:4px; padding:16px;">';
        html += '<h3 style="margin-top:0;"><?php echo esc_js( __( 'ترتيب الطلاب', 'rsyi-sa' ) ); ?></h3>';
        html += '<table class="wp-list-table widefat fixed striped"><thead><tr>';
        html += '<th style="width:40px; text-align:center;">#</th><th><?php echo esc_js( __( 'الطالب', 'rsyi-sa' ) ); ?></th>';
        html += '<th style="text-align:center;"><?php echo esc_js( __( 'الدرجة', 'rsyi-sa' ) ); ?></th>';
        html += '<th style="text-align:center;"><?php echo esc_js( __( 'النسبة%', 'rsyi-sa' ) ); ?></th>';
        html += '<th style="text-align:center;"><?php echo esc_js( __( 'التقدير', 'rsyi-sa' ) ); ?></th>';
        html += '</tr></thead><tbody>';
        $.each(d.ranked, function(i, r){
            html += '<tr><td style="text-align:center;">'+(i+1)+'</td><td>'+r.name+'</td>';
            html += '<td style="text-align:center; font-weight:700;">'+r.score+'</td>';
            html += '<td style="text-align:center;">'+r.pct+'%</td>';
            html += '<td style="text-align:center;"><span style="background:'+(gradeBg[r.grade]||'#eee')+'; color:'+(gradeColors[r.grade]||'#333')+'; padding:2px 10px; border-radius:10px; font-weight:700;">'+r.grade+'</span></td></tr>';
        });
        html += '</tbody></table></div>';
        $('#rsyi-stats-container').html(html);
    });

    function statsCard(val, label, color){
        return '<div style="background:#fff; border:1px solid #dee2e6; border-top:3px solid '+color+'; border-radius:6px; padding:16px; text-align:center;">'+
               '<div style="font-size:26px; font-weight:700; color:'+color+';">'+val+'</div>'+
               '<div style="font-size:12px; color:#888; margin-top:4px;">'+label+'</div></div>';
    }
    <?php endif; ?>
});
</script>
