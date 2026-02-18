<?php
/**
 * Admin Settings Template
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

if ( ! current_user_can( 'rsyi_manage_settings' ) ) {
    wp_die( __( 'صلاحية غير كافية.', 'rsyi-sa' ) );
}

$institute_name = get_option( 'rsyi_institute_name', 'معهد البحر الأحمر للتخطيط البحري – الجونة' );
?>
<h1><?php esc_html_e( 'الإعدادات', 'rsyi-sa' ); ?></h1>
<hr class="wp-header-end">

<div class="rsyi-card" style="max-width:640px;">
    <h2 style="margin-top:0;"><?php esc_html_e( 'إعدادات المعهد', 'rsyi-sa' ); ?></h2>

    <table class="form-table" role="presentation">
        <tr>
            <th scope="row">
                <label for="rsyi_institute_name">
                    <?php esc_html_e( 'اسم المعهد', 'rsyi-sa' ); ?>
                </label>
            </th>
            <td>
                <input type="text"
                       id="rsyi_institute_name"
                       name="rsyi_institute_name"
                       value="<?php echo esc_attr( $institute_name ); ?>"
                       class="regular-text"
                       placeholder="<?php esc_attr_e( 'أدخل اسم المعهد', 'rsyi-sa' ); ?>">
                <p class="description">
                    <?php esc_html_e( 'يظهر هذا الاسم في لوحة التحكم وفي تقارير PDF.', 'rsyi-sa' ); ?>
                </p>
            </td>
        </tr>
    </table>

    <p class="submit">
        <button type="button" id="rsyi-save-settings" class="button button-primary">
            <?php esc_html_e( 'حفظ الإعدادات', 'rsyi-sa' ); ?>
        </button>
        <span id="rsyi-settings-status" style="margin-right:12px;display:none;"></span>
    </p>
</div>

<script>
(function($){
    $('#rsyi-save-settings').on('click', function(){
        var btn    = $(this);
        var status = $('#rsyi-settings-status');
        var name   = $('#rsyi_institute_name').val().trim();

        if (!name) {
            status.text('<?php esc_js( esc_html__( 'الاسم لا يمكن أن يكون فارغاً.', 'rsyi-sa' ) ); ?>').css('color','#c0392b').show();
            return;
        }

        btn.prop('disabled', true);
        status.text('<?php esc_js( esc_html__( 'جاري الحفظ…', 'rsyi-sa' ) ); ?>').css('color','#666').show();

        $.post(rsyiSA.ajaxUrl, {
            action: 'rsyi_save_settings',
            _nonce: rsyiSA.nonce,
            rsyi_institute_name: name
        }, function(res) {
            btn.prop('disabled', false);
            if (res.success) {
                status.text(res.data.message).css('color','#1a7a4a').show();
                setTimeout(function(){ status.fadeOut(); }, 3000);
            } else {
                status.text(res.data.message || '<?php esc_js( esc_html__( 'حدث خطأ.', 'rsyi-sa' ) ); ?>').css('color','#c0392b').show();
            }
        }).fail(function(){
            btn.prop('disabled', false);
            status.text('<?php esc_js( esc_html__( 'فشل الاتصال.', 'rsyi-sa' ) ); ?>').css('color','#c0392b').show();
        });
    });
}(jQuery));
</script>
