<?php
/**
 * PDF Template – Expulsion Letter
 * Variables: $case_id, $profile (student profile object)
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

global $wpdb;
$case = $wpdb->get_row( $wpdb->prepare(
    "SELECT ec.*, u.display_name AS dean_name
     FROM {$wpdb->prefix}rsyi_expulsion_cases ec
     LEFT JOIN {$wpdb->users} u ON u.ID = ec.dean_id
     WHERE ec.id = %d",
    $case_id
) );
$cohort = \RSYI_SA\Modules\Cohorts::get_cohort( (int) $profile->cohort_id );
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<style>
body { font-family: 'DejaVu Sans', Arial, sans-serif; direction: rtl; font-size: 13px; color: #111; padding: 40px; line-height: 1.8; }
.letterhead { text-align: center; margin-bottom: 40px; border-bottom: 4px double #1a1a2e; padding-bottom: 20px; }
.letterhead h1 { font-size: 22px; color: #1a1a2e; margin: 0 0 6px; }
.letterhead p { margin: 2px 0; color: #555; }
.letter-meta { display: flex; justify-content: space-between; margin-bottom: 30px; }
.letter-body { margin: 20px 0; }
.letter-body p { margin: 14px 0; }
.student-box { border: 2px solid #1a1a2e; padding: 16px; margin: 20px 0; border-radius: 4px; }
.student-box table td:first-child { font-weight: bold; width: 180px; }
.signature { margin-top: 60px; }
.stamp { width: 120px; height: 120px; border: 3px solid #1a1a2e; border-radius: 50%; display: inline-block; text-align: center; line-height: 120px; color: #1a1a2e; font-size: 13px; margin-right: 30px; }
.official-text { font-size: 11px; color: #888; margin-top: 30px; border-top: 1px solid #ccc; padding-top: 10px; }
</style>
</head>
<body>
<div class="letterhead">
    <h1>معهد البحر الأحمر للتخطيط البحري</h1>
    <p>الجونة – محافظة البحر الأحمر – جمهورية مصر العربية</p>
    <p>نظام شؤون الطلاب | برنامج المنح الدراسية</p>
</div>

<div class="letter-meta">
    <span>رقم المرجع: RSYI/EXP/<?php echo str_pad( $case_id, 4, '0', STR_PAD_LEFT ); ?>/<?php echo date( 'Y' ); ?></span>
    <span>التاريخ: <?php echo esc_html( date( 'd/m/Y' ) ); ?></span>
</div>

<p>إلى: السيد / السيدة <strong><?php echo esc_html( $profile->arabic_full_name ); ?></strong></p>
<p>تحية طيبة وبعد،،،</p>

<div class="letter-body">
    <p>
        بناءً على مراجعة سجل سلوكياتكم وتصرفاتكم خلال فترة الدراسة في معهد البحر الأحمر للتخطيط البحري،
        وبعد استيفاء الإجراءات المنصوص عليها في اللائحة الداخلية للمعهد،
        وبموافقة سعادة عميد المعهد،
    </p>
    <p><strong>يُعلمكم المعهد بقرار <u>الفصل النهائي</u> من برنامج المنح الدراسية اعتباراً من تاريخه.</strong></p>

    <div class="student-box">
        <table>
            <tr><td>الاسم الكامل (عربي):</td><td><?php echo esc_html( $profile->arabic_full_name ); ?></td></tr>
            <tr><td>الاسم الكامل (إنجليزي):</td><td><?php echo esc_html( $profile->english_full_name ); ?></td></tr>
            <tr><td>رقم الهوية الوطنية:</td><td><?php echo esc_html( $profile->national_id_number ); ?></td></tr>
            <tr><td>الفوج:</td><td><?php echo esc_html( $cohort->name ?? '—' ); ?></td></tr>
            <tr><td>إجمالي النقاط السلوكية:</td><td><?php echo esc_html( $case->total_points ?? '' ); ?> / 40</td></tr>
            <tr><td>تاريخ قرار الطرد:</td><td><?php echo esc_html( $case->executed_at ?? '' ); ?></td></tr>
        </table>
    </div>

    <p>
        يتعين عليكم إعادة جميع ممتلكات المعهد وإخلاء مسكن الطلاب خلال 48 ساعة من تاريخ هذا القرار.
        وفي حال الاعتراض، يحق لكم التقدم بتظلم رسمي إلى مجلس المعهد خلال 14 يوماً.
    </p>
</div>

<div class="signature">
    <p>اعتمد بمعرفة:</p>
    <table style="width:100%"><tr>
        <td style="text-align:center;width:50%">
            <div class="stamp">ختم<br>المعهد</div><br>
            عميد المعهد<br>
            <strong><?php echo esc_html( $case->dean_name ?? '' ); ?></strong>
            <br><br>التوقيع: __________________
        </td>
        <td style="text-align:center;width:50%">
            مدير شؤون الطلاب<br><br><br>
            التوقيع: __________________
        </td>
    </tr></table>
</div>

<p class="official-text">
    هذا الخطاب وثيقة رسمية صادرة عن نظام شؤون الطلاب الرقمي – معهد البحر الأحمر للتخطيط البحري.
    رقم القضية: #<?php echo (int) $case_id; ?>
</p>
</body>
</html>
