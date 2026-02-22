<?php
/**
 * Admin Template: Overnight Permit Detail
 * Variables: $permit_id (int)
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

global $wpdb;
$permit = $wpdb->get_row( $wpdb->prepare(
    "SELECT op.*, sp.english_full_name, sp.arabic_full_name, sp.id AS profile_id
     FROM {$wpdb->prefix}rsyi_overnight_permits op
     LEFT JOIN {$wpdb->prefix}rsyi_student_profiles sp ON sp.id = op.student_id
     WHERE op.id = %d",
    $permit_id
) );

if ( ! $permit ) {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'Permit not found.', 'rsyi-sa' ) . '</p></div>';
    return;
}

$status_map = [
    'pending_supervisor' => [ 'label' => __( 'Pending Supervisor', 'rsyi-sa' ), 'color' => '#f39c12' ],
    'pending_manager'    => [ 'label' => __( 'Pending SA Manager', 'rsyi-sa' ), 'color' => '#3498db' ],
    'pending_dean'       => [ 'label' => __( 'Pending Dean', 'rsyi-sa' ),       'color' => '#9b59b6' ],
    'approved'           => [ 'label' => __( 'Approved', 'rsyi-sa' ),           'color' => '#27ae60' ],
    'rejected'           => [ 'label' => __( 'Rejected', 'rsyi-sa' ),           'color' => '#e74c3c' ],
    'executed'           => [ 'label' => __( 'Executed', 'rsyi-sa' ),           'color' => '#8e44ad' ],
];
$si = $status_map[ $permit->status ] ?? [ 'label' => $permit->status, 'color' => '#999' ];

$supervisor_user = $permit->supervisor_id ? get_userdata( $permit->supervisor_id ) : null;
$manager_user    = $permit->manager_id    ? get_userdata( $permit->manager_id )    : null;
$dean_user       = $permit->dean_id       ? get_userdata( $permit->dean_id )       : null;

$steps = [
    [
        'label'       => __( 'Student Supervisor', 'rsyi-sa' ),
        'approved_at' => $permit->supervisor_approved_at,
        'rejected_at' => $permit->supervisor_rejected_at,
        'notes'       => $permit->supervisor_notes,
        'user'        => $supervisor_user,
        'step'        => 1,
        'pending_status' => 'pending_supervisor',
        'approve_action' => 'rsyi_approve_overnight_permit',
        'reject_action'  => 'rsyi_reject_overnight_permit',
    ],
    [
        'label'       => __( 'Student Affairs Manager', 'rsyi-sa' ),
        'approved_at' => $permit->manager_approved_at,
        'rejected_at' => $permit->manager_rejected_at,
        'notes'       => $permit->manager_notes,
        'user'        => $manager_user,
        'step'        => 2,
        'pending_status' => 'pending_manager',
        'approve_action' => 'rsyi_approve_overnight_permit',
        'reject_action'  => 'rsyi_reject_overnight_permit',
    ],
    [
        'label'       => __( 'Dean', 'rsyi-sa' ),
        'approved_at' => $permit->dean_approved_at,
        'rejected_at' => $permit->dean_rejected_at,
        'notes'       => $permit->dean_notes,
        'user'        => $dean_user,
        'step'        => 3,
        'pending_status' => 'pending_dean',
        'approve_action' => 'rsyi_approve_overnight_permit',
        'reject_action'  => 'rsyi_reject_overnight_permit',
    ],
];
?>
<h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=rsyi-overnight' ) ); ?>"
       style="font-size:14px; color:#555; text-decoration:none;">
        ← <?php esc_html_e( 'Back to Overnight Permits', 'rsyi-sa' ); ?>
    </a>
</h1>
<h2 style="display:flex; align-items:center; gap:14px; margin-top:6px;">
    <?php printf( esc_html__( 'Overnight Permit #%d', 'rsyi-sa' ), $permit->id ); ?>
    <span style="background:<?php echo esc_attr( $si['color'] ); ?>; color:#fff; padding:4px 14px; border-radius:20px; font-size:14px;">
        <?php echo esc_html( $si['label'] ); ?>
    </span>
</h2>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-top:16px;">

    <!-- Permit Details -->
    <div style="background:#fff; border:1px solid #ccd0d4; border-radius:6px; padding:20px;">
        <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:8px;"><?php esc_html_e( 'Permit Details', 'rsyi-sa' ); ?></h3>
        <table style="width:100%; border-collapse:collapse;">
        <?php
        $rows = [
            [ __( 'Student', 'rsyi-sa' ),   $permit->english_full_name . ' (' . $permit->arabic_full_name . ')' ],
            [ __( 'From', 'rsyi-sa' ),      date_i18n( 'M j, Y H:i', strtotime( $permit->from_datetime ) ) ],
            [ __( 'To', 'rsyi-sa' ),        date_i18n( 'M j, Y H:i', strtotime( $permit->to_datetime ) ) ],
            [ __( 'Reason', 'rsyi-sa' ),    $permit->reason ],
            [ __( 'Submitted', 'rsyi-sa' ), date_i18n( 'M j, Y H:i', strtotime( $permit->created_at ) ) ],
        ];
        foreach ( $rows as [$lbl, $val] ) : ?>
        <tr>
            <td style="padding:7px 10px 7px 0; font-weight:600; color:#555; width:35%;"><?php echo esc_html( $lbl ); ?></td>
            <td style="padding:7px 0;"><?php echo esc_html( $val ); ?></td>
        </tr>
        <?php endforeach; ?>
        </table>
        <div style="margin-top:12px;">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=rsyi-students&action=view&id=' . $permit->profile_id ) ); ?>" class="button button-small">
                <?php esc_html_e( 'View Student', 'rsyi-sa' ); ?>
            </a>
        </div>
    </div>

    <!-- 3-Step Workflow -->
    <div style="background:#fff; border:1px solid #ccd0d4; border-radius:6px; padding:20px;">
        <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:8px;"><?php esc_html_e( '3-Step Approval Workflow', 'rsyi-sa' ); ?></h3>

        <?php foreach ( $steps as $step ) :
            $color = $step['approved_at'] ? '#27ae60' : ( $step['rejected_at'] ? '#e74c3c' : '#ccc' );
            $icon  = $step['approved_at'] ? '✓' : ( $step['rejected_at'] ? '✗' : $step['step'] );
        ?>
        <div style="display:flex; gap:12px; margin-bottom:16px;">
            <div style="width:36px; height:36px; border-radius:50%; background:<?php echo esc_attr( $color ); ?>;
                        color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; flex-shrink:0;">
                <?php echo esc_html( $icon ); ?>
            </div>
            <div>
                <strong><?php echo esc_html( $step['label'] ); ?></strong>
                <?php if ( $step['approved_at'] ) : ?>
                    <p style="margin:2px 0 0; color:#27ae60; font-size:13px;">
                        <?php echo esc_html__( 'Approved', 'rsyi-sa' ) . ' – ' . ( $step['user'] ? $step['user']->display_name : '—' ) . ' – ' . date_i18n( 'M j, Y', strtotime( $step['approved_at'] ) ); ?>
                    </p>
                <?php elseif ( $step['rejected_at'] ) : ?>
                    <p style="margin:2px 0 0; color:#e74c3c; font-size:13px;">
                        <?php echo esc_html__( 'Rejected', 'rsyi-sa' ) . ' – ' . date_i18n( 'M j, Y', strtotime( $step['rejected_at'] ) ); ?>
                        <?php if ( $step['notes'] ) : ?><br><em><?php echo esc_html( $step['notes'] ); ?></em><?php endif; ?>
                    </p>
                <?php else : ?>
                    <p style="margin:2px 0 0; color:#999; font-size:13px;"><?php esc_html_e( 'Pending…', 'rsyi-sa' ); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Action buttons for current step -->
        <?php if ( current_user_can( 'rsyi_approve_overnight_permit' ) && in_array( $permit->status, [ 'pending_supervisor', 'pending_manager', 'pending_dean' ], true ) ) : ?>
        <div style="display:flex; gap:10px; margin-top:8px; border-top:1px solid #eee; padding-top:14px;">
            <button class="button button-primary rsyi-op-approve" data-id="<?php echo esc_attr( $permit->id ); ?>">
                <?php esc_html_e( 'Approve', 'rsyi-sa' ); ?>
            </button>
            <button class="button rsyi-op-reject" data-id="<?php echo esc_attr( $permit->id ); ?>" style="color:#e74c3c;">
                <?php esc_html_e( 'Reject', 'rsyi-sa' ); ?>
            </button>
        </div>
        <?php elseif ( $permit->status === 'approved' && current_user_can( 'rsyi_approve_exit_permit' ) ) : ?>
        <button class="button button-primary rsyi-op-execute" data-id="<?php echo esc_attr( $permit->id ); ?>">
            <?php esc_html_e( 'Mark as Executed', 'rsyi-sa' ); ?>
        </button>
        <?php endif; ?>
        <span id="rsyi-op-msg" style="display:none; margin-top:10px; display:block;"></span>
    </div>
</div>

<script>
jQuery(function($){
    function op_action(action_slug, id, notes){
        $.post(rsyiSA.ajaxUrl, { action: action_slug, _nonce: rsyiSA.nonce, permit_id: id, notes: notes || '' }, function(res){
            if(res.success){ location.reload(); }
            else { $('#rsyi-op-msg').show().css('color','red').text(res.data.message); }
        });
    }
    $('.rsyi-op-approve').on('click', function(){ op_action('rsyi_approve_overnight_permit', $(this).data('id')); });
    $('.rsyi-op-reject').on('click', function(){
        var n = prompt('<?php echo esc_js( __( 'Rejection reason (optional):', 'rsyi-sa' ) ); ?>');
        if(n === null) return;
        op_action('rsyi_reject_overnight_permit', $(this).data('id'), n);
    });
    $('.rsyi-op-execute').on('click', function(){ op_action('rsyi_execute_overnight_permit', $(this).data('id')); });
});
</script>
