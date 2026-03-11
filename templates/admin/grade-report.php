<?php
/**
 * Admin Template: Grade Report
 * Matrix view: students (rows) × exams (columns)
 * Requires capability: rsyi_view_exam_stats
 *
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

if ( ! current_user_can( 'rsyi_view_exam_stats' ) ) {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'ليس لديك صلاحية الوصول لهذه الصفحة.', 'rsyi-sa' ) . '</p></div>';
    return;
}

global $wpdb;

$cohorts  = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}rsyi_cohorts WHERE is_active = 1 ORDER BY name ASC" );
$subjects = $wpdb->get_col( "SELECT DISTINCT subject FROM {$wpdb->prefix}rsyi_exams WHERE subject IS NOT NULL ORDER BY subject ASC" );

// Filters
$filter_cohort  = absint( $_GET['cohort_id'] ?? 0 );
$filter_subject = sanitize_text_field( wp_unslash( $_GET['subject'] ?? '' ) );
$filter_from    = sanitize_text_field( wp_unslash( $_GET['date_from'] ?? '' ) );
$filter_to      = sanitize_text_field( wp_unslash( $_GET['date_to']   ?? '' ) );

$data_loaded = ( $filter_cohort > 0 );

$students   = [];
$exams_list = [];
$results_grid = []; // [student_id][exam_id] = { score, grade, is_passing }

if ( $data_loaded ) {
    // Get active students in cohort
    $students = $wpdb->get_results( $wpdb->prepare(
        "SELECT id, arabic_full_name, english_full_name FROM {$wpdb->prefix}rsyi_student_profiles
         WHERE cohort_id = %d AND status = 'active' ORDER BY arabic_full_name ASC",
        $filter_cohort
    ) );

    // Build exam query
    $exam_where = [ $wpdb->prepare( "cohort_id = %d", $filter_cohort ) ];
    if ( $filter_subject ) {
        $exam_where[] = $wpdb->prepare( "subject = %s", $filter_subject );
    }
    if ( $filter_from ) {
        $exam_where[] = $wpdb->prepare( "exam_date >= %s", $filter_from );
    }
    if ( $filter_to ) {
        $exam_where[] = $wpdb->prepare( "exam_date <= %s", $filter_to );
    }
    $exam_sql   = "SELECT * FROM {$wpdb->prefix}rsyi_exams WHERE " . implode( ' AND ', $exam_where ) . " ORDER BY exam_date ASC, title ASC LIMIT 50";
    $exams_list = $wpdb->get_results( $exam_sql );

    if ( ! empty( $exams_list ) && ! empty( $students ) ) {
        $exam_ids  = implode( ',', array_map( 'absint', wp_list_pluck( $exams_list, 'id' ) ) );
        $stud_ids  = implode( ',', array_map( 'absint', wp_list_pluck( $students, 'id' ) ) );
        $all_res   = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}rsyi_exam_results WHERE exam_id IN ({$exam_ids}) AND student_id IN ({$stud_ids})"
        );
        foreach ( $all_res as $r ) {
            $results_grid[ (int) $r->student_id ][ (int) $r->exam_id ] = $r;
        }
    }
}

function rsyi_cell_color( $r, $exam ): string {
    if ( ! $r ) return '#f8f9fa';
    $max     = (int) $exam->max_score ?: 100;
    $passing = isset( $exam->passing_score ) && $exam->passing_score !== null
               ? (int) $exam->passing_score : (int) round( $max * 0.5 );
    $pct = $max > 0 ? (int) $r->score / $max * 100 : 0;
    if ( $pct >= 80 ) return '#d4edda';
    if ( $pct >= $passing / $max * 100 ) return '#fff3cd';
    return '#f8d7da';
}
?>
<h1 class="wp-heading-inline"><?php esc_html_e( 'رصد الدرجات', 'rsyi-sa' ); ?></h1>
<hr class="wp-header-end">

<!-- Filters -->
<div style="background:#fff; border:1px solid #ccd0d4; border-radius:4px; padding:16px 20px; margin:16px 0;" dir="rtl">
    <form method="get" style="display:flex; flex-wrap:wrap; align-items:flex-end; gap:12px;">
        <input type="hidden" name="page" value="rsyi-grade-report">

        <div>
            <label style="display:block; font-weight:600; margin-bottom:4px;"><?php esc_html_e( 'الفوج', 'rsyi-sa' ); ?></label>
            <select name="cohort_id" style="min-width:180px;">
                <option value=""><?php esc_html_e( '— اختر الفوج —', 'rsyi-sa' ); ?></option>
                <?php foreach ( $cohorts as $c ) : ?>
                <option value="<?php echo esc_attr( $c->id ); ?>" <?php selected( $filter_cohort, $c->id ); ?>>
                    <?php echo esc_html( $c->name ); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label style="display:block; font-weight:600; margin-bottom:4px;"><?php esc_html_e( 'المادة', 'rsyi-sa' ); ?></label>
            <select name="subject" style="min-width:160px;">
                <option value=""><?php esc_html_e( '— جميع المواد —', 'rsyi-sa' ); ?></option>
                <?php foreach ( $subjects as $subj ) : ?>
                <option value="<?php echo esc_attr( $subj ); ?>" <?php selected( $filter_subject, $subj ); ?>>
                    <?php echo esc_html( $subj ); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label style="display:block; font-weight:600; margin-bottom:4px;"><?php esc_html_e( 'من تاريخ', 'rsyi-sa' ); ?></label>
            <input type="date" name="date_from" value="<?php echo esc_attr( $filter_from ); ?>" style="min-width:140px;">
        </div>

        <div>
            <label style="display:block; font-weight:600; margin-bottom:4px;"><?php esc_html_e( 'إلى تاريخ', 'rsyi-sa' ); ?></label>
            <input type="date" name="date_to" value="<?php echo esc_attr( $filter_to ); ?>" style="min-width:140px;">
        </div>

        <div>
            <button type="submit" class="button button-primary"><?php esc_html_e( 'عرض', 'rsyi-sa' ); ?></button>
        </div>

        <?php if ( $data_loaded && ! empty( $students ) && ! empty( $exams_list ) ) : ?>
        <div style="margin-right:auto;">
            <button type="button" class="button" id="rsyi-gr-csv">⬇ <?php esc_html_e( 'تصدير CSV', 'rsyi-sa' ); ?></button>
            <button type="button" class="button" onclick="window.print()">🖨 <?php esc_html_e( 'طباعة', 'rsyi-sa' ); ?></button>
        </div>
        <?php endif; ?>
    </form>
</div>

<?php if ( ! $data_loaded ) : ?>
<div style="background:#fff; border:1px solid #ccd0d4; border-radius:4px; padding:40px; text-align:center; color:#888;" dir="rtl">
    <p style="font-size:16px;"><?php esc_html_e( 'اختر فوجاً لعرض رصد الدرجات.', 'rsyi-sa' ); ?></p>
</div>
<?php elseif ( empty( $students ) ) : ?>
<div class="notice notice-warning" dir="rtl"><p><?php esc_html_e( 'لا يوجد طلاب نشطون في هذا الفوج.', 'rsyi-sa' ); ?></p></div>
<?php elseif ( empty( $exams_list ) ) : ?>
<div class="notice notice-warning" dir="rtl"><p><?php esc_html_e( 'لا توجد امتحانات مطابقة للفلتر المحدد.', 'rsyi-sa' ); ?></p></div>
<?php else : ?>

<!-- Legend -->
<div style="display:flex; gap:12px; margin-bottom:12px;" dir="rtl">
    <span style="font-size:12px; color:#555;"><?php esc_html_e( 'مفتاح الألوان:', 'rsyi-sa' ); ?></span>
    <span style="background:#d4edda; padding:2px 10px; border-radius:3px; font-size:12px;"><?php esc_html_e( 'ممتاز ≥ 80%', 'rsyi-sa' ); ?></span>
    <span style="background:#fff3cd; padding:2px 10px; border-radius:3px; font-size:12px;"><?php esc_html_e( 'ناجح', 'rsyi-sa' ); ?></span>
    <span style="background:#f8d7da; padding:2px 10px; border-radius:3px; font-size:12px;"><?php esc_html_e( 'راسب', 'rsyi-sa' ); ?></span>
</div>

<!-- Grade Matrix -->
<div style="overflow-x:auto;">
<table class="wp-list-table widefat fixed" id="rsyi-grade-matrix" dir="rtl"
       style="border-collapse:collapse; min-width:100%;">
    <thead>
        <tr style="background:#f6f7f7;">
            <th style="padding:10px 12px; text-align:right; min-width:160px; border:1px solid #ddd;">
                <?php esc_html_e( 'الطالب', 'rsyi-sa' ); ?>
            </th>
            <?php foreach ( $exams_list as $ex ) : ?>
            <th style="padding:8px 10px; text-align:center; border:1px solid #ddd; min-width:90px; font-size:12px;">
                <?php echo esc_html( $ex->title ); ?>
                <?php if ( $ex->exam_date ) : ?>
                <br><span style="font-weight:400; color:#888; font-size:11px;"><?php echo esc_html( date_i18n( 'j M', strtotime( $ex->exam_date ) ) ); ?></span>
                <?php endif; ?>
                <br><span style="font-weight:400; color:#888; font-size:11px;"><?php echo esc_html( '/ ' . $ex->max_score ); ?></span>
            </th>
            <?php endforeach; ?>
            <th style="padding:8px 10px; text-align:center; border:1px solid #ddd; min-width:80px; background:#e8f4fd;">
                <?php esc_html_e( 'المعدل %', 'rsyi-sa' ); ?>
            </th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ( $students as $st ) :
        $row_scores = [];
        $row_maxes  = [];
    ?>
    <tr>
        <td style="padding:8px 12px; border:1px solid #ddd; font-weight:600;">
            <?php echo esc_html( $st->arabic_full_name ); ?>
        </td>
        <?php foreach ( $exams_list as $ex ) :
            $r   = $results_grid[ (int) $st->id ][ (int) $ex->id ] ?? null;
            $max = (int) $ex->max_score ?: 100;
            $bg  = rsyi_cell_color( $r, $ex );
            if ( $r ) { $row_scores[] = (int) $r->score; $row_maxes[] = $max; }
        ?>
        <td style="padding:8px 10px; text-align:center; border:1px solid #ddd; background:<?php echo esc_attr( $bg ); ?>;">
            <?php if ( $r ) : ?>
            <strong><?php echo esc_html( $r->score ); ?></strong>
            <?php if ( $r->grade ) : ?>
            <span style="font-size:11px; color:#666; margin-right:3px;">(<?php echo esc_html( $r->grade ); ?>)</span>
            <?php endif; ?>
            <?php else : ?>
            <span style="color:#ccc;">—</span>
            <?php endif; ?>
        </td>
        <?php endforeach; ?>
        <?php
        $total_pct = '—';
        if ( ! empty( $row_scores ) ) {
            $sum_score = array_sum( $row_scores );
            $sum_max   = array_sum( $row_maxes );
            $total_pct = $sum_max > 0 ? round( $sum_score / $sum_max * 100, 1 ) . '%' : '—';
        }
        $avg_color = is_numeric( rtrim( $total_pct, '%' ) ) ? ( (float) rtrim( $total_pct, '%' ) >= 60 ? '#155724' : '#721c24' ) : '#888';
        ?>
        <td style="padding:8px 10px; text-align:center; border:1px solid #ddd; background:#e8f4fd; font-weight:700; color:<?php echo esc_attr( $avg_color ); ?>;">
            <?php echo esc_html( $total_pct ); ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    <!-- Column averages -->
    <tfoot>
        <tr style="background:#f6f7f7; font-weight:700;">
            <td style="padding:8px 12px; border:1px solid #ddd;"><?php esc_html_e( 'متوسط الامتحان', 'rsyi-sa' ); ?></td>
            <?php foreach ( $exams_list as $ex ) :
                $all_scores = [];
                foreach ( $students as $st2 ) {
                    $r2 = $results_grid[ (int) $st2->id ][ (int) $ex->id ] ?? null;
                    if ( $r2 ) $all_scores[] = (int) $r2->score;
                }
                $max = (int) $ex->max_score ?: 100;
                $avg = ! empty( $all_scores ) ? round( array_sum( $all_scores ) / count( $all_scores ), 1 ) : null;
                $avg_pct = $avg !== null ? round( $avg / $max * 100, 1 ) : null;
            ?>
            <td style="padding:8px 10px; text-align:center; border:1px solid #ddd;">
                <?php if ( $avg !== null ) : ?>
                <?php echo esc_html( $avg ); ?> <span style="color:#888; font-size:11px;">(<?php echo esc_html( $avg_pct ); ?>%)</span>
                <?php else : ?>
                <span style="color:#ccc;">—</span>
                <?php endif; ?>
            </td>
            <?php endforeach; ?>
            <td style="border:1px solid #ddd; background:#e8f4fd;"></td>
        </tr>
    </tfoot>
</table>
</div>
<?php endif; ?>

<style>
@media print {
    #wpadminbar, #adminmenumain, #wpfooter, .wp-header-end, form, .notice { display:none !important; }
    #wpbody-content { margin:0 !important; }
    table { font-size:11px !important; }
}
</style>

<script>
jQuery(function($){
    $('#rsyi-gr-csv').on('click', function(){
        var rows = [];
        // Header row
        var headers = [];
        $('#rsyi-grade-matrix thead tr th').each(function(){ headers.push($(this).text().trim().replace(/\s+/g,' ')); });
        rows.push(headers);
        // Data rows
        $('#rsyi-grade-matrix tbody tr').each(function(){
            var row = [];
            $(this).find('td').each(function(){ row.push($(this).text().trim().replace(/\s+/g,' ')); });
            rows.push(row);
        });
        // Footer row
        var footer = [];
        $('#rsyi-grade-matrix tfoot tr td').each(function(){ footer.push($(this).text().trim().replace(/\s+/g,' ')); });
        rows.push(footer);

        var csv = rows.map(function(r){ return r.map(function(v){ return '"' + String(v).replace(/"/g,'""') + '"'; }).join(','); }).join('\n');
        var blob = new Blob(["\uFEFF" + csv], {type:'text/csv;charset=utf-8;'});
        var url  = URL.createObjectURL(blob);
        var a    = document.createElement('a');
        a.href = url; a.download = 'grade-report.csv'; a.click();
        URL.revokeObjectURL(url);
    });
});
</script>
