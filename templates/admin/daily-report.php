<?php
/**
 * Admin Daily Report PDF Generator
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

$cohorts = \RSYI_SA\Modules\Cohorts::get_all_cohorts( true );
?>
<h1><?php esc_html_e( 'التقرير اليومي – أذونات الخروج والمبيت', 'rsyi-sa' ); ?></h1>
<p><?php esc_html_e( 'أنشئ تقرير PDF مجمّع لأذونات الخروج والمبيت لتاريخ محدد.', 'rsyi-sa' ); ?></p>

<div class="rsyi-card">
    <table class="form-table">
        <tr>
            <th><?php esc_html_e( 'التاريخ', 'rsyi-sa' ); ?></th>
            <td><input type="date" id="rsyi_report_date" value="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th><?php esc_html_e( 'الفوج', 'rsyi-sa' ); ?></th>
            <td>
                <select id="rsyi_report_cohort">
                    <option value="0"><?php esc_html_e( '— كل الأفواج —', 'rsyi-sa' ); ?></option>
                    <?php foreach ( $cohorts as $c ) : ?>
                        <option value="<?php echo esc_attr( $c->id ); ?>"><?php echo esc_html( $c->name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
    </table>
    <p>
        <button id="rsyi_generate_report" class="button button-primary">
            📄 <?php esc_html_e( 'إنشاء التقرير', 'rsyi-sa' ); ?>
        </button>
        <span id="rsyi_report_status" style="margin-right:10px;"></span>
    </p>
</div>

<script>
jQuery(function($){
    $('#rsyi_generate_report').on('click', function(){
        var btn = $(this);
        btn.prop('disabled', true);
        $('#rsyi_report_status').text('<?php echo esc_js( __( 'جارٍ الإنشاء...', 'rsyi-sa' ) ); ?>');

        $.post(rsyiSA.ajaxUrl, {
            action:    'rsyi_generate_daily_report',
            _nonce:    rsyiSA.nonce,
            date:      $('#rsyi_report_date').val(),
            cohort_id: $('#rsyi_report_cohort').val()
        }, function(res){
            btn.prop('disabled', false);
            if(res.success){
                $('#rsyi_report_status').html('<a href="'+res.data.url+'" target="_blank"><?php echo esc_js( __( '⬇ تحميل التقرير', 'rsyi-sa' ) ); ?></a>');
            } else {
                $('#rsyi_report_status').text(res.data.message || '<?php echo esc_js( __( 'حدث خطأ.', 'rsyi-sa' ) ); ?>');
            }
        }).fail(function(){
            btn.prop('disabled', false);
            $('#rsyi_report_status').text('<?php echo esc_js( __( 'فشل الاتصال.', 'rsyi-sa' ) ); ?>');
        });
    });
});
</script>
