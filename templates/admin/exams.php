<?php
/**
 * Admin Template: Exams Management
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
     ORDER BY e.exam_date DESC, e.created_at DESC LIMIT 100"
);

// Selected exam for entering results
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
?>
<h1 class="wp-heading-inline"><?php esc_html_e( 'الامتحانات', 'rsyi-sa' ); ?></h1>
<hr class="wp-header-end">

<!-- Tabs -->
<nav class="nav-tab-wrapper" style="margin-bottom:20px;" dir="rtl">
    <a href="<?php echo esc_url( add_query_arg( [ 'page' => 'rsyi-exams', 'tab' => 'list' ], admin_url( 'admin.php' ) ) ); ?>"
       class="nav-tab <?php echo $active_tab === 'list' ? 'nav-tab-active' : ''; ?>">
        <?php esc_html_e( 'قائمة الامتحانات', 'rsyi-sa' ); ?>
    </a>
    <a href="<?php echo esc_url( add_query_arg( [ 'page' => 'rsyi-exams', 'tab' => 'add' ], admin_url( 'admin.php' ) ) ); ?>"
       class="nav-tab <?php echo $active_tab === 'add' ? 'nav-tab-active' : ''; ?>">
        <?php esc_html_e( 'إنشاء امتحان', 'rsyi-sa' ); ?>
    </a>
    <?php if ( $selected_exam ) : ?>
    <a href="<?php echo esc_url( add_query_arg( [ 'page' => 'rsyi-exams', 'tab' => 'results', 'exam_id' => $selected_exam_id ], admin_url( 'admin.php' ) ) ); ?>"
       class="nav-tab <?php echo $active_tab === 'results' ? 'nav-tab-active' : ''; ?>">
        <?php esc_html_e( 'إدخال النتائج', 'rsyi-sa' ); ?>
    </a>
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
            <th><?php esc_html_e( 'التاريخ', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'الدرجة القصوى', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'إجراءات', 'rsyi-sa' ); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ( $exams as $e ) : ?>
    <tr>
        <td><?php echo esc_html( $e->title ); ?></td>
        <td><?php echo $e->subject ? esc_html( $e->subject ) : '—'; ?></td>
        <td><?php echo $e->cohort_name ? esc_html( $e->cohort_name ) : '—'; ?></td>
        <td><?php echo $e->exam_date ? esc_html( date_i18n( 'j M Y', strtotime( $e->exam_date ) ) ) : '—'; ?></td>
        <td><?php echo esc_html( $e->max_score ); ?></td>
        <td>
            <a href="<?php echo esc_url( add_query_arg( [ 'page' => 'rsyi-exams', 'tab' => 'results', 'exam_id' => $e->id ], admin_url( 'admin.php' ) ) ); ?>"
               class="button button-small">
                <?php esc_html_e( 'إدخال النتائج', 'rsyi-sa' ); ?>
            </a>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<!-- ── Add exam tab ── -->
<?php elseif ( $active_tab === 'add' ) : ?>
<div style="max-width:600px; background:#fff; border:1px solid #ccd0d4; border-radius:4px; padding:20px;" dir="rtl">
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
                <td><input type="number" name="duration_min" min="1" style="width:80px;"></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'الدرجة القصوى', 'rsyi-sa' ); ?></th>
                <td><input type="number" name="max_score" value="100" min="1" style="width:80px;" required></td>
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

<!-- ── Results tab ── -->
<?php elseif ( $active_tab === 'results' && $selected_exam ) : ?>
<h2 dir="rtl"><?php echo esc_html( $selected_exam->title ); ?> — <?php esc_html_e( 'إدخال النتائج', 'rsyi-sa' ); ?></h2>

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
                <th style="width:40px;">#</th>
                <th><?php esc_html_e( 'الطالب', 'rsyi-sa' ); ?></th>
                <th style="width:100px; text-align:center;"><?php printf( esc_html__( 'الدرجة / %d', 'rsyi-sa' ), $selected_exam->max_score ); ?></th>
                <th style="width:80px; text-align:center;"><?php esc_html_e( 'التقدير', 'rsyi-sa' ); ?></th>
                <th><?php esc_html_e( 'ملاحظات', 'rsyi-sa' ); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ( $exam_students as $i => $st ) :
            $res   = $exam_results_map[ (int) $st->profile_id ] ?? null;
            $score = $res ? $res->score : '';
            $grade = $res ? $res->grade : '';
            $notes = $res ? $res->notes : '';
        ?>
        <tr>
            <td><?php echo $i + 1; ?></td>
            <td>
                <strong><?php echo esc_html( $st->arabic_full_name ); ?></strong>
                <input type="hidden" name="student_ids[]" value="<?php echo esc_attr( $st->profile_id ); ?>">
            </td>
            <td>
                <input type="number" name="score_<?php echo esc_attr( $st->profile_id ); ?>"
                       min="0" max="<?php echo esc_attr( $selected_exam->max_score ); ?>"
                       value="<?php echo esc_attr( $score ); ?>"
                       style="width:70px; text-align:center;">
            </td>
            <td>
                <input type="text" name="grade_<?php echo esc_attr( $st->profile_id ); ?>"
                       value="<?php echo esc_attr( $grade ); ?>"
                       style="width:60px; text-align:center;" maxlength="5"
                       placeholder="A/B/C…">
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
<?php endif; ?>

<script>
jQuery(function($){
    $('#rsyi-create-exam-form').on('submit', function(e){
        e.preventDefault();
        $.post(rsyiSA.ajaxUrl, $(this).serialize(), function(res){
            $('#rsyi-exam-create-msg').css('color', res.success ? 'green' : 'red').text(res.data.message);
            if(res.success) setTimeout(function(){ location.href = '?page=rsyi-exams&tab=list'; }, 1000);
        });
    });
    $('#rsyi-results-form').on('submit', function(e){
        e.preventDefault();
        var $btn = $('#rsyi-results-save');
        $btn.prop('disabled', true);
        $.post(rsyiSA.ajaxUrl, $(this).serialize(), function(res){
            $btn.prop('disabled', false);
            $('#rsyi-results-msg').css('color', res.success ? 'green' : 'red').text(res.data.message);
        });
    });
});
</script>
