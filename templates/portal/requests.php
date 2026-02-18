<?php
/**
 * Portal – Requests (Exit + Overnight Permits)
 * Variables: $profile, $exit_permits, $overnight_permits
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

$status_labels = [
    'pending_dorm'       => __( 'انتظار مشرف السكن', 'rsyi-sa' ),
    'pending_manager'    => __( 'انتظار مدير شؤون الطلاب', 'rsyi-sa' ),
    'pending_supervisor' => __( 'انتظار المشرف الأكاديمي', 'rsyi-sa' ),
    'pending_dean'       => __( 'انتظار العميد', 'rsyi-sa' ),
    'approved'           => __( 'موافق عليه', 'rsyi-sa' ),
    'rejected'           => __( 'مرفوض', 'rsyi-sa' ),
    'executed'           => __( 'منفَّذ', 'rsyi-sa' ),
];
?>
<div class="rsyi-portal" dir="rtl">
    <h2><?php esc_html_e( 'طلباتي', 'rsyi-sa' ); ?></h2>

    <!-- Exit Permit Form -->
    <div class="rsyi-section">
        <h3><?php esc_html_e( 'طلب إذن خروج', 'rsyi-sa' ); ?></h3>
        <form id="rsyi_exit_form">
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'من تاريخ/وقت', 'rsyi-sa' ); ?></th>
                    <td><input type="datetime-local" id="exit_from" name="from_datetime" required></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'إلى تاريخ/وقت', 'rsyi-sa' ); ?></th>
                    <td><input type="datetime-local" id="exit_to" name="to_datetime" required></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'السبب', 'rsyi-sa' ); ?></th>
                    <td><textarea id="exit_reason" name="reason" rows="3" class="large-text" required></textarea></td>
                </tr>
            </table>
            <button type="submit" class="button button-primary"><?php esc_html_e( 'تقديم الطلب', 'rsyi-sa' ); ?></button>
            <span class="rsyi-form-status" id="exit_status"></span>
        </form>
    </div>

    <!-- Overnight Permit Form -->
    <div class="rsyi-section">
        <h3><?php esc_html_e( 'طلب إذن مبيت', 'rsyi-sa' ); ?></h3>
        <form id="rsyi_overnight_form">
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'من تاريخ/وقت', 'rsyi-sa' ); ?></th>
                    <td><input type="datetime-local" id="on_from" name="from_datetime" required></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'إلى تاريخ/وقت', 'rsyi-sa' ); ?></th>
                    <td><input type="datetime-local" id="on_to" name="to_datetime" required></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'السبب', 'rsyi-sa' ); ?></th>
                    <td><textarea id="on_reason" name="reason" rows="3" class="large-text" required></textarea></td>
                </tr>
            </table>
            <button type="submit" class="button button-primary"><?php esc_html_e( 'تقديم الطلب', 'rsyi-sa' ); ?></button>
            <span class="rsyi-form-status" id="on_status"></span>
        </form>
    </div>

    <!-- Exit Permits History -->
    <div class="rsyi-section">
        <h3><?php esc_html_e( 'أذونات الخروج السابقة', 'rsyi-sa' ); ?></h3>
        <?php if ( empty( $exit_permits ) ) : ?>
            <p><?php esc_html_e( 'لا توجد طلبات.', 'rsyi-sa' ); ?></p>
        <?php else : ?>
        <table class="rsyi-portal-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'من', 'rsyi-sa' ); ?></th>
                    <th><?php esc_html_e( 'إلى', 'rsyi-sa' ); ?></th>
                    <th><?php esc_html_e( 'السبب', 'rsyi-sa' ); ?></th>
                    <th><?php esc_html_e( 'الحالة', 'rsyi-sa' ); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $exit_permits as $p ) : ?>
                <tr>
                    <td><?php echo esc_html( $p->from_datetime ); ?></td>
                    <td><?php echo esc_html( $p->to_datetime ); ?></td>
                    <td><?php echo esc_html( $p->reason ); ?></td>
                    <td>
                        <span class="rsyi-badge rsyi-status-<?php echo esc_attr( $p->status ); ?>">
                            <?php echo esc_html( $status_labels[ $p->status ] ?? $p->status ); ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Overnight Permits History -->
    <div class="rsyi-section">
        <h3><?php esc_html_e( 'أذونات المبيت السابقة', 'rsyi-sa' ); ?></h3>
        <?php if ( empty( $overnight_permits ) ) : ?>
            <p><?php esc_html_e( 'لا توجد طلبات.', 'rsyi-sa' ); ?></p>
        <?php else : ?>
        <table class="rsyi-portal-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'من', 'rsyi-sa' ); ?></th>
                    <th><?php esc_html_e( 'إلى', 'rsyi-sa' ); ?></th>
                    <th><?php esc_html_e( 'السبب', 'rsyi-sa' ); ?></th>
                    <th><?php esc_html_e( 'الحالة', 'rsyi-sa' ); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $overnight_permits as $p ) : ?>
                <tr>
                    <td><?php echo esc_html( $p->from_datetime ); ?></td>
                    <td><?php echo esc_html( $p->to_datetime ); ?></td>
                    <td><?php echo esc_html( $p->reason ); ?></td>
                    <td>
                        <span class="rsyi-badge rsyi-status-<?php echo esc_attr( $p->status ); ?>">
                            <?php echo esc_html( $status_labels[ $p->status ] ?? $p->status ); ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(function($){
    function submitPermit(formId, action, statusId){
        $(formId).on('submit', function(e){
            e.preventDefault();
            var btn = $(this).find('button[type=submit]');
            btn.prop('disabled', true);
            $(statusId).text('');
            var data = $(this).serializeArray().reduce(function(obj, item){ obj[item.name]=item.value; return obj; }, {});
            data.action = action;
            data._nonce = rsyiPortal.nonce;
            $.post(rsyiPortal.ajaxUrl, data, function(res){
                btn.prop('disabled', false);
                if(res.success){
                    $(statusId).text('✅ ' + res.data.message);
                    setTimeout(function(){ location.reload(); }, 1500);
                } else {
                    var msg = res.data.errors ? res.data.errors.join(', ') : (res.data.message || '<?php echo esc_js( __( 'خطأ', 'rsyi-sa' ) ); ?>');
                    $(statusId).text('❌ ' + msg);
                }
            }).fail(function(){ btn.prop('disabled', false); });
        });
    }
    submitPermit('#rsyi_exit_form',      'rsyi_submit_exit_permit',      '#exit_status');
    submitPermit('#rsyi_overnight_form', 'rsyi_submit_overnight_permit', '#on_status');
});
</script>
