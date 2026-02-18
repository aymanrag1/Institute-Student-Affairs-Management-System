<?php
/**
 * Portal – Behavior Record
 * Variables: $profile, $violations, $total_pts, $warnings
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="rsyi-portal" dir="rtl">
    <h2><?php esc_html_e( 'سجلي السلوكي', 'rsyi-sa' ); ?></h2>

    <div class="rsyi-info-cards">
        <div class="rsyi-info-card <?php echo $total_pts >= 30 ? 'rsyi-pts-danger' : ( $total_pts >= 20 ? 'rsyi-pts-warning' : '' ); ?>">
            <span class="rsyi-info-label"><?php esc_html_e( 'إجمالي نقاط المخالفات', 'rsyi-sa' ); ?></span>
            <span class="rsyi-info-value"><?php echo esc_html( $total_pts ); ?> / 40</span>
        </div>
    </div>

    <?php if ( $total_pts >= 30 ) : ?>
    <div class="rsyi-alert rsyi-alert-danger">
        <?php esc_html_e( '⚠ تحذير: رصيدك السلوكي مرتفع جداً. الوصول إلى 40 نقطة سيستوجب فتح قضية طرد.', 'rsyi-sa' ); ?>
    </div>
    <?php endif; ?>

    <!-- Threshold progress bar -->
    <div style="margin:20px 0;">
        <div style="background:#eee;border-radius:8px;height:18px;position:relative;overflow:hidden;">
            <?php
            $pct = min( 100, round( $total_pts / 40 * 100 ) );
            $bar_color = $total_pts >= 30 ? '#c0392b' : ( $total_pts >= 20 ? '#e67e22' : ( $total_pts >= 10 ? '#f39c12' : '#27ae60' ) );
            ?>
            <div style="background:<?php echo esc_attr( $bar_color ); ?>;width:<?php echo esc_attr( $pct ); ?>%;height:100%;border-radius:8px;transition:width .5s;"></div>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:11px;color:#888;margin-top:4px;">
            <span>0</span><span>10</span><span>20</span><span>30</span><span>40</span>
        </div>
    </div>

    <!-- Pending acknowledgments -->
    <?php
    $pending_acks = array_filter( $warnings, fn($w) => ! $w->acknowledged_at && (int)$w->threshold < 40 );
    if ( ! empty( $pending_acks ) ) :
    ?>
    <div class="rsyi-alert rsyi-alert-danger" id="rsyi_ack_section">
        <strong><?php esc_html_e( 'يجب الإقرار بالتحذيرات التالية:', 'rsyi-sa' ); ?></strong>
        <?php foreach ( $pending_acks as $w ) : ?>
        <div class="rsyi-warning-item">
            <p><?php printf( esc_html__( 'تحذير عند %d نقطة', 'rsyi-sa' ), (int) $w->threshold ); ?></p>
            <button class="button rsyi-ack-btn" data-warning-id="<?php echo esc_attr( $w->id ); ?>">
                <?php esc_html_e( 'إقرار واستلام ✍', 'rsyi-sa' ); ?>
            </button>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Violations table -->
    <h3><?php esc_html_e( 'تفاصيل المخالفات', 'rsyi-sa' ); ?></h3>
    <?php if ( empty( $violations ) ) : ?>
        <p class="rsyi-hint"><?php esc_html_e( 'لا توجد مخالفات مسجلة.', 'rsyi-sa' ); ?></p>
    <?php else : ?>
    <table class="rsyi-portal-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'نوع المخالفة', 'rsyi-sa' ); ?></th>
                <th><?php esc_html_e( 'النقاط', 'rsyi-sa' ); ?></th>
                <th><?php esc_html_e( 'التاريخ', 'rsyi-sa' ); ?></th>
                <th><?php esc_html_e( 'الوصف', 'rsyi-sa' ); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ( $violations as $v ) : ?>
        <tr>
            <td><?php echo esc_html( $v->type_ar ); ?></td>
            <td><strong><?php echo esc_html( $v->points_assigned ); ?></strong></td>
            <td><?php echo esc_html( $v->incident_date ); ?></td>
            <td><?php echo esc_html( $v->description ); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <!-- Warnings timeline -->
    <h3><?php esc_html_e( 'سجل التحذيرات', 'rsyi-sa' ); ?></h3>
    <?php if ( empty( $warnings ) ) : ?>
        <p class="rsyi-hint"><?php esc_html_e( 'لا توجد تحذيرات.', 'rsyi-sa' ); ?></p>
    <?php else : ?>
    <table class="rsyi-portal-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'العتبة', 'rsyi-sa' ); ?></th>
                <th><?php esc_html_e( 'النقاط وقت التحذير', 'rsyi-sa' ); ?></th>
                <th><?php esc_html_e( 'تاريخ التحذير', 'rsyi-sa' ); ?></th>
                <th><?php esc_html_e( 'الإقرار', 'rsyi-sa' ); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ( $warnings as $w ) : ?>
        <tr>
            <td><?php echo esc_html( $w->threshold ); ?> <?php esc_html_e( 'نقطة', 'rsyi-sa' ); ?></td>
            <td><?php echo esc_html( $w->total_points_at_warning ); ?></td>
            <td><?php echo esc_html( $w->created_at ); ?></td>
            <td>
                <?php if ( $w->acknowledged_at ) : ?>
                    <span style="color:#1a7a4a;">✅ <?php echo esc_html( $w->acknowledged_at ); ?></span>
                <?php elseif ( (int)$w->threshold < 40 ) : ?>
                    <span style="color:#c0392b;"><?php esc_html_e( 'لم يتم الإقرار بعد', 'rsyi-sa' ); ?></span>
                <?php else : ?>
                    <span style="color:#c0392b;"><?php esc_html_e( 'قضية طرد', 'rsyi-sa' ); ?></span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<script>
jQuery(function($){
    $('.rsyi-ack-btn').on('click', function(){
        var btn = $(this);
        var id  = btn.data('warning-id');
        if(!confirm('<?php echo esc_js( __( 'هل تقر بأنك اطلعت على هذا التحذير وفهمت محتواه؟', 'rsyi-sa' ) ); ?>')) return;
        $.post(rsyiPortal.ajaxUrl, {
            action: 'rsyi_acknowledge_warning',
            _nonce: rsyiPortal.nonce,
            warning_id: id
        }, function(res){
            if(res.success){
                btn.closest('.rsyi-warning-item').html('<p class="rsyi-success">✅ ' + res.data.message + '</p>');
                setTimeout(function(){ location.reload(); }, 1200);
            } else {
                alert(res.data.message);
            }
        });
    });
});
</script>
