<?php defined( 'ABSPATH' ) || exit; ?>
<!DOCTYPE html><html lang="ar" dir="rtl">
<head><meta charset="UTF-8"><style>
body{font-family:Arial,sans-serif;background:#f5f5f5;direction:rtl;text-align:right}
.container{max-width:600px;margin:30px auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1)}
.header{background:#c0392b;color:#fff;padding:24px 32px}
.body{padding:24px 32px}
.footer{background:#f0f0f0;padding:16px 32px;font-size:13px;color:#888;text-align:center}
.reason-box{background:#fff3cd;border:1px solid #ffc107;border-radius:4px;padding:12px 16px;margin:16px 0}
.badge{display:inline-block;background:#c0392b;color:#fff;padding:4px 12px;border-radius:4px}
</style></head>
<body>
<div class="container">
    <div class="header"><h1>معهد البحر الأحمر – إشعار رفض وثيقة</h1></div>
    <div class="body">
        <p>عزيزي / عزيزتي <strong><?php echo esc_html( $display_name ); ?></strong>،</p>
        <p>نأسف لإخبارك بأنه تم رفض الوثيقة التالية:</p>
        <p><span class="badge">❌ <?php echo esc_html( $doc_type_label ); ?></span></p>
        <div class="reason-box">
            <strong>سبب الرفض:</strong><br>
            <?php echo esc_html( $reason ); ?>
        </div>
        <p>يرجى مراجعة السبب وإعادة رفع الوثيقة الصحيحة من خلال البوابة الإلكترونية.</p>
        <p><a href="<?php echo esc_url( home_url( '/portal/documents/' ) ); ?>" style="background:#1a5f7a;color:#fff;padding:10px 20px;border-radius:4px;text-decoration:none">
            الذهاب إلى البوابة
        </a></p>
    </div>
    <div class="footer">معهد البحر الأحمر للتخطيط البحري – الجونة، مصر</div>
</div>
</body></html>
