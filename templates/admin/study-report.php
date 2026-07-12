<?php
/**
 * Admin – Daily Study Follow-up Report
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

global $wpdb;

$filter_date   = sanitize_text_field( $_GET['report_date'] ?? date( 'Y-m-d' ) );
$filter_cohort = absint( $_GET['cohort_id'] ?? 0 );

$cohorts = $wpdb->get_results(
    "SELECT id, name FROM {$wpdb->prefix}rsyi_cohorts WHERE is_active = 1 ORDER BY name ASC"
);
$courses = $wpdb->get_results(
    "SELECT id, name_ar, name_en FROM {$wpdb->prefix}rsyi_courses WHERE is_active = 1 ORDER BY name_ar ASC"
);

// Build query
$where  = "ca.report_date = %s";
$params = [ $filter_date ];
if ( $filter_cohort ) {
    $where   .= " AND sp.cohort_id = %d";
    $params[] = $filter_cohort;
}

$report_rows = $wpdb->get_results( $wpdb->prepare(
    "SELECT ca.*, sp.arabic_full_name, sp.english_full_name, sp.cohort_id,
            co.name AS cohort_name, c.name_ar AS course_name_ar, c.name_en AS course_name_en,
            u.display_name AS recorder_name
     FROM {$wpdb->prefix}rsyi_course_attendance ca
     JOIN {$wpdb->prefix}rsyi_student_profiles sp ON sp.id = ca.student_id
     JOIN {$wpdb->prefix}rsyi_cohorts co ON co.id = sp.cohort_id
     LEFT JOIN {$wpdb->prefix}rsyi_courses c ON c.id = ca.course_id
     LEFT JOIN {$wpdb->users} u ON u.ID = ca.recorded_by
     WHERE {$where}
     ORDER BY co.name ASC, sp.arabic_full_name ASC",
    ...$params
) );

// Group by course for summary
$summary = [];
foreach ( $report_rows as $r ) {
    $key = $r->course_id ?: 0;
    $lbl = $r->course_name_ar ?: 'غير محدد';
    if ( ! isset( $summary[$key] ) ) {
        $summary[$key] = [ 'label' => $lbl, 'count' => 0 ];
    }
    $summary[$key]['count']++;
}
arsort( $summary );
?>
<h1 style="margin-bottom:4px;">📊 تقرير المتابعة الدراسي / Daily Study Follow-up Report</h1>

<form method="get" style="background:#fff; border:1px solid #ccd0d4; border-radius:8px; padding:16px 20px; margin:16px 0; display:flex; gap:16px; align-items:flex-end; flex-wrap:wrap;">
    <input type="hidden" name="page" value="rsyi-study-report">
    <div>
        <label style="display:block; font-weight:700; margin-bottom:4px; font-size:12px; color:#555;">📅 التاريخ / Date</label>
        <input type="date" name="report_date" value="<?php echo esc_attr( $filter_date ); ?>"
               style="border:1px solid #ccd0d4; border-radius:4px; padding:6px 10px;">
    </div>
    <div>
        <label style="display:block; font-weight:700; margin-bottom:4px; font-size:12px; color:#555;">👥 الدفعة / Cohort</label>
        <select name="cohort_id" style="border:1px solid #ccd0d4; border-radius:4px; padding:6px 10px; min-width:160px;">
            <option value="">— جميع الدفعات / All Cohorts —</option>
            <?php foreach ( $cohorts as $c ) : ?>
            <option value="<?php echo (int) $c->id; ?>" <?php selected( $filter_cohort, $c->id ); ?>>
                <?php echo esc_html( $c->name ); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="button button-primary">🔍 عرض التقرير / View Report</button>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=rsyi-study-report&report_date=' . urlencode( $filter_date ) . ( $filter_cohort ? '&cohort_id=' . $filter_cohort : '' ) . '&export=1' ) ); ?>"
       class="button" style="background:#27ae60; color:#fff; border-color:#27ae60;">
        ⬇ تصدير CSV / Export CSV
    </a>
</form>

<?php
// CSV Export
if ( ! empty( $_GET['export'] ) && current_user_can( 'rsyi_view_study_report' ) ) {
    $filename = 'study-report-' . $filter_date . '.csv';
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
    $out = fopen( 'php://output', 'w' );
    fputs( $out, "\xEF\xBB\xBF" ); // UTF-8 BOM for Excel
    fputcsv( $out, [ 'الاسم بالعربي', 'الاسم بالإنجليزي', 'الدفعة', 'الكورس', 'ملاحظات', 'تم التسجيل بواسطة' ] );
    foreach ( $report_rows as $r ) {
        fputcsv( $out, [
            $r->arabic_full_name,
            $r->english_full_name,
            $r->cohort_name,
            $r->course_name_ar ?: 'غير محدد',
            $r->notes,
            $r->recorder_name,
        ] );
    }
    fclose( $out );
    exit;
}
?>

<?php if ( empty( $report_rows ) ) : ?>
<div style="background:#f8f9fa; border:1px solid #dee2e6; border-radius:8px; padding:40px; text-align:center; color:#666; margin-top:16px;">
    <div style="font-size:48px; margin-bottom:12px;">📭</div>
    <h3 style="margin:0 0 8px;">لا توجد بيانات لهذا اليوم / No Data for This Date</h3>
    <p style="margin:0;">لم يتم تسجيل أي تقرير في / No report recorded for <strong><?php echo esc_html( $filter_date ); ?></strong>
    <?php echo $filter_cohort ? ' لهذه الدفعة / for this cohort' : ''; ?></p>
    <p style="margin-top:12px; font-size:13px; color:#888;">يقوم الحكمدار بتسجيل التقرير من / Boss Man submits the report via <a href="<?php echo esc_url( get_option( 'rsyi_page_boss_man' ) ? get_permalink( get_option( 'rsyi_page_boss_man' ) ) : '#' ); ?>">لوحة تحكم الحكمدار / Boss Man Dashboard</a></p>
</div>
<?php else : ?>

<!-- Summary Cards -->
<div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(180px, 1fr)); gap:14px; margin-bottom:24px;">
    <div style="background:#fff; border:1px solid #dee2e6; border-radius:8px; padding:16px; text-align:center; border-top:3px solid #0073aa;">
        <div style="font-size:28px; font-weight:700; color:#0073aa;"><?php echo count( $report_rows ); ?></div>
        <div style="font-size:12px; color:#888; text-transform:uppercase;">إجمالي الطلاب / Total Students</div>
    </div>
    <?php foreach ( $summary as $s ) : ?>
    <div style="background:#fff; border:1px solid #dee2e6; border-radius:8px; padding:16px; text-align:center; border-top:3px solid #27ae60;">
        <div style="font-size:28px; font-weight:700; color:#27ae60;"><?php echo (int) $s['count']; ?></div>
        <div style="font-size:12px; color:#555; font-weight:600;"><?php echo esc_html( $s['label'] ); ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Distribution Chart (text-based) -->
<div style="background:#fff; border:1px solid #dee2e6; border-radius:8px; padding:20px; margin-bottom:24px;">
    <h3 style="margin:0 0 16px; font-size:14px; color:#333;">📊 توزيع الطلاب على الكورسات / Student Distribution by Course</h3>
    <?php
    $total = count( $report_rows );
    foreach ( $summary as $s ) :
        $pct = $total > 0 ? round( ($s['count'] / $total) * 100 ) : 0;
    ?>
    <div style="margin-bottom:12px;">
        <div style="display:flex; justify-content:space-between; margin-bottom:4px; font-size:13px;">
            <span><?php echo esc_html( $s['label'] ); ?></span>
            <span style="color:#0073aa; font-weight:700;"><?php echo (int) $s['count']; ?> (<?php echo $pct; ?>%)</span>
        </div>
        <div style="background:#f0f0f0; border-radius:4px; height:8px; overflow:hidden;">
            <div style="background:#0073aa; width:<?php echo $pct; ?>%; height:100%; border-radius:4px;"></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Detailed Table -->
<table class="widefat rsyi-table striped">
    <thead>
        <tr>
            <th style="width:40px;">#</th>
            <th>الطالب / Student</th>
            <th>الدفعة / Cohort</th>
            <th>الكورس / Course</th>
            <th>ملاحظات / Notes</th>
            <th>سجّل بواسطة / Recorded By</th>
        </tr>
    </thead>
    <tbody>
    <?php $i = 1; foreach ( $report_rows as $r ) : ?>
    <tr>
        <td><?php echo $i++; ?></td>
        <td>
            <strong><?php echo esc_html( $r->arabic_full_name ); ?></strong><br>
            <small style="color:#888;"><?php echo esc_html( $r->english_full_name ); ?></small>
        </td>
        <td><?php echo esc_html( $r->cohort_name ); ?></td>
        <td>
            <?php if ( $r->course_name_ar ) : ?>
            <span style="background:#e3f2fd; color:#0073aa; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">
                <?php echo esc_html( $r->course_name_ar ); ?>
            </span>
            <?php else : ?>
            <span style="color:#999;">—</span>
            <?php endif; ?>
        </td>
        <td style="font-size:12px; color:#666;"><?php echo esc_html( $r->notes ?: '—' ); ?></td>
        <td style="font-size:12px; color:#888;"><?php echo esc_html( $r->recorder_name ?: '—' ); ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
