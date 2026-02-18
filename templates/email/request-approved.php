<?php defined( 'ABSPATH' ) || exit; ?>
<!DOCTYPE html><html lang="ar" dir="rtl">
<head><meta charset="UTF-8"><style>
body{font-family:Arial,sans-serif;background:#f5f5f5;direction:rtl;text-align:right}
.container{max-width:600px;margin:30px auto;background:#fff;border-radius:8px;overflow:hidden}
.header{background:#1a7a4a;color:#fff;padding:24px 32px}
.body{padding:24px 32px}
.footer{background:#f0f0f0;padding:16px 32px;font-size:13px;color:#888;text-align:center}
</style></head>
<body>
<div class="container">
    <div class="header"><h1>✅ تمت الموافقة على طلبك</h1></div>
    <div class="body">
        <p>عزيزي / عزيزتي <strong><?php echo esc_html( $display_name ); ?></strong>،</p>
        <p>تمت الموافقة على <?php echo esc_html( $request_type ); ?> رقم #<?php echo (int) $request_id; ?>.</p>
        <p><a href="<?php echo esc_url( $portal_url ); ?>" style="background:#1a7a4a;color:#fff;padding:10px 20px;border-radius:4px;text-decoration:none">
            عرض طلبي
        </a></p>
    </div>
    <div class="footer">معهد البحر الأحمر للتخطيط البحري – الجونة، مصر</div>
</div>
</body></html>
