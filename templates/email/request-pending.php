<?php defined( 'ABSPATH' ) || exit; ?>
<!DOCTYPE html><html lang="ar" dir="rtl">
<head><meta charset="UTF-8"><style>
body{font-family:Arial,sans-serif;background:#f5f5f5;direction:rtl;text-align:right}
.container{max-width:600px;margin:30px auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1)}
.header{background:#2980b9;color:#fff;padding:24px 32px}
.body{padding:24px 32px}
.footer{background:#f0f0f0;padding:16px 32px;font-size:13px;color:#888;text-align:center}
.info-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #eee}
</style></head>
<body>
<div class="container">
    <div class="header"><h1>طلب <?php echo esc_html( $request_type ); ?> جديد #<?php echo (int) $request_id; ?></h1></div>
    <div class="body">
        <p>عزيزي / عزيزتي <strong><?php echo esc_html( $approver_name ); ?></strong>،</p>
        <p>يوجد طلب <?php echo esc_html( $request_type ); ?> جديد يستوجب مراجعتك وموافقتك.</p>
        <div class="info-row"><span>من:</span><strong><?php echo esc_html( $from_datetime ); ?></strong></div>
        <div class="info-row"><span>إلى:</span><strong><?php echo esc_html( $to_datetime ); ?></strong></div>
        <div class="info-row"><span>السبب:</span><span><?php echo esc_html( $reason ); ?></span></div>
        <br>
        <a href="<?php echo esc_url( $admin_url ); ?>" style="background:#2980b9;color:#fff;padding:10px 20px;border-radius:4px;text-decoration:none">
            مراجعة الطلب الآن
        </a>
    </div>
    <div class="footer">معهد البحر الأحمر للتخطيط البحري – الجونة، مصر</div>
</div>
</body></html>
