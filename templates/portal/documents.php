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
?>
<div class="rsyi-portal" dir="rtl">
    <h2><?php esc_html_e( 'الوثائق المطلوبة', 'rsyi-sa' ); ?></h2>

    <?php if ( $all_approved ) : ?>
        <div class="rsyi-alert rsyi-alert-success">
            <?php esc_html_e( '✅ جميع وثائقك معتمدة. تم تفعيل حسابك.', 'rsyi-sa' ); ?>
        </div>
    <?php else : ?>
        <p class="rsyi-hint"><?php esc_html_e( 'يرجى رفع جميع الوثائق المطلوبة. ستتم مراجعتها من قِبل الإدارة.', 'rsyi-sa' ); ?></p>
    <?php endif; ?>

    <div class="rsyi-doc-grid">
    <?php foreach ( $labels as $type => $label ) :
        $doc    = $doc_map[ $type ] ?? null;
        $status = $doc ? $doc->status : 'missing';
        $status_label = [
            'missing'  => __( 'غير مرفوع', 'rsyi-sa' ),
            'pending'  => __( 'قيد المراجعة', 'rsyi-sa' ),
            'approved' => __( 'معتمد', 'rsyi-sa' ),
            'rejected' => __( 'مرفوض', 'rsyi-sa' ),
        ][ $status ] ?? $status;
        $icon = match ( $status ) {
            'approved' => '✅', 'rejected' => '❌', 'pending' => '⏳', default => '📭'
        };
    ?>
    <div class="rsyi-doc-card rsyi-doc-<?php echo esc_attr( $status ); ?>">
        <div class="rsyi-doc-header">
            <span class="rsyi-doc-icon"><?php echo esc_html( $icon ); ?></span>
            <span class="rsyi-doc-label"><?php echo esc_html( $label ); ?></span>
            <span class="rsyi-doc-status"><?php echo esc_html( $status_label ); ?></span>
        </div>

        <?php if ( $doc && $doc->status === 'rejected' && $doc->rejection_reason ) : ?>
        <div class="rsyi-doc-rejection">
            <strong><?php esc_html_e( 'سبب الرفض:', 'rsyi-sa' ); ?></strong>
            <?php echo esc_html( $doc->rejection_reason ); ?>
        </div>
        <?php endif; ?>

        <?php if ( $doc && $doc->status === 'approved' ) : ?>
        <a href="<?php echo esc_url( \RSYI_SA\Secure_Download::get_url( (int) $doc->id ) ); ?>"
           class="button button-small" target="_blank">
            <?php esc_html_e( 'عرض', 'rsyi-sa' ); ?>
        </a>
        <?php elseif ( ! $doc || $doc->status === 'rejected' ) : ?>
        <form class="rsyi-upload-form" data-type="<?php echo esc_attr( $type ); ?>">
            <input type="file" name="document_file" accept=".jpg,.jpeg,.png,.webp,.pdf" class="rsyi-file-input">
            <button type="submit" class="button rsyi-upload-btn">
                <?php echo $doc ? esc_html__( 'إعادة الرفع', 'rsyi-sa' ) : esc_html__( 'رفع الوثيقة', 'rsyi-sa' ); ?>
            </button>
            <span class="rsyi-upload-status"></span>
        </form>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    </div>
</div>

<script>
jQuery(function($){
    $('.rsyi-upload-form').on('submit', function(e){
        e.preventDefault();
        var form    = $(this);
        var type    = form.data('type');
        var fileInput = form.find('input[type=file]')[0];
        if(!fileInput.files.length){
            form.find('.rsyi-upload-status').text('<?php echo esc_js( __( 'يرجى اختيار ملف.', 'rsyi-sa' ) ); ?>');
            return;
        }
        var fd = new FormData();
        fd.append('action',        'rsyi_upload_document');
        fd.append('_nonce',        rsyiPortal.nonce);
        fd.append('doc_type',      type);
        fd.append('document_file', fileInput.files[0]);

        form.find('.rsyi-upload-btn').prop('disabled', true);
        form.find('.rsyi-upload-status').text('<?php echo esc_js( __( 'جارٍ الرفع...', 'rsyi-sa' ) ); ?>');

        $.ajax({
            url: rsyiPortal.ajaxUrl,
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function(res){
                if(res.success){
                    form.find('.rsyi-upload-status').text('✅ ' + res.data.message);
                    setTimeout(function(){ location.reload(); }, 1200);
                } else {
                    form.find('.rsyi-upload-btn').prop('disabled', false);
                    form.find('.rsyi-upload-status').text('❌ ' + (res.data.message || '<?php echo esc_js( __( 'حدث خطأ.', 'rsyi-sa' ) ); ?>'));
                }
            },
            error: function(){
                form.find('.rsyi-upload-btn').prop('disabled', false);
                form.find('.rsyi-upload-status').text('<?php echo esc_js( __( 'فشل الاتصال.', 'rsyi-sa' ) ); ?>');
            }
        });
    });
});
</script>
