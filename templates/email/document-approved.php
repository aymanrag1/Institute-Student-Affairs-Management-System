<?php defined( 'ABSPATH' ) || exit; ?>
<!DOCTYPE html><html lang="ar" dir="rtl">
<head><meta charset="UTF-8"><style>
body{font-family:Arial,sans-serif;background:#f5f5f5;direction:rtl;text-align:right}
.container{max-width:600px;margin:30px auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1)}
.header{background:#1a5f7a;color:#fff;padding:24px 32px}
.header h1{margin:0;font-size:22px}
.body{padding:24px 32px}
.footer{background:#f0f0f0;padding:16px 32px;font-size:13px;color:#888;text-align:center}
.badge{display:inline-block;background:#28a745;color:#fff;padding:4px 12px;border-radius:4px}
</style></head>
<body>
<div class="container">
    <div class="header">
        <h1>معهد البحر الأحمر للتخطيط البحري</h1>
        <p style="margin:4px 0 0">نظام شؤون الطلاب</p>
    </div>
    <div class="body">
        <p>عزيزي / عزيزتي <strong><?php echo esc_html( $display_name ); ?></strong>،</p>
        <p>يسعدنا إخبارك بأنه تمت الموافقة على الوثيقة التالية:</p>
        <p><span class="badge">✅ <?php echo esc_html( $doc_type_label ); ?></span></p>
        <p>سيتم تفعيل حسابك تلقائياً بمجرد اعتماد جميع الوثائق المطلوبة.</p>
    </div>
    <div class="footer">
        <?php esc_html_e( 'معهد البحر الأحمر للتخطيط البحري – الجونة، مصر', 'rsyi-sa' ); ?> | <?php echo esc_html( get_bloginfo( 'name' ) ); ?>
    </div>
</div>
</body></html>
