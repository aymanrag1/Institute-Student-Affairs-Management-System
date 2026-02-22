<?php
/**
 * Admin Template: Add Violation
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

global $wpdb;

$violation_types = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}rsyi_violation_types WHERE is_active = 1 ORDER BY name_en ASC"
);

$cohorts  = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}rsyi_cohorts WHERE is_active = 1 ORDER BY name ASC" );
$students = \RSYI_SA\Modules\Accounts::get_all_students( [ 'status' => 'active', 'per_page' => 500 ] );

$max_pts = \RSYI_SA\Roles::get_max_violation_points( wp_get_current_user() );

$preselect_student = absint( $_GET['student_id'] ?? 0 );
?>
<h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=rsyi-violations' ) ); ?>"
       style="font-size:14px; color:#555; text-decoration:none;">
        ← <?php esc_html_e( 'Back to Violations', 'rsyi-sa' ); ?>
    </a>
</h1>
<h2 style="margin-top:6px;"><?php esc_html_e( 'Log New Violation', 'rsyi-sa' ); ?></h2>

<div style="max-width:720px;">
<form id="rsyi-add-violation-form"
      style="background:#fff; border:1px solid #ccd0d4; border-radius:6px; padding:24px;">
    <?php wp_nonce_field( 'rsyi_sa_admin', '_nonce' ); ?>
    <input type="hidden" name="action" value="rsyi_create_violation">

    <table class="form-table">
        <tr>
            <th scope="row"><label for="viol_student"><?php esc_html_e( 'Student', 'rsyi-sa' ); ?> *</label></th>
            <td>
                <select name="student_profile_id" id="viol_student" required style="min-width:300px;">
                    <option value=""><?php esc_html_e( '— Select Student —', 'rsyi-sa' ); ?></option>
                    <?php foreach ( $students as $s ) : ?>
                    <option value="<?php echo esc_attr( $s->id ); ?>"
                            <?php selected( $preselect_student, (int) $s->id ); ?>>
                        <?php echo esc_html( $s->english_full_name . ' – ' . $s->cohort_name ); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="viol_type"><?php esc_html_e( 'Violation Type', 'rsyi-sa' ); ?> *</label></th>
            <td>
                <select name="violation_type_id" id="viol_type" required style="min-width:300px;">
                    <option value=""><?php esc_html_e( '— Select Type —', 'rsyi-sa' ); ?></option>
                    <?php foreach ( $violation_types as $vt ) : ?>
                    <option value="<?php echo esc_attr( $vt->id ); ?>"
                            data-default="<?php echo esc_attr( $vt->default_points ); ?>"
                            data-max="<?php echo esc_attr( min( $vt->max_points, $max_pts ) ); ?>"
                            <?php echo ( $vt->requires_dean && ! current_user_can( 'rsyi_approve_expulsion' ) ) ? 'disabled' : ''; ?>>
                        <?php echo esc_html( $vt->name_en ); ?>
                        (<?php echo esc_html__( 'default', 'rsyi-sa' ); ?>: <?php echo esc_html( $vt->default_points ); ?> <?php esc_html_e( 'pts', 'rsyi-sa' ); ?>)
                        <?php if ( $vt->requires_dean ) echo ' – Dean only'; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="viol_points"><?php esc_html_e( 'Points', 'rsyi-sa' ); ?> *</label></th>
            <td>
                <input type="number" name="points_assigned" id="viol_points"
                       min="1" max="<?php echo esc_attr( $max_pts ); ?>" value="1"
                       required style="width:80px;">
                <span style="color:#555; font-size:13px;">
                    <?php printf( esc_html__( 'Max allowed for your role: %d', 'rsyi-sa' ), $max_pts ); ?>
                </span>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="viol_date"><?php esc_html_e( 'Incident Date', 'rsyi-sa' ); ?> *</label></th>
            <td><input type="date" name="incident_date" id="viol_date" required value="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>"></td>
        </tr>
        <tr>
            <th scope="row"><label for="viol_desc"><?php esc_html_e( 'Description', 'rsyi-sa' ); ?></label></th>
            <td><textarea name="description" id="viol_desc" rows="3" class="large-text"></textarea></td>
        </tr>
    </table>

    <p>
        <button type="submit" class="button button-primary button-large" id="rsyi-viol-submit">
            <?php esc_html_e( 'Log Violation', 'rsyi-sa' ); ?>
        </button>
        <span id="rsyi-viol-msg" style="margin-left:12px; display:none;"></span>
    </p>
</form>
</div>

<script>
jQuery(function($){
    // Auto-fill default points when type changes
    $('#viol_type').on('change', function(){
        var $opt = $(this).find('option:selected');
        var def  = parseInt($opt.data('default'), 10) || 1;
        var max  = parseInt($opt.data('max'), 10) || <?php echo esc_js( $max_pts ); ?>;
        $('#viol_points').attr('max', max).val(Math.min(def, max));
    });

    $('#rsyi-add-violation-form').on('submit', function(e){
        e.preventDefault();
        var $btn = $('#rsyi-viol-submit');
        var $msg = $('#rsyi-viol-msg');
        $btn.prop('disabled', true);
        $.post(rsyiSA.ajaxUrl, $(this).serialize(), function(res){
            $btn.prop('disabled', false);
            $msg.show().css('color', res.success ? 'green' : 'red').text(res.data.message);
            if(res.success){
                setTimeout(function(){ window.location = '<?php echo esc_url( admin_url( 'admin.php?page=rsyi-violations' ) ); ?>'; }, 1000);
            }
        });
    });
});
</script>
