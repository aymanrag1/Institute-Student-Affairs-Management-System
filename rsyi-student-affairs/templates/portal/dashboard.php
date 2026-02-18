<?php
/**
 * Portal – Student Dashboard
 * Variables: $profile, $total_pts, $warnings (array), $cohort
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

$status_labels = [
    'pending_docs' => __( 'انتظار وثائق', 'rsyi-sa' ),
    'active'       => __( 'نشط', 'rsyi-sa' ),
    'suspended'    => __( 'موقوف', 'rsyi-sa' ),
    'expelled'     => __( 'مطرود', 'rsyi-sa' ),
];
?>
<div class="rsyi-portal" dir="rtl">
    <h2><?php esc_html_e( 'مرحباً،', 'rsyi-sa' ); ?> <?php echo esc_html( $profile->arabic_full_name ); ?></h2>

    <?php if ( ! empty( $warnings ) ) : ?>
    <div class="rsyi-alert rsyi-alert-danger">
        <strong><?php esc_html_e( '⚠ تنبيهات تستوجب إجراءً منك:', 'rsyi-sa' ); ?></strong>
        <?php foreach ( $warnings as $w ) : ?>
        <div class="rsyi-warning-item">
            <p>
                <?php printf(
                    esc_html__( 'وصلت إلى %d نقطة في سجلك السلوكي. يرجى الإقرار بهذا التحذير.', 'rsyi-sa' ),
                    (int) $w->threshold
                ); ?>
            </p>
            <button class="button rsyi-ack-btn" data-warning-id="<?php echo esc_attr( $w->id ); ?>">
                <?php esc_html_e( 'إقرار ومتابعة ✍', 'rsyi-sa' ); ?>
            </button>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="rsyi-info-cards">
        <div class="rsyi-info-card">
            <span class="rsyi-info-label"><?php esc_html_e( 'الفوج', 'rsyi-sa' ); ?></span>
            <span class="rsyi-info-value"><?php echo esc_html( $cohort->name ?? '—' ); ?></span>
        </div>
        <div class="rsyi-info-card rsyi-status-<?php echo esc_attr( $profile->status ); ?>">
            <span class="rsyi-info-label"><?php esc_html_e( 'حالة الحساب', 'rsyi-sa' ); ?></span>
            <span class="rsyi-info-value"><?php echo esc_html( $status_labels[ $profile->status ] ?? $profile->status ); ?></span>
        </div>
        <div class="rsyi-info-card <?php echo $total_pts >= 30 ? 'rsyi-pts-danger' : ( $total_pts >= 20 ? 'rsyi-pts-warning' : '' ); ?>">
            <span class="rsyi-info-label"><?php esc_html_e( 'نقاط السلوك', 'rsyi-sa' ); ?></span>
            <span class="rsyi-info-value"><?php echo esc_html( $total_pts ); ?> / 40</span>
        </div>
    </div>

    <div class="rsyi-portal-links">
        <a class="rsyi-portal-link" href="<?php echo esc_url( home_url( '/portal/documents/' ) ); ?>">
            📄 <?php esc_html_e( 'وثائقي', 'rsyi-sa' ); ?>
        </a>
        <a class="rsyi-portal-link" href="<?php echo esc_url( home_url( '/portal/requests/' ) ); ?>">
            📝 <?php esc_html_e( 'طلباتي', 'rsyi-sa' ); ?>
        </a>
        <a class="rsyi-portal-link" href="<?php echo esc_url( home_url( '/portal/behavior/' ) ); ?>">
            📊 <?php esc_html_e( 'سجلي السلوكي', 'rsyi-sa' ); ?>
        </a>
    </div>
</div>

<script>
jQuery(function($){
    $('.rsyi-ack-btn').on('click', function(){
        var btn = $(this);
        var id  = btn.data('warning-id');
        if(!confirm('<?php echo esc_js( __( 'هل تقر بأنك اطلعت على هذا التحذير؟', 'rsyi-sa' ) ); ?>')) return;
        $.post(rsyiPortal.ajaxUrl, {
            action: 'rsyi_acknowledge_warning',
            _nonce: rsyiPortal.nonce,
            warning_id: id
        }, function(res){
            if(res.success){
                btn.closest('.rsyi-warning-item').html('<p class="rsyi-success">✅ ' + res.data.message + '</p>');
            } else {
                alert(res.data.message);
            }
        });
    });
});
</script>
