<?php defined( 'ABSPATH' ) || exit; ?>
<!DOCTYPE html><html lang="ar" dir="rtl">
<head><meta charset="UTF-8"><style>
body{font-family:Arial,sans-serif;background:#f5f5f5;direction:rtl;text-align:right}
.container{max-width:600px;margin:30px auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1)}
.header{background:#e67e22;color:#fff;padding:24px 32px}
.body{padding:24px 32px}
.footer{background:#f0f0f0;padding:16px 32px;font-size:13px;color:#888;text-align:center}
.pts-box{font-size:48px;font-weight:bold;color:#c0392b;text-align:center;padding:16px}
.info-box{background:#fef9e7;border:1px solid #f39c12;border-radius:4px;padding:12px 16px;margin:12px 0}
</style></head>
<body>
<div class="container">
    <div class="header"><h1>⚠ تحذير سلوكي رسمي</h1></div>
    <div class="body">
        <p>عزيزي / عزيزتي <strong><?php echo esc_html( $display_name ); ?></strong>،</p>
        <p>وصل رصيدك في نظام النقاط السلوكية إلى:</p>
        <div class="pts-box"><?php echo (int) $threshold; ?> نقطة</div>
        <div class="info-box">
            <strong>إجمالي النقاط الحالية:</strong> <?php echo (int) $total_points; ?> / 40<br>
            <?php if ( $threshold >= 30 ) : ?>
            <strong style="color:#c0392b">تحذير: وصولك إلى 40 نقطة سيستوجب فتح قضية طرد من المعهد.</strong>
            <?php else : ?>
            يرجى الالتزام بلوائح المعهد لتجنب تصاعد العقوبات.
            <?php endif; ?>
        </div>
        <p>يجب عليك تسجيل الدخول إلى البوابة الإلكترونية والإقرار باستلام هذا التحذير.</p>
        <p><a href="<?php echo esc_url( $portal_url ); ?>" style="background:#e67e22;color:#fff;padding:10px 20px;border-radius:4px;text-decoration:none">
            الإقرار بالتحذير
        </a></p>
    </div>
    <div class="footer">معهد البحر الأحمر للتخطيط البحري – الجونة، مصر</div>
</div>
</body></html>
