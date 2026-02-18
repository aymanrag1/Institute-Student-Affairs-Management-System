<?php defined( 'ABSPATH' ) || exit; ?>
<!DOCTYPE html><html lang="ar" dir="rtl">
<head><meta charset="UTF-8"><style>
body{font-family:Arial,sans-serif;background:#f5f5f5;direction:rtl;text-align:right}
.container{max-width:600px;margin:30px auto;background:#fff;border-radius:8px;overflow:hidden}
.header{background:#1a1a2e;color:#fff;padding:24px 32px}
.body{padding:24px 32px}
.footer{background:#f0f0f0;padding:16px 32px;font-size:13px;color:#888;text-align:center}
</style></head>
<body>
<div class="container">
    <div class="header"><h1>قرار الطرد من المعهد</h1></div>
    <div class="body">
        <p>السيد / السيدة <strong><?php echo esc_html( $display_name ); ?></strong>،</p>
        <p>بناءً على مراجعة سجلك السلوكي وقرار إدارة المعهد، تقرر <strong>فصلك نهائياً</strong> من معهد البحر الأحمر للتخطيط البحري.</p>
        <p>يمكنك الاطلاع على خطاب الطرد الرسمي من الرابط التالي:</p>
        <?php if ( $letter_url ) : ?>
        <p><a href="<?php echo esc_url( $letter_url ); ?>" style="background:#1a1a2e;color:#fff;padding:10px 20px;border-radius:4px;text-decoration:none">
            تحميل خطاب الطرد
        </a></p>
        <?php endif; ?>
        <p>للاستفسار يرجى التواصل مع إدارة المعهد.</p>
    </div>
    <div class="footer">معهد البحر الأحمر للتخطيط البحري – الجونة، مصر</div>
</div>
</body></html>
