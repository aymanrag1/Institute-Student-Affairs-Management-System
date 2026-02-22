<?php
/**
 * Admin Template: Exit Permit Detail
 * Variables: $permit_id (int)
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

global $wpdb;
$permit = $wpdb->get_row( $wpdb->prepare(
    "SELECT ep.*, sp.english_full_name, sp.arabic_full_name, sp.id AS profile_id
     FROM {$wpdb->prefix}rsyi_exit_permits ep
     LEFT JOIN {$wpdb->prefix}rsyi_student_profiles sp ON sp.id = ep.student_id
     WHERE ep.id = %d",
    $permit_id
) );

if ( ! $permit ) {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'Permit not found.', 'rsyi-sa' ) . '</p></div>';
    return;
}

$status_map = [
    'pending_dorm'    => [ 'label' => __( 'Pending Dorm Supervisor', 'rsyi-sa' ), 'color' => '#f39c12' ],
    'pending_manager' => [ 'label' => __( 'Pending SA Manager', 'rsyi-sa' ),      'color' => '#3498db' ],
    'approved'        => [ 'label' => __( 'Approved', 'rsyi-sa' ),               'color' => '#27ae60' ],
    'rejected'        => [ 'label' => __( 'Rejected', 'rsyi-sa' ),               'color' => '#e74c3c' ],
    'executed'        => [ 'label' => __( 'Executed', 'rsyi-sa' ),               'color' => '#9b59b6' ],
];
$si = $status_map[ $permit->status ] ?? [ 'label' => $permit->status, 'color' => '#999' ];

$dorm_user    = $permit->dorm_supervisor_id ? get_userdata( $permit->dorm_supervisor_id ) : null;
$manager_user = $permit->manager_id ? get_userdata( $permit->manager_id ) : null;
?>
<h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=rsyi-exit' ) ); ?>"
       style="font-size:14px; color:#555; text-decoration:none;">
        ← <?php esc_html_e( 'Back to Exit Permits', 'rsyi-sa' ); ?>
    </a>
</h1>
<h2 style="display:flex; align-items:center; gap:14px; margin-top:6px;">
    <?php printf( esc_html__( 'Exit Permit #%d', 'rsyi-sa' ), $permit->id ); ?>
    <span style="background:<?php echo esc_attr( $si['color'] ); ?>; color:#fff;
                 padding:4px 14px; border-radius:20px; font-size:14px;">
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
            [ __( 'Student', 'rsyi-sa' ),    $permit->english_full_name . ' (' . $permit->arabic_full_name . ')' ],
            [ __( 'From', 'rsyi-sa' ),       date_i18n( 'M j, Y H:i', strtotime( $permit->from_datetime ) ) ],
            [ __( 'To', 'rsyi-sa' ),         date_i18n( 'M j, Y H:i', strtotime( $permit->to_datetime ) ) ],
            [ __( 'Reason', 'rsyi-sa' ),     $permit->reason ],
            [ __( 'Submitted', 'rsyi-sa' ),  date_i18n( 'M j, Y H:i', strtotime( $permit->created_at ) ) ],
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

    <!-- Approval Timeline -->
    <div style="background:#fff; border:1px solid #ccd0d4; border-radius:6px; padding:20px;">
        <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:8px;"><?php esc_html_e( 'Approval Workflow', 'rsyi-sa' ); ?></h3>

        <!-- Step 1: Dorm Supervisor -->
        <div style="display:flex; gap:12px; margin-bottom:16px;">
            <div style="width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; color:#fff;
                        background:<?php echo $permit->dorm_approved_at ? '#27ae60' : ( $permit->dorm_rejected_at ? '#e74c3c' : '#ccc' ); ?>; flex-shrink:0;">
                <?php echo $permit->dorm_approved_at ? '✓' : ( $permit->dorm_rejected_at ? '✗' : '1' ); ?>
            </div>
            <div>
                <strong><?php esc_html_e( 'Dorm Supervisor', 'rsyi-sa' ); ?></strong>
                <?php if ( $permit->dorm_approved_at ) : ?>
                    <p style="margin:2px 0 0; color:#27ae60; font-size:13px;">
                        <?php echo esc_html__( 'Approved', 'rsyi-sa' ) . ' – ' . ( $dorm_user ? $dorm_user->display_name : '—' ) . ' – ' . date_i18n( 'M j, Y', strtotime( $permit->dorm_approved_at ) ); ?>
                    </p>
                <?php elseif ( $permit->dorm_rejected_at ) : ?>
                    <p style="margin:2px 0 0; color:#e74c3c; font-size:13px;">
                        <?php echo esc_html__( 'Rejected', 'rsyi-sa' ) . ' – ' . date_i18n( 'M j, Y', strtotime( $permit->dorm_rejected_at ) ); ?>
                        <?php if ( $permit->dorm_notes ) : ?><br><em><?php echo esc_html( $permit->dorm_notes ); ?></em><?php endif; ?>
                    </p>
                <?php else : ?>
                    <p style="margin:2px 0 0; color:#999; font-size:13px;"><?php esc_html_e( 'Pending…', 'rsyi-sa' ); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Step 2: SA Manager -->
        <div style="display:flex; gap:12px; margin-bottom:20px;">
            <div style="width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; color:#fff;
                        background:<?php echo $permit->manager_approved_at ? '#27ae60' : ( $permit->manager_rejected_at ? '#e74c3c' : '#ccc' ); ?>; flex-shrink:0;">
                <?php echo $permit->manager_approved_at ? '✓' : ( $permit->manager_rejected_at ? '✗' : '2' ); ?>
            </div>
            <div>
                <strong><?php esc_html_e( 'Student Affairs Manager', 'rsyi-sa' ); ?></strong>
                <?php if ( $permit->manager_approved_at ) : ?>
                    <p style="margin:2px 0 0; color:#27ae60; font-size:13px;">
                        <?php echo esc_html__( 'Approved', 'rsyi-sa' ) . ' – ' . ( $manager_user ? $manager_user->display_name : '—' ) . ' – ' . date_i18n( 'M j, Y', strtotime( $permit->manager_approved_at ) ); ?>
                    </p>
                <?php elseif ( $permit->manager_rejected_at ) : ?>
                    <p style="margin:2px 0 0; color:#e74c3c; font-size:13px;">
                        <?php echo esc_html__( 'Rejected', 'rsyi-sa' ) . ' – ' . date_i18n( 'M j, Y', strtotime( $permit->manager_rejected_at ) ); ?>
                        <?php if ( $permit->manager_notes ) : ?><br><em><?php echo esc_html( $permit->manager_notes ); ?></em><?php endif; ?>
                    </p>
                <?php else : ?>
                    <p style="margin:2px 0 0; color:#999; font-size:13px;"><?php esc_html_e( 'Pending…', 'rsyi-sa' ); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Approve / Reject buttons -->
        <?php if ( $permit->status === 'pending_dorm' && current_user_can( 'rsyi_approve_exit_permit' ) ) : ?>
        <div style="display:flex; gap:10px;">
            <button class="button button-primary rsyi-ep-approve" data-id="<?php echo esc_attr( $permit->id ); ?>"><?php esc_html_e( 'Approve (Dorm)', 'rsyi-sa' ); ?></button>
            <button class="button rsyi-ep-reject"  data-id="<?php echo esc_attr( $permit->id ); ?>" style="color:#e74c3c;"><?php esc_html_e( 'Reject', 'rsyi-sa' ); ?></button>
        </div>
        <?php elseif ( $permit->status === 'pending_manager' && current_user_can( 'rsyi_approve_exit_permit' ) ) : ?>
        <div style="display:flex; gap:10px;">
            <button class="button button-primary rsyi-ep-approve" data-id="<?php echo esc_attr( $permit->id ); ?>"><?php esc_html_e( 'Approve (Manager)', 'rsyi-sa' ); ?></button>
            <button class="button rsyi-ep-reject"  data-id="<?php echo esc_attr( $permit->id ); ?>" style="color:#e74c3c;"><?php esc_html_e( 'Reject', 'rsyi-sa' ); ?></button>
        </div>
        <?php elseif ( $permit->status === 'approved' && current_user_can( 'rsyi_approve_exit_permit' ) ) : ?>
        <button class="button button-primary rsyi-ep-execute" data-id="<?php echo esc_attr( $permit->id ); ?>"><?php esc_html_e( 'Mark as Executed', 'rsyi-sa' ); ?></button>
        <?php endif; ?>
        <span id="rsyi-ep-msg" style="display:none; margin-top:10px; display:block;"></span>
    </div>
</div>

<script>
jQuery(function($){
    function ep_action(action_slug, id, notes){
        $.post(rsyiSA.ajaxUrl, { action: action_slug, _nonce: rsyiSA.nonce, permit_id: id, notes: notes || '' }, function(res){
            if(res.success){ location.reload(); }
            else { $('#rsyi-ep-msg').show().css('color','red').text(res.data.message); }
        });
    }
    $('.rsyi-ep-approve').on('click', function(){ ep_action('rsyi_approve_exit_permit', $(this).data('id')); });
    $('.rsyi-ep-reject').on('click', function(){
        var n = prompt('<?php echo esc_js( __( 'Rejection reason (optional):', 'rsyi-sa' ) ); ?>');
        if(n === null) return;
        ep_action('rsyi_reject_exit_permit', $(this).data('id'), n);
    });
    $('.rsyi-ep-execute').on('click', function(){ ep_action('rsyi_execute_exit_permit', $(this).data('id')); });
});
</script>
