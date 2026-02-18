<?php defined( 'ABSPATH' ) || exit; ?>
<!DOCTYPE html><html lang="ar" dir="rtl">
<head><meta charset="UTF-8"><style>
body{font-family:Arial,sans-serif;background:#f5f5f5;direction:rtl;text-align:right}
.container{max-width:600px;margin:30px auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1)}
.header{background:#922b21;color:#fff;padding:24px 32px}
.body{padding:24px 32px}
.footer{background:#f0f0f0;padding:16px 32px;font-size:13px;color:#888;text-align:center}
</style></head>
<body>
<div class="container">
    <div class="header"><h1>🚨 قضية طرد جديدة – مطلوب موافقة العميد</h1></div>
    <div class="body">
        <p>حضرة السيد / السيدة <strong><?php echo esc_html( $dean_name ); ?></strong>،</p>
        <p>تجاوز الطالب <strong><?php echo esc_html( $student_name ); ?></strong> حد النقاط المحدد (40 نقطة).</p>
        <p>إجمالي النقاط الحالية: <strong><?php echo (int) $total_points; ?></strong></p>
        <p>رقم القضية: <strong>#<?php echo (int) $case_id; ?></strong></p>
        <p>يستلزم الأمر موافقتكم لإتمام قرار الطرد أو رفضه.</p>
        <p><a href="<?php echo esc_url( $admin_url ); ?>" style="background:#922b21;color:#fff;padding:10px 20px;border-radius:4px;text-decoration:none">
            مراجعة القضية الآن
        </a></p>
    </div>
    <div class="footer">معهد البحر الأحمر للتخطيط البحري – الجونة، مصر</div>
</div>
</body></html>
