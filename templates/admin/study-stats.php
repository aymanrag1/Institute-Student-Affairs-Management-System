<?php
/**
 * Admin – Course Study Statistics / إحصائيات الكورسات الدراسية
 * Reports: days per student per course, course totals, cohort breakdown
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

global $wpdb;

$filter_cohort   = absint( $_GET['cohort_id']   ?? 0 );
$filter_course   = absint( $_GET['course_id']   ?? 0 );
$filter_from     = sanitize_text_field( $_GET['date_from'] ?? date( 'Y-m-01' ) ); // first of month
$filter_to       = sanitize_text_field( $_GET['date_to']   ?? date( 'Y-m-d' ) );
$active_report   = sanitize_text_field( $_GET['report']    ?? 'student_days' );

$cohorts = $wpdb->get_results(
    "SELECT id, name FROM {$wpdb->prefix}rsyi_cohorts WHERE is_active = 1 ORDER BY name ASC"
);
$courses = $wpdb->get_results(
    "SELECT id, name_ar, name_en FROM {$wpdb->prefix}rsyi_courses WHERE is_active = 1 ORDER BY name_ar ASC"
);

// ── Shared WHERE clause ────────────────────────────────────────────────────────
$where_parts = [ 'ca.report_date BETWEEN %s AND %s' ];
$params      = [ $filter_from, $filter_to ];

if ( $filter_cohort ) {
    $where_parts[] = 'sp.cohort_id = %d';
    $params[]      = $filter_cohort;
}
if ( $filter_course ) {
    $where_parts[] = 'ca.course_id = %d';
    $params[]      = $filter_course;
}
$where = implode( ' AND ', $where_parts );

// ── Report 1: Days per student per course ─────────────────────────────────────
$student_days = [];
if ( $active_report === 'student_days' ) {
    $student_days = $wpdb->get_results( $wpdb->prepare(
        "SELECT sp.arabic_full_name, sp.english_full_name, co.name AS cohort_name,
                c.name_ar AS course_name_ar, c.name_en AS course_name_en,
                COUNT(DISTINCT ca.report_date) AS day_count,
                MIN(ca.report_date) AS first_day,
                MAX(ca.report_date) AS last_day
         FROM {$wpdb->prefix}rsyi_course_attendance ca
         JOIN {$wpdb->prefix}rsyi_student_profiles sp ON sp.id = ca.student_id
         JOIN {$wpdb->prefix}rsyi_cohorts co ON co.id = sp.cohort_id
         LEFT JOIN {$wpdb->prefix}rsyi_courses c ON c.id = ca.course_id
         WHERE {$where}
         GROUP BY ca.student_id, ca.course_id
         ORDER BY sp.arabic_full_name ASC, day_count DESC",
        ...$params
    ) );
}

// ── Report 2: Course totals (student-days per course) ─────────────────────────
$course_totals = [];
if ( $active_report === 'course_totals' ) {
    $course_totals = $wpdb->get_results( $wpdb->prepare(
        "SELECT c.name_ar AS course_name_ar, c.name_en AS course_name_en,
                COUNT(DISTINCT ca.student_id) AS unique_students,
                COUNT(DISTINCT ca.report_date) AS active_days,
                COUNT(*) AS total_entries
         FROM {$wpdb->prefix}rsyi_course_attendance ca
         JOIN {$wpdb->prefix}rsyi_student_profiles sp ON sp.id = ca.student_id
         LEFT JOIN {$wpdb->prefix}rsyi_courses c ON c.id = ca.course_id
         WHERE {$where}
         GROUP BY ca.course_id
         ORDER BY total_entries DESC",
        ...$params
    ) );
}

// ── Report 3: Cohort breakdown per course ─────────────────────────────────────
$cohort_breakdown = [];
if ( $active_report === 'cohort_breakdown' ) {
    $cohort_breakdown = $wpdb->get_results( $wpdb->prepare(
        "SELECT co.name AS cohort_name,
                c.name_ar AS course_name_ar, c.name_en AS course_name_en,
                COUNT(DISTINCT ca.student_id) AS unique_students,
                COUNT(DISTINCT ca.report_date) AS active_days,
                COUNT(*) AS total_entries
         FROM {$wpdb->prefix}rsyi_course_attendance ca
         JOIN {$wpdb->prefix}rsyi_student_profiles sp ON sp.id = ca.student_id
         JOIN {$wpdb->prefix}rsyi_cohorts co ON co.id = sp.cohort_id
         LEFT JOIN {$wpdb->prefix}rsyi_courses c ON c.id = ca.course_id
         WHERE {$where}
         GROUP BY sp.cohort_id, ca.course_id
         ORDER BY co.name ASC, total_entries DESC",
        ...$params
    ) );
}

// ── Report 4: Student attendance summary (total days recorded) ─────────────────
$student_summary = [];
if ( $active_report === 'student_summary' ) {
    $student_summary = $wpdb->get_results( $wpdb->prepare(
        "SELECT sp.arabic_full_name, sp.english_full_name, co.name AS cohort_name,
                COUNT(DISTINCT ca.report_date) AS recorded_days,
                COUNT(DISTINCT ca.course_id) AS unique_courses,
                COUNT(*) AS total_entries
         FROM {$wpdb->prefix}rsyi_course_attendance ca
         JOIN {$wpdb->prefix}rsyi_student_profiles sp ON sp.id = ca.student_id
         JOIN {$wpdb->prefix}rsyi_cohorts co ON co.id = sp.cohort_id
         WHERE {$where}
         GROUP BY ca.student_id
         ORDER BY recorded_days DESC",
        ...$params
    ) );
}
?>
<h1 style="margin-bottom:4px;">📊 إحصائيات الكورسات / Course Statistics</h1>
<p style="margin:0 0 16px; color:#666; font-size:13px; direction:rtl;">
    اختر الفترة الزمنية والنوع من التقارير للاطلاع على إحصائيات المتابعة الدراسية
</p>

<!-- Filters -->
<form method="get" style="background:#fff; border:1px solid #ccd0d4; border-radius:8px; padding:16px 20px; margin-bottom:20px;">
    <input type="hidden" name="page" value="rsyi-study-stats">
    <div style="display:flex; gap:14px; align-items:flex-end; flex-wrap:wrap;">
        <div>
            <label style="display:block; font-weight:700; font-size:12px; color:#555; margin-bottom:4px;">📅 من / From</label>
            <input type="date" name="date_from" value="<?php echo esc_attr( $filter_from ); ?>"
                   style="border:1px solid #ccd0d4; border-radius:4px; padding:6px 10px;">
        </div>
        <div>
            <label style="display:block; font-weight:700; font-size:12px; color:#555; margin-bottom:4px;">📅 إلى / To</label>
            <input type="date" name="date_to" value="<?php echo esc_attr( $filter_to ); ?>"
                   style="border:1px solid #ccd0d4; border-radius:4px; padding:6px 10px;">
        </div>
        <div>
            <label style="display:block; font-weight:700; font-size:12px; color:#555; margin-bottom:4px;">👥 الدفعة / Cohort</label>
            <select name="cohort_id" style="border:1px solid #ccd0d4; border-radius:4px; padding:6px 10px; min-width:140px;">
                <option value="">— الكل / All —</option>
                <?php foreach ( $cohorts as $c ) : ?>
                <option value="<?php echo (int) $c->id; ?>" <?php selected( $filter_cohort, $c->id ); ?>><?php echo esc_html( $c->name ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label style="display:block; font-weight:700; font-size:12px; color:#555; margin-bottom:4px;">📚 الكورس / Course</label>
            <select name="course_id" style="border:1px solid #ccd0d4; border-radius:4px; padding:6px 10px; min-width:140px;">
                <option value="">— الكل / All —</option>
                <?php foreach ( $courses as $c ) : ?>
                <option value="<?php echo (int) $c->id; ?>" <?php selected( $filter_course, $c->id ); ?>><?php echo esc_html( $c->name_ar ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label style="display:block; font-weight:700; font-size:12px; color:#555; margin-bottom:4px;">📋 نوع التقرير / Report Type</label>
            <select name="report" style="border:1px solid #ccd0d4; border-radius:4px; padding:6px 10px; min-width:200px;">
                <option value="student_days"      <?php selected( $active_report, 'student_days' ); ?>>أيام كل طالب في كل كورس / Student Days Per Course</option>
                <option value="student_summary"   <?php selected( $active_report, 'student_summary' ); ?>>ملخص حضور الطلاب / Student Attendance Summary</option>
                <option value="course_totals"     <?php selected( $active_report, 'course_totals' ); ?>>إجماليات الكورسات / Course Totals</option>
                <option value="cohort_breakdown"  <?php selected( $active_report, 'cohort_breakdown' ); ?>>توزيع الدفعات على الكورسات / Cohort Breakdown</option>
            </select>
        </div>
        <button type="submit" class="button button-primary" style="align-self:flex-end;">🔍 عرض / View</button>
        <?php
        $export_url = add_query_arg( array_merge( $_GET, [ 'page' => 'rsyi-study-stats', 'export' => '1' ] ), admin_url( 'admin.php' ) );
        ?>
        <a href="<?php echo esc_url( $export_url ); ?>" class="button" style="background:#27ae60; color:#fff; border-color:#27ae60; align-self:flex-end;">
            ⬇ تصدير CSV / Export
        </a>
    </div>
</form>

<?php
// ── CSV Export ─────────────────────────────────────────────────────────────────
if ( ! empty( $_GET['export'] ) && current_user_can( 'rsyi_view_study_report' ) ) {
    $filename = 'study-stats-' . $active_report . '-' . $filter_from . '-to-' . $filter_to . '.csv';
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
    $out = fopen( 'php://output', 'w' );
    fputs( $out, "\xEF\xBB\xBF" );
    switch ( $active_report ) {
        case 'student_days':
            fputcsv( $out, [ 'الاسم بالعربي', 'الاسم بالإنجليزي', 'الدفعة', 'الكورس', 'عدد الأيام', 'أول يوم', 'آخر يوم' ] );
            foreach ( $student_days as $r ) fputcsv( $out, [ $r->arabic_full_name, $r->english_full_name, $r->cohort_name, $r->course_name_ar, $r->day_count, $r->first_day, $r->last_day ] );
            break;
        case 'student_summary':
            fputcsv( $out, [ 'الاسم بالعربي', 'الاسم بالإنجليزي', 'الدفعة', 'أيام مسجّلة', 'كورسات مختلفة', 'إجمالي السجلات' ] );
            foreach ( $student_summary as $r ) fputcsv( $out, [ $r->arabic_full_name, $r->english_full_name, $r->cohort_name, $r->recorded_days, $r->unique_courses, $r->total_entries ] );
            break;
        case 'course_totals':
            fputcsv( $out, [ 'الكورس', 'Course (EN)', 'طلاب مختلفون', 'أيام نشطة', 'إجمالي السجلات' ] );
            foreach ( $course_totals as $r ) fputcsv( $out, [ $r->course_name_ar, $r->course_name_en, $r->unique_students, $r->active_days, $r->total_entries ] );
            break;
        case 'cohort_breakdown':
            fputcsv( $out, [ 'الدفعة', 'الكورس', 'Course (EN)', 'طلاب مختلفون', 'أيام نشطة', 'إجمالي السجلات' ] );
            foreach ( $cohort_breakdown as $r ) fputcsv( $out, [ $r->cohort_name, $r->course_name_ar, $r->course_name_en, $r->unique_students, $r->active_days, $r->total_entries ] );
            break;
    }
    fclose( $out );
    exit;
}

// ── Render report ──────────────────────────────────────────────────────────────
$badge = static fn( string $text, string $color = '#0073aa' ) =>
    '<span style="background:' . $color . ';color:#fff;padding:2px 10px;border-radius:20px;font-size:12px;font-weight:600;">' . esc_html( $text ) . '</span>';
?>

<?php if ( $active_report === 'student_days' ) : ?>
<!-- ── Report 1: Days per student per course ── -->
<h2 style="direction:rtl; font-size:16px; margin-bottom:12px;">
    📚 أيام كل طالب في كل كورس / Student Days Per Course
    <small style="font-size:12px; color:#888;">(<?php echo esc_html( $filter_from ); ?> → <?php echo esc_html( $filter_to ); ?>)</small>
</h2>
<?php if ( empty( $student_days ) ) : ?>
<div style="background:#f8f9fa;border:1px solid #dee2e6;border-radius:8px;padding:40px;text-align:center;color:#666;">
    <div style="font-size:40px;margin-bottom:8px;">📭</div>
    <p>لا توجد بيانات في هذه الفترة / No data for this period</p>
</div>
<?php else : ?>
<table class="widefat rsyi-table striped" style="direction:rtl;">
    <thead>
        <tr>
            <th>#</th>
            <th>الطالب / Student</th>
            <th>الدفعة / Cohort</th>
            <th>الكورس / Course</th>
            <th style="text-align:center;">عدد الأيام / Days</th>
            <th>أول يوم / First Day</th>
            <th>آخر يوم / Last Day</th>
        </tr>
    </thead>
    <tbody>
    <?php $i = 1; foreach ( $student_days as $r ) : ?>
    <tr>
        <td><?php echo $i++; ?></td>
        <td>
            <strong><?php echo esc_html( $r->arabic_full_name ); ?></strong><br>
            <small style="color:#888;"><?php echo esc_html( $r->english_full_name ); ?></small>
        </td>
        <td><?php echo esc_html( $r->cohort_name ); ?></td>
        <td>
            <?php echo $badge( $r->course_name_ar ?: '—' ); ?>
            <?php if ( $r->course_name_en ) : ?><br><small style="color:#888;"><?php echo esc_html( $r->course_name_en ); ?></small><?php endif; ?>
        </td>
        <td style="text-align:center;">
            <strong style="font-size:18px; color:#0073aa;"><?php echo (int) $r->day_count; ?></strong>
            <span style="font-size:11px; color:#999;"> يوم</span>
        </td>
        <td style="font-size:12px;"><?php echo esc_html( $r->first_day ); ?></td>
        <td style="font-size:12px;"><?php echo esc_html( $r->last_day ); ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php elseif ( $active_report === 'student_summary' ) : ?>
<!-- ── Report 2: Student attendance summary ── -->
<h2 style="direction:rtl; font-size:16px; margin-bottom:12px;">
    👤 ملخص حضور الطلاب / Student Attendance Summary
    <small style="font-size:12px; color:#888;">(<?php echo esc_html( $filter_from ); ?> → <?php echo esc_html( $filter_to ); ?>)</small>
</h2>
<?php if ( empty( $student_summary ) ) : ?>
<div style="background:#f8f9fa;border:1px solid #dee2e6;border-radius:8px;padding:40px;text-align:center;color:#666;">
    <p>لا توجد بيانات / No data</p>
</div>
<?php else : ?>
<!-- Summary bar chart by student -->
<?php
$max_days = max( array_column( (array) $student_summary, 'recorded_days' ) ) ?: 1;
$palette  = [ '#0073aa', '#27ae60', '#e67e22', '#8e44ad', '#e74c3c', '#16a085' ];
?>
<div style="background:#fff;border:1px solid #dee2e6;border-radius:8px;padding:20px;margin-bottom:20px;">
    <h3 style="margin:0 0 16px; font-size:14px; direction:rtl;">📊 الطلاب حسب الأيام المسجّلة / Students by Recorded Days</h3>
    <?php foreach ( $student_summary as $idx => $r ) :
        $pct   = round( ($r->recorded_days / $max_days) * 100 );
        $color = $palette[ $idx % count( $palette ) ];
    ?>
    <div style="margin-bottom:10px;">
        <div style="display:flex; justify-content:space-between; margin-bottom:3px; font-size:13px; direction:rtl;">
            <span><?php echo esc_html( $r->arabic_full_name ); ?> <small style="color:#888;">(<?php echo esc_html( $r->cohort_name ); ?>)</small></span>
            <span style="color:<?php echo $color; ?>; font-weight:700;"><?php echo (int) $r->recorded_days; ?> يوم / <?php echo (int) $r->unique_courses; ?> كورس</span>
        </div>
        <div style="background:#f0f0f0;border-radius:4px;height:10px;overflow:hidden;">
            <div style="background:<?php echo $color; ?>;width:<?php echo $pct; ?>%;height:100%;border-radius:4px;"></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<table class="widefat rsyi-table striped" style="direction:rtl;">
    <thead>
        <tr>
            <th>#</th>
            <th>الطالب / Student</th>
            <th>الدفعة / Cohort</th>
            <th style="text-align:center;">أيام مسجّلة / Recorded Days</th>
            <th style="text-align:center;">كورسات مختلفة / Unique Courses</th>
            <th style="text-align:center;">إجمالي السجلات / Total Entries</th>
        </tr>
    </thead>
    <tbody>
    <?php $i = 1; foreach ( $student_summary as $r ) : ?>
    <tr>
        <td><?php echo $i++; ?></td>
        <td>
            <strong><?php echo esc_html( $r->arabic_full_name ); ?></strong><br>
            <small style="color:#888;"><?php echo esc_html( $r->english_full_name ); ?></small>
        </td>
        <td><?php echo esc_html( $r->cohort_name ); ?></td>
        <td style="text-align:center;"><strong style="font-size:16px;color:#0073aa;"><?php echo (int) $r->recorded_days; ?></strong></td>
        <td style="text-align:center;"><?php echo (int) $r->unique_courses; ?></td>
        <td style="text-align:center;"><?php echo (int) $r->total_entries; ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php elseif ( $active_report === 'course_totals' ) : ?>
<!-- ── Report 3: Course totals ── -->
<h2 style="direction:rtl; font-size:16px; margin-bottom:12px;">
    📚 إجماليات الكورسات / Course Totals
    <small style="font-size:12px; color:#888;">(<?php echo esc_html( $filter_from ); ?> → <?php echo esc_html( $filter_to ); ?>)</small>
</h2>
<?php if ( empty( $course_totals ) ) : ?>
<div style="background:#f8f9fa;border:1px solid #dee2e6;border-radius:8px;padding:40px;text-align:center;color:#666;">
    <p>لا توجد بيانات / No data</p>
</div>
<?php else : ?>
<?php $max_entries = max( array_column( (array) $course_totals, 'total_entries' ) ) ?: 1; ?>
<!-- Bar chart -->
<div style="background:#fff;border:1px solid #dee2e6;border-radius:8px;padding:20px;margin-bottom:20px;">
    <h3 style="margin:0 0 16px; font-size:14px; direction:rtl;">📊 الكورسات حسب إجمالي السجلات / Courses by Total Entries</h3>
    <?php foreach ( $course_totals as $idx => $r ) :
        $pct   = round( ($r->total_entries / $max_entries) * 100 );
        $color = $palette[ $idx % count( $palette ) ];
    ?>
    <div style="margin-bottom:12px;">
        <div style="display:flex; justify-content:space-between; margin-bottom:3px; font-size:13px; direction:rtl;">
            <span><?php echo esc_html( $r->course_name_ar ?: '—' ); ?> <?php if($r->course_name_en): ?><small style="color:#888;">(<?php echo esc_html($r->course_name_en); ?>)</small><?php endif; ?></span>
            <span style="color:<?php echo $color; ?>; font-weight:700;"><?php echo (int)$r->total_entries; ?> سجل / <?php echo (int)$r->unique_students; ?> طالب</span>
        </div>
        <div style="background:#f0f0f0;border-radius:4px;height:10px;overflow:hidden;">
            <div style="background:<?php echo $color; ?>;width:<?php echo $pct; ?>%;height:100%;border-radius:4px;"></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<table class="widefat rsyi-table striped" style="direction:rtl;">
    <thead>
        <tr>
            <th>#</th>
            <th>الكورس / Course</th>
            <th style="text-align:center;">طلاب مختلفون / Unique Students</th>
            <th style="text-align:center;">أيام نشطة / Active Days</th>
            <th style="text-align:center;">إجمالي السجلات / Total Entries</th>
        </tr>
    </thead>
    <tbody>
    <?php $i = 1; foreach ( $course_totals as $r ) : ?>
    <tr>
        <td><?php echo $i++; ?></td>
        <td>
            <?php echo $badge( $r->course_name_ar ?: '—' ); ?>
            <?php if ( $r->course_name_en ) : ?><br><small style="color:#888;"><?php echo esc_html( $r->course_name_en ); ?></small><?php endif; ?>
        </td>
        <td style="text-align:center;"><strong style="font-size:16px;color:#27ae60;"><?php echo (int) $r->unique_students; ?></strong></td>
        <td style="text-align:center;"><?php echo (int) $r->active_days; ?></td>
        <td style="text-align:center;"><strong style="font-size:16px;color:#0073aa;"><?php echo (int) $r->total_entries; ?></strong></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php elseif ( $active_report === 'cohort_breakdown' ) : ?>
<!-- ── Report 4: Cohort breakdown ── -->
<h2 style="direction:rtl; font-size:16px; margin-bottom:12px;">
    👥 توزيع الدفعات على الكورسات / Cohort Breakdown by Course
    <small style="font-size:12px; color:#888;">(<?php echo esc_html( $filter_from ); ?> → <?php echo esc_html( $filter_to ); ?>)</small>
</h2>
<?php if ( empty( $cohort_breakdown ) ) : ?>
<div style="background:#f8f9fa;border:1px solid #dee2e6;border-radius:8px;padding:40px;text-align:center;color:#666;">
    <p>لا توجد بيانات / No data</p>
</div>
<?php else :
    // Group by cohort
    $by_cohort = [];
    foreach ( $cohort_breakdown as $r ) {
        $cn = $r->cohort_name;
        if ( ! isset( $by_cohort[$cn] ) ) $by_cohort[$cn] = [];
        $by_cohort[$cn][] = $r;
    }
?>
<?php foreach ( $by_cohort as $cohort_name => $rows ) : ?>
<div style="background:#fff;border:1px solid #dee2e6;border-radius:8px;padding:16px 20px;margin-bottom:16px;">
    <h3 style="margin:0 0 12px; font-size:14px; color:#0073aa; direction:rtl;">👥 <?php echo esc_html( $cohort_name ); ?></h3>
    <table class="widefat rsyi-table striped" style="direction:rtl;">
        <thead>
            <tr>
                <th>#</th>
                <th>الكورس / Course</th>
                <th style="text-align:center;">طلاب مختلفون / Students</th>
                <th style="text-align:center;">أيام نشطة / Days</th>
                <th style="text-align:center;">إجمالي / Total</th>
            </tr>
        </thead>
        <tbody>
        <?php $i = 1; foreach ( $rows as $r ) : ?>
        <tr>
            <td><?php echo $i++; ?></td>
            <td><?php echo $badge( $r->course_name_ar ?: '—' ); ?> <?php if($r->course_name_en): ?><small style="color:#888;"><?php echo esc_html($r->course_name_en); ?></small><?php endif; ?></td>
            <td style="text-align:center;"><strong style="color:#27ae60;"><?php echo (int)$r->unique_students; ?></strong></td>
            <td style="text-align:center;"><?php echo (int)$r->active_days; ?></td>
            <td style="text-align:center;"><strong style="color:#0073aa;"><?php echo (int)$r->total_entries; ?></strong></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endforeach; ?>
<?php endif; ?>
<?php endif; ?>
