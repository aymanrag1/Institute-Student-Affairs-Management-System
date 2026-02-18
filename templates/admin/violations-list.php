<?php
/**
 * Admin Violations List
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

global $wpdb;

$student_id = (int) ( $_GET['student_id'] ?? 0 );
$where  = '1=1';
$params = [];
if ( $student_id ) {
    $where   .= ' AND v.student_id = %d';
    $params[] = $student_id;
}
$params[] = 30;
$params[] = 0;

$violations = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT v.*, vt.name_ar AS type_ar, vt.name_en AS type_en,
                sp.arabic_full_name, u.display_name AS assigned_by_name
         FROM {$wpdb->prefix}rsyi_violations v
         JOIN {$wpdb->prefix}rsyi_violation_types vt ON vt.id = v.violation_type_id
         JOIN {$wpdb->prefix}rsyi_student_profiles sp ON sp.id = v.student_id
         JOIN {$wpdb->users} u ON u.ID = v.assigned_by
         WHERE {$where}
         ORDER BY v.incident_date DESC
         LIMIT %d OFFSET %d",
        ...$params
    )
);

$violation_types = \RSYI_SA\Modules\Behavior::get_all_violation_types();
?>
<h1><?php esc_html_e( 'المخالفات السلوكية', 'rsyi-sa' ); ?></h1>

<?php if ( current_user_can( 'rsyi_create_violation' ) ) : ?>
<div class="rsyi-card" style="margin-bottom:20px;">
    <h2><?php esc_html_e( 'تسجيل مخالفة جديدة', 'rsyi-sa' ); ?></h2>
    <table class="form-table">
        <tr>
            <th><?php esc_html_e( 'رقم ملف الطالب', 'rsyi-sa' ); ?></th>
            <td><input type="number" id="viol_student_id" class="regular-text" value="<?php echo esc_attr( $student_id ); ?>"></td>
        </tr>
        <tr>
            <th><?php esc_html_e( 'نوع المخالفة', 'rsyi-sa' ); ?></th>
            <td>
                <select id="viol_type_id">
                    <option value=""><?php esc_html_e( '— اختر —', 'rsyi-sa' ); ?></option>
                    <?php foreach ( $violation_types as $vt ) : ?>
                        <option value="<?php echo esc_attr( $vt->id ); ?>"
                                data-default="<?php echo esc_attr( $vt->default_points ); ?>"
                                data-max="<?php echo esc_attr( $vt->max_points ); ?>">
                            <?php echo esc_html( $vt->name_ar . ' (' . $vt->name_en . ')' ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><?php esc_html_e( 'النقاط', 'rsyi-sa' ); ?></th>
            <td>
                <input type="number" id="viol_points" min="1" max="30" class="small-text" value="5">
                <span id="viol_pts_hint" style="margin-right:8px; color:#666;"></span>
            </td>
        </tr>
        <tr>
            <th><?php esc_html_e( 'تاريخ الحادثة', 'rsyi-sa' ); ?></th>
            <td><input type="date" id="viol_date" value="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>"></td>
        </tr>
        <tr>
            <th><?php esc_html_e( 'الوصف', 'rsyi-sa' ); ?></th>
            <td><textarea id="viol_description" rows="3" class="large-text"></textarea></td>
        </tr>
    </table>
    <button id="rsyi_submit_violation" class="button button-primary"><?php esc_html_e( 'تسجيل المخالفة', 'rsyi-sa' ); ?></button>
    <span id="viol_status" style="margin-right:10px;"></span>
</div>
<?php endif; ?>

<table class="widefat rsyi-table striped">
    <thead>
        <tr>
            <th><?php esc_html_e( 'الطالب', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'نوع المخالفة', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'النقاط', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'التاريخ', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'الحالة', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'المسجّل بواسطة', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'إجراءات', 'rsyi-sa' ); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php if ( empty( $violations ) ) : ?>
        <tr><td colspan="7"><?php esc_html_e( 'لا توجد مخالفات.', 'rsyi-sa' ); ?></td></tr>
    <?php else : ?>
        <?php foreach ( $violations as $v ) : ?>
        <tr class="<?php echo $v->status === 'overturned' ? 'rsyi-row-muted' : ''; ?>">
            <td><?php echo esc_html( $v->arabic_full_name ); ?></td>
            <td><?php echo esc_html( $v->type_ar ); ?></td>
            <td><strong><?php echo esc_html( $v->points_assigned ); ?></strong></td>
            <td><?php echo esc_html( $v->incident_date ); ?></td>
            <td>
                <span class="rsyi-badge rsyi-status-<?php echo esc_attr( $v->status ); ?>">
                    <?php echo $v->status === 'active' ? esc_html__( 'نشطة', 'rsyi-sa' ) : esc_html__( 'ملغاة', 'rsyi-sa' ); ?>
                </span>
            </td>
            <td><?php echo esc_html( $v->assigned_by_name ); ?></td>
            <td>
                <?php if ( $v->status === 'active' && current_user_can( 'rsyi_overturn_violation' ) ) : ?>
                <button class="button button-small rsyi-overturn-btn" data-id="<?php echo esc_attr( $v->id ); ?>">
                    <?php esc_html_e( 'إلغاء', 'rsyi-sa' ); ?>
                </button>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>

<script>
jQuery(function($){
    // Auto-fill points when type changes
    $('#viol_type_id').on('change', function(){
        var opt = $(this).find('option:selected');
        var def = opt.data('default') || 5;
        var max = opt.data('max') || 30;
        $('#viol_points').val(def).attr('max', max);
        $('#viol_pts_hint').text('<?php echo esc_js( __( 'الحد الأقصى:', 'rsyi-sa' ) ); ?> ' + max);
    });

    $('#rsyi_submit_violation').on('click', function(){
        var btn = $(this);
        btn.prop('disabled', true);
        $('#viol_status').text('');
        $.post(rsyiSA.ajaxUrl, {
            action:            'rsyi_create_violation',
            _nonce:            rsyiSA.nonce,
            student_id:        $('#viol_student_id').val(),
            violation_type_id: $('#viol_type_id').val(),
            points_assigned:   $('#viol_points').val(),
            incident_date:     $('#viol_date').val(),
            description:       $('#viol_description').val()
        }, function(res){
            btn.prop('disabled', false);
            if(res.success){
                $('#viol_status').text('✅ ' + res.data.message + ' — ' + '<?php echo esc_js( __( 'إجمالي النقاط:', 'rsyi-sa' ) ); ?> ' + res.data.total_points);
                setTimeout(function(){ location.reload(); }, 1500);
            } else {
                $('#viol_status').text('❌ ' + (res.data.message || '<?php echo esc_js( __( 'خطأ', 'rsyi-sa' ) ); ?>'));
            }
        }).fail(function(){ btn.prop('disabled', false); });
    });

    // Overturn
    $(document).on('click', '.rsyi-overturn-btn', function(){
        var id = $(this).data('id');
        var reason = prompt('<?php echo esc_js( __( 'سبب الإلغاء:', 'rsyi-sa' ) ); ?>');
        if(!reason) return;
        $.post(rsyiSA.ajaxUrl, {
            action: 'rsyi_overturn_violation',
            _nonce: rsyiSA.nonce,
            violation_id: id,
            reason: reason
        }, function(res){
            if(res.success){ location.reload(); }
            else { alert(res.data.message); }
        });
    });
});
</script>
