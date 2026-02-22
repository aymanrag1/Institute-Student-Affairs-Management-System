<?php
/**
 * Portal – Student Dashboard
 * Variables: $profile, $total_pts, $warnings (array), $cohort
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

$status_labels = [
    'pending_docs' => __( 'Pending Documents', 'rsyi-sa' ),
    'active'       => __( 'Active', 'rsyi-sa' ),
    'suspended'    => __( 'Suspended', 'rsyi-sa' ),
    'expelled'     => __( 'Expelled', 'rsyi-sa' ),
];

// Portal page URLs
$page_links = [
    'documents'  => get_option( 'rsyi_page_documents' )  ? get_permalink( get_option( 'rsyi_page_documents' ) )  : '',
    'requests'   => get_option( 'rsyi_page_requests' )   ? get_permalink( get_option( 'rsyi_page_requests' ) )   : '',
    'behavior'   => get_option( 'rsyi_page_behavior' )   ? get_permalink( get_option( 'rsyi_page_behavior' ) )   : '',
    'evaluation' => get_option( 'rsyi_page_evaluation' ) ? get_permalink( get_option( 'rsyi_page_evaluation' ) ) : '',
];
?>
<div class="rsyi-portal" dir="ltr" style="font-family:sans-serif; max-width:860px; margin:0 auto;">

    <!-- Welcome Header -->
    <div style="background:linear-gradient(135deg,#0073aa,#005177); color:#fff; border-radius:10px; padding:28px 32px; margin-bottom:24px; display:flex; align-items:center; gap:20px;">
        <?php echo get_avatar( $profile->user_id, 64, '', '', [ 'style' => 'border-radius:50%; border:3px solid rgba(255,255,255,.4);' ] ); ?>
        <div>
            <h2 style="margin:0 0 4px; font-size:22px;">
                <?php esc_html_e( 'Welcome,', 'rsyi-sa' ); ?> <?php echo esc_html( $profile->english_full_name ); ?>
            </h2>
            <p style="margin:0; opacity:.85; font-size:14px;">
                <?php echo esc_html( get_option( 'rsyi_institute_name', 'Red Sea Yacht Institute' ) ); ?>
                &nbsp;|&nbsp; <?php esc_html_e( 'Student Portal', 'rsyi-sa' ); ?>
            </p>
        </div>
    </div>

    <!-- Pending Warnings -->
    <?php if ( ! empty( $warnings ) ) : ?>
    <div style="background:#fff3cd; border:1px solid #ffc107; border-left:5px solid #ff9800; border-radius:6px; padding:18px 20px; margin-bottom:20px;">
        <strong style="color:#856404;">⚠ <?php esc_html_e( 'Action Required – Pending Warnings', 'rsyi-sa' ); ?></strong>
        <?php foreach ( $warnings as $w ) : ?>
        <div style="margin-top:12px; padding-top:12px; border-top:1px solid rgba(0,0,0,.1);" class="rsyi-warning-item">
            <p style="margin:0 0 8px; color:#555;">
                <?php printf(
                    esc_html__( 'You have reached %d behavior points. You must acknowledge this warning to continue.', 'rsyi-sa' ),
                    (int) $w->threshold
                ); ?>
            </p>
            <button class="button button-primary rsyi-ack-btn" data-warning-id="<?php echo esc_attr( $w->id ); ?>">
                ✍ <?php esc_html_e( 'Acknowledge & Continue', 'rsyi-sa' ); ?>
            </button>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Status Cards -->
    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:28px;">
        <div style="background:#fff; border:1px solid #dee2e6; border-radius:8px; padding:18px; text-align:center;">
            <div style="font-size:13px; color:#888; margin-bottom:6px; text-transform:uppercase; letter-spacing:.5px;"><?php esc_html_e( 'Cohort', 'rsyi-sa' ); ?></div>
            <div style="font-size:20px; font-weight:700; color:#0073aa;"><?php echo esc_html( isset( $cohort->name ) ? $cohort->name : '—' ); ?></div>
        </div>
        <div style="background:#fff; border:1px solid #dee2e6; border-radius:8px; padding:18px; text-align:center; border-top:3px solid <?php
            $bc = [ 'pending_docs' => '#f39c12', 'active' => '#27ae60', 'suspended' => '#e67e22', 'expelled' => '#e74c3c' ];
            echo esc_attr( $bc[ $profile->status ] ?? '#999' );
        ?>;">
            <div style="font-size:13px; color:#888; margin-bottom:6px; text-transform:uppercase; letter-spacing:.5px;"><?php esc_html_e( 'Account Status', 'rsyi-sa' ); ?></div>
            <div style="font-size:18px; font-weight:700;"><?php echo esc_html( $status_labels[ $profile->status ] ?? $profile->status ); ?></div>
        </div>
        <div style="background:#fff; border:1px solid #dee2e6; border-radius:8px; padding:18px; text-align:center; border-top:3px solid <?php echo $total_pts >= 30 ? '#e74c3c' : ( $total_pts >= 20 ? '#e67e22' : '#27ae60' ); ?>;">
            <div style="font-size:13px; color:#888; margin-bottom:6px; text-transform:uppercase; letter-spacing:.5px;"><?php esc_html_e( 'Behavior Points', 'rsyi-sa' ); ?></div>
            <div style="font-size:24px; font-weight:700; color:<?php echo $total_pts >= 30 ? '#e74c3c' : ( $total_pts >= 20 ? '#e67e22' : '#27ae60' ); ?>;">
                <?php echo esc_html( $total_pts ); ?> <span style="font-size:14px; color:#888;">/ 40</span>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <h3 style="color:#333; margin-bottom:14px;"><?php esc_html_e( 'Quick Access', 'rsyi-sa' ); ?></h3>
    <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:14px;">
        <?php
        $nav_items = [
            [ 'icon' => '📄', 'label' => __( 'My Documents', 'rsyi-sa' ),          'url' => $page_links['documents'],  'desc' => __( 'Upload & track required documents', 'rsyi-sa' ) ],
            [ 'icon' => '📝', 'label' => __( 'Permits & Requests', 'rsyi-sa' ),     'url' => $page_links['requests'],   'desc' => __( 'Exit & overnight permits', 'rsyi-sa' ) ],
            [ 'icon' => '📊', 'label' => __( 'Behavior Record', 'rsyi-sa' ),         'url' => $page_links['behavior'],   'desc' => __( 'View your behavior points', 'rsyi-sa' ) ],
            [ 'icon' => '⭐', 'label' => __( 'Peer Evaluation', 'rsyi-sa' ),         'url' => $page_links['evaluation'], 'desc' => __( 'Rate your cohort members', 'rsyi-sa' ) ],
        ];
        foreach ( $nav_items as $item ) :
            if ( ! $item['url'] ) continue;
        ?>
        <a href="<?php echo esc_url( $item['url'] ); ?>"
           style="background:#fff; border:1px solid #dee2e6; border-radius:8px; padding:20px 16px; text-align:center; text-decoration:none; color:#333; transition:box-shadow .2s; display:block;"
           onmouseover="this.style.boxShadow='0 4px 12px rgba(0,115,170,.2)'; this.style.borderColor='#0073aa';"
           onmouseout="this.style.boxShadow=''; this.style.borderColor='#dee2e6';">
            <div style="font-size:32px; margin-bottom:8px;"><?php echo $item['icon']; ?></div>
            <div style="font-weight:700; margin-bottom:4px; color:#0073aa;"><?php echo esc_html( $item['label'] ); ?></div>
            <div style="font-size:12px; color:#888;"><?php echo esc_html( $item['desc'] ); ?></div>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if ( $profile->status === 'pending_docs' ) : ?>
    <div style="background:#e8f4fd; border:1px solid #90caf9; border-radius:6px; padding:16px 20px; margin-top:20px; display:flex; align-items:center; gap:14px;">
        <span style="font-size:28px;">📋</span>
        <div>
            <strong><?php esc_html_e( 'Documents Required', 'rsyi-sa' ); ?></strong>
            <p style="margin:4px 0 0; color:#555; font-size:14px;">
                <?php esc_html_e( 'Your account will be activated after all 8 required documents are uploaded and approved.', 'rsyi-sa' ); ?>
            </p>
            <?php if ( $page_links['documents'] ) : ?>
            <a href="<?php echo esc_url( $page_links['documents'] ); ?>" class="button button-primary" style="margin-top:10px;">
                <?php esc_html_e( 'Upload Documents Now →', 'rsyi-sa' ); ?>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Logout link -->
    <div style="margin-top:24px; text-align:right; padding-top:16px; border-top:1px solid #eee;">
        <a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" style="color:#999; font-size:13px; text-decoration:none;">
            <?php esc_html_e( '← Sign Out', 'rsyi-sa' ); ?>
        </a>
    </div>
</div>

<script>
jQuery(function($){
    $('.rsyi-ack-btn').on('click', function(){
        var btn = $(this);
        var id  = btn.data('warning-id');
        if(!confirm('<?php echo esc_js( __( 'Do you confirm that you have read and understood this warning?', 'rsyi-sa' ) ); ?>')) return;
        btn.prop('disabled', true);
        $.post(rsyiPortal.ajaxUrl, {
            action:     'rsyi_acknowledge_warning',
            _nonce:     rsyiPortal.nonce,
            warning_id: id
        }, function(res){
            if(res.success){
                btn.closest('.rsyi-warning-item').html('<p style="color:#27ae60; font-weight:700;">✅ ' + res.data.message + '</p>');
            } else {
                btn.prop('disabled', false);
                alert(res.data.message);
            }
        });
    });
});
</script>
