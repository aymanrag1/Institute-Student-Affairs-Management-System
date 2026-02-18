<?php defined( 'ABSPATH' ) || exit; ?>
<!DOCTYPE html><html lang="ar" dir="rtl">
<head><meta charset="UTF-8"><style>
body{font-family:Arial,sans-serif;background:#f5f5f5;direction:rtl;text-align:right}
.container{max-width:600px;margin:30px auto;background:#fff;border-radius:8px;overflow:hidden}
.header{background:#c0392b;color:#fff;padding:24px 32px}
.body{padding:24px 32px}
.footer{background:#f0f0f0;padding:16px 32px;font-size:13px;color:#888;text-align:center}
.reason-box{background:#fdf2f2;border:1px solid #e74c3c;border-radius:4px;padding:12px;margin:12px 0}
</style></head>
<body>
<div class="container">
    <div class="header"><h1>❌ تم رفض طلبك</h1></div>
    <div class="body">
        <p>عزيزي / عزيزتي <strong><?php echo esc_html( $display_name ); ?></strong>،</p>
        <p>نأسف لإخبارك بأنه تم رفض <?php echo esc_html( $request_type ); ?> رقم #<?php echo (int) $request_id; ?>.</p>
        <div class="reason-box">
            <strong>ملاحظات الإدارة:</strong><br>
            <?php echo esc_html( $notes ); ?>
        </div>
        <p>يمكنك تقديم طلب جديد من البوابة الإلكترونية.</p>
    </div>
    <div class="footer">معهد البحر الأحمر للتخطيط البحري – الجونة، مصر</div>
</div>
</body></html>
