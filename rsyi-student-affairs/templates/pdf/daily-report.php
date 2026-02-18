<?php
/**
 * PDF Template – Daily Aggregated Exit + Overnight Report
 * Variables: $data (array with 'exit' and 'overnight' keys), $date, $cohort_id
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;
$report_date = $data['date'] ?? $date ?? date( 'Y-m-d' );
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; }
body { font-family: 'DejaVu Sans', Arial, sans-serif; direction: rtl; font-size: 12px; color: #222; margin: 0; padding: 20px; }
.report-header { text-align: center; border-bottom: 3px solid #1a5f7a; padding-bottom: 16px; margin-bottom: 20px; }
.report-header h1 { font-size: 20px; margin: 0 0 6px; color: #1a5f7a; }
.report-header p { margin: 2px 0; font-size: 13px; color: #555; }
h2 { font-size: 15px; color: #1a5f7a; border-bottom: 1px solid #ccc; padding-bottom: 6px; margin-top: 24px; }
table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
th { background: #1a5f7a; color: #fff; padding: 8px 10px; text-align: right; }
td { padding: 7px 10px; border-bottom: 1px solid #e0e0e0; }
tr:nth-child(even) td { background: #f9f9f9; }
.no-data { text-align: center; color: #999; font-style: italic; padding: 12px; }
.footer { text-align: center; margin-top: 30px; font-size: 11px; color: #888; border-top: 1px solid #ccc; padding-top: 10px; }
.badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 11px; }
.badge-approved { background: #d4edda; color: #155724; }
.badge-executed { background: #cce5ff; color: #004085; }
</style>
</head>
<body>
<div class="report-header">
    <h1>معهد البحر الأحمر للتخطيط البحري – الجونة</h1>
    <p>التقرير اليومي لأذونات الخروج والمبيت</p>
    <p>التاريخ: <strong><?php echo esc_html( $report_date ); ?></strong>
    <?php if ( $cohort_id ) : ?>
        &nbsp;|&nbsp; الفوج: <strong><?php echo esc_html( \RSYI_SA\Modules\Cohorts::get_cohort( $cohort_id )->name ?? '' ); ?></strong>
    <?php else : ?>
        &nbsp;|&nbsp; جميع الأفواج
    <?php endif; ?>
    </p>
    <p>وقت الطباعة: <?php echo esc_html( current_time( 'd/m/Y H:i' ) ); ?></p>
</div>

<!-- EXIT PERMITS -->
<h2>أذونات الخروج</h2>
<?php if ( empty( $data['exit'] ) ) : ?>
    <p class="no-data">لا توجد أذونات خروج لهذا التاريخ.</p>
<?php else : ?>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>اسم الطالب</th>
            <th>الفوج</th>
            <th>من</th>
            <th>إلى</th>
            <th>السبب</th>
            <th>الحالة</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ( $data['exit'] as $i => $p ) : ?>
    <tr>
        <td><?php echo $i + 1; ?></td>
        <td><?php echo esc_html( $p->arabic_full_name ); ?></td>
        <td><?php echo esc_html( $p->cohort_name ); ?></td>
        <td><?php echo esc_html( $p->from_datetime ); ?></td>
        <td><?php echo esc_html( $p->to_datetime ); ?></td>
        <td><?php echo esc_html( $p->reason ); ?></td>
        <td>
            <span class="badge <?php echo $p->status === 'executed' ? 'badge-executed' : 'badge-approved'; ?>">
                <?php echo $p->status === 'executed' ? 'منفَّذ' : 'موافق عليه'; ?>
            </span>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<p><strong>الإجمالي: <?php echo count( $data['exit'] ); ?> إذن</strong></p>
<?php endif; ?>

<!-- OVERNIGHT PERMITS -->
<h2>أذونات المبيت</h2>
<?php if ( empty( $data['overnight'] ) ) : ?>
    <p class="no-data">لا توجد أذونات مبيت لهذا التاريخ.</p>
<?php else : ?>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>اسم الطالب</th>
            <th>الفوج</th>
            <th>من</th>
            <th>إلى</th>
            <th>السبب</th>
            <th>الحالة</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ( $data['overnight'] as $i => $p ) : ?>
    <tr>
        <td><?php echo $i + 1; ?></td>
        <td><?php echo esc_html( $p->arabic_full_name ); ?></td>
        <td><?php echo esc_html( $p->cohort_name ); ?></td>
        <td><?php echo esc_html( $p->from_datetime ); ?></td>
        <td><?php echo esc_html( $p->to_datetime ); ?></td>
        <td><?php echo esc_html( $p->reason ); ?></td>
        <td>
            <span class="badge <?php echo $p->status === 'executed' ? 'badge-executed' : 'badge-approved'; ?>">
                <?php echo $p->status === 'executed' ? 'منفَّذ' : 'موافق عليه'; ?>
            </span>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<p><strong>الإجمالي: <?php echo count( $data['overnight'] ); ?> إذن</strong></p>
<?php endif; ?>

<div class="footer">
    هذا التقرير مُنشأ آلياً من نظام شؤون الطلاب – معهد البحر الأحمر للتخطيط البحري<br>
    لا يُعتد بهذا التقرير إلا بوجود ختم وتوقيع المسؤول
</div>
</body>
</html>
