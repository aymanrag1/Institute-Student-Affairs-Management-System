<?php defined( 'ABSPATH' ) || exit; ?>
<!DOCTYPE html><html lang="ar" dir="rtl">
<head><meta charset="UTF-8"><style>
body{font-family:Arial,sans-serif;background:#f5f5f5;direction:rtl;text-align:right}
.container{max-width:600px;margin:30px auto;background:#fff;border-radius:8px;overflow:hidden}
.header{background:#5d6d7e;color:#fff;padding:24px 32px}
.body{padding:24px 32px}
.footer{background:#f0f0f0;padding:16px 32px;font-size:13px;color:#888;text-align:center}
</style></head>
<body>
<div class="container">
    <div class="header"><h1>إشعار تنفيذ قرار طرد</h1></div>
    <div class="body">
        <p>حضرة السيد / السيدة <strong><?php echo esc_html( $staff_name ); ?></strong>،</p>
        <p>نُعلمكم بأنه تم تنفيذ قرار طرد الطالب <strong><?php echo esc_html( $student_name ); ?></strong> (قضية رقم #<?php echo (int) $case_id; ?>).</p>
        <?php if ( $letter_url ) : ?>
        <p><a href="<?php echo esc_url( $letter_url ); ?>">تحميل خطاب الطرد الرسمي</a></p>
        <?php endif; ?>
        <p>يُرجى اتخاذ الإجراءات اللازمة من جانبكم.</p>
    </div>
    <div class="footer">معهد البحر الأحمر للتخطيط البحري – الجونة، مصر</div>
</div>
</body></html>
