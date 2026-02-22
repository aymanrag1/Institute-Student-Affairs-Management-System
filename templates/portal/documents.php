<?php
/**
 * Portal – Documents Upload
 * Variables: $profile, $doc_map (keyed by doc_type), $labels
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

$all_approved = true;
foreach ( $doc_map as $doc ) {
    if ( ! $doc || $doc->status !== 'approved' ) {
        $all_approved = false;
        break;
    }
}

$approved_count = 0;
foreach ( $labels as $type => $label ) {
    $doc = $doc_map[ $type ] ?? null;
    if ( $doc && $doc->status === 'approved' ) $approved_count++;
}
$total_count = count( $labels );
$progress_pct = $total_count > 0 ? round( ( $approved_count / $total_count ) * 100 ) : 0;
?>
<div class="rsyi-portal" dir="ltr" style="font-family:sans-serif; max-width:860px; margin:0 auto;">

    <!-- Header -->
    <div style="background:linear-gradient(135deg,#0073aa,#005177); color:#fff; border-radius:10px; padding:24px 28px; margin-bottom:24px;">
        <h2 style="margin:0 0 4px; font-size:20px;">📋 <?php esc_html_e( 'Required Documents', 'rsyi-sa' ); ?></h2>
        <p style="margin:0; opacity:.85; font-size:14px;">
            <?php esc_html_e( 'Upload all 8 required documents. Each will be reviewed by the administration.', 'rsyi-sa' ); ?>
        </p>
    </div>

    <!-- Progress Bar -->
    <div style="background:#fff; border:1px solid #dee2e6; border-radius:8px; padding:18px 20px; margin-bottom:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
            <strong style="color:#333;"><?php esc_html_e( 'Upload Progress', 'rsyi-sa' ); ?></strong>
            <span style="font-weight:700; color:#0073aa;"><?php echo esc_html( $approved_count ); ?> / <?php echo esc_html( $total_count ); ?> <?php esc_html_e( 'approved', 'rsyi-sa' ); ?></span>
        </div>
        <div style="background:#e9ecef; border-radius:4px; height:10px; overflow:hidden;">
            <div style="background:<?php echo $progress_pct >= 100 ? '#27ae60' : '#0073aa'; ?>; height:100%; width:<?php echo esc_attr( $progress_pct ); ?>%; transition:width .4s;"></div>
        </div>
    </div>

    <?php if ( $all_approved ) : ?>
    <div style="background:#d4edda; border:1px solid #c3e6cb; border-radius:6px; padding:14px 18px; margin-bottom:20px; color:#155724; font-weight:600;">
        ✅ <?php esc_html_e( 'All documents approved. Your account is now active!', 'rsyi-sa' ); ?>
    </div>
    <?php endif; ?>

    <!-- Document Cards Grid -->
    <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:16px;">
    <?php foreach ( $labels as $type => $label ) :
        $doc    = $doc_map[ $type ] ?? null;
        $status = $doc ? $doc->status : 'missing';
        $status_info = [
            'missing'  => [ 'label' => __( 'Not Uploaded', 'rsyi-sa' ),   'color' => '#6c757d', 'bg' => '#f8f9fa', 'border' => '#dee2e6', 'icon' => '📭' ],
            'pending'  => [ 'label' => __( 'Under Review', 'rsyi-sa' ),   'color' => '#856404', 'bg' => '#fff3cd', 'border' => '#ffc107', 'icon' => '⏳' ],
            'approved' => [ 'label' => __( 'Approved', 'rsyi-sa' ),        'color' => '#155724', 'bg' => '#d4edda', 'border' => '#28a745', 'icon' => '✅' ],
            'rejected' => [ 'label' => __( 'Rejected', 'rsyi-sa' ),        'color' => '#721c24', 'bg' => '#f8d7da', 'border' => '#dc3545', 'icon' => '❌' ],
        ][ $status ] ?? [ 'label' => $status, 'color' => '#333', 'bg' => '#fff', 'border' => '#dee2e6', 'icon' => '📄' ];
    ?>
    <div style="background:<?php echo esc_attr( $status_info['bg'] ); ?>; border:2px solid <?php echo esc_attr( $status_info['border'] ); ?>; border-radius:8px; padding:16px;">
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
            <span style="font-size:24px;"><?php echo esc_html( $status_info['icon'] ); ?></span>
            <div>
                <div style="font-weight:700; font-size:13px; color:#333;"><?php echo esc_html( $label ); ?></div>
                <div style="font-size:12px; color:<?php echo esc_attr( $status_info['color'] ); ?>; font-weight:600;"><?php echo esc_html( $status_info['label'] ); ?></div>
            </div>
        </div>

        <?php if ( $doc && $doc->status === 'rejected' && $doc->rejection_reason ) : ?>
        <div style="background:rgba(0,0,0,.05); border-radius:4px; padding:8px; margin-bottom:10px; font-size:12px; color:#721c24;">
            <strong><?php esc_html_e( 'Rejection reason:', 'rsyi-sa' ); ?></strong><br>
            <?php echo esc_html( $doc->rejection_reason ); ?>
        </div>
        <?php endif; ?>

        <?php if ( $doc && $doc->status === 'approved' ) : ?>
        <a href="<?php echo esc_url( \RSYI_SA\Secure_Download::get_url( (int) $doc->id ) ); ?>"
           class="button button-small" target="_blank" style="font-size:12px;">
            👁 <?php esc_html_e( 'View Document', 'rsyi-sa' ); ?>
        </a>
        <?php elseif ( ! $doc || $doc->status === 'rejected' ) : ?>
        <form class="rsyi-upload-form" data-type="<?php echo esc_attr( $type ); ?>">
            <input type="file" name="document_file" accept=".jpg,.jpeg,.png,.webp,.pdf"
                   class="rsyi-file-input" style="font-size:12px; margin-bottom:6px; display:block; width:100%;">
            <button type="submit" class="button button-primary rsyi-upload-btn" style="font-size:12px; width:100%;">
                <?php echo $doc ? esc_html__( 'Re-upload', 'rsyi-sa' ) : esc_html__( 'Upload Document', 'rsyi-sa' ); ?>
            </button>
            <span class="rsyi-upload-status" style="font-size:12px; display:block; margin-top:4px;"></span>
        </form>
        <?php else : ?>
        <span style="font-size:12px; color:#888; font-style:italic;"><?php esc_html_e( 'Awaiting review…', 'rsyi-sa' ); ?></span>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    </div>

    <!-- Back to Dashboard -->
    <div style="margin-top:24px; text-align:right; padding-top:16px; border-top:1px solid #eee;">
        <?php
        $dashboard_url = get_option( 'rsyi_page_dashboard' ) ? get_permalink( get_option( 'rsyi_page_dashboard' ) ) : home_url( '/student-dashboard/' );
        ?>
        <a href="<?php echo esc_url( $dashboard_url ); ?>" style="color:#0073aa; font-size:13px; text-decoration:none;">
            ← <?php esc_html_e( 'Back to Dashboard', 'rsyi-sa' ); ?>
        </a>
    </div>
</div>

<script>
jQuery(function($){
    $('.rsyi-upload-form').on('submit', function(e){
        e.preventDefault();
        var form      = $(this);
        var type      = form.data('type');
        var fileInput = form.find('input[type=file]')[0];
        if ( ! fileInput.files.length ) {
            form.find('.rsyi-upload-status').text('<?php echo esc_js( __( 'Please select a file first.', 'rsyi-sa' ) ); ?>');
            return;
        }
        var fd = new FormData();
        fd.append('action',        'rsyi_upload_document');
        fd.append('_nonce',        rsyiPortal.nonce);
        fd.append('doc_type',      type);
        fd.append('document_file', fileInput.files[0]);

        form.find('.rsyi-upload-btn').prop('disabled', true).text('<?php echo esc_js( __( 'Uploading…', 'rsyi-sa' ) ); ?>');
        form.find('.rsyi-upload-status').text('');

        $.ajax({
            url: rsyiPortal.ajaxUrl,
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function(res){
                if ( res.success ) {
                    form.find('.rsyi-upload-status').css('color','#155724').text('✅ ' + res.data.message);
                    setTimeout(function(){ location.reload(); }, 1200);
                } else {
                    form.find('.rsyi-upload-btn').prop('disabled', false).text('<?php echo esc_js( __( 'Upload Document', 'rsyi-sa' ) ); ?>');
                    form.find('.rsyi-upload-status').css('color','#721c24').text('❌ ' + (res.data.message || '<?php echo esc_js( __( 'An error occurred.', 'rsyi-sa' ) ); ?>'));
                }
            },
            error: function(){
                form.find('.rsyi-upload-btn').prop('disabled', false).text('<?php echo esc_js( __( 'Upload Document', 'rsyi-sa' ) ); ?>');
                form.find('.rsyi-upload-status').css('color','#721c24').text('<?php echo esc_js( __( 'Connection failed.', 'rsyi-sa' ) ); ?>');
            }
        });
    });
});
</script>
