<?php
/**
 * Admin Template: Expulsion Case Detail
 * Variables: $case_id (int)
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

global $wpdb;
$case = $wpdb->get_row( $wpdb->prepare(
    "SELECT ec.*, sp.english_full_name, sp.arabic_full_name, sp.id AS profile_id
     FROM {$wpdb->prefix}rsyi_expulsion_cases ec
     LEFT JOIN {$wpdb->prefix}rsyi_student_profiles sp ON sp.id = ec.student_id
     WHERE ec.id = %d",
    $case_id
) );

if ( ! $case ) {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'Case not found.', 'rsyi-sa' ) . '</p></div>';
    return;
}

$status_map = [
    'pending_dean' => [ 'label' => __( 'Pending Dean Decision', 'rsyi-sa' ), 'color' => '#f39c12' ],
    'approved'     => [ 'label' => __( 'Approved – Expelled', 'rsyi-sa' ),   'color' => '#e74c3c' ],
    'rejected'     => [ 'label' => __( 'Rejected – Overturned', 'rsyi-sa' ), 'color' => '#27ae60' ],
];
$si = $status_map[ $case->status ] ?? [ 'label' => $case->status, 'color' => '#999' ];
$dean_user = $case->dean_id ? get_userdata( $case->dean_id ) : null;
?>
<h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=rsyi-expulsion' ) ); ?>"
       style="font-size:14px; color:#555; text-decoration:none;">
        ← <?php esc_html_e( 'Back to Expulsion Cases', 'rsyi-sa' ); ?>
    </a>
</h1>
<h2 style="display:flex; align-items:center; gap:14px; margin-top:6px;">
    <?php printf( esc_html__( 'Expulsion Case #%d', 'rsyi-sa' ), $case->id ); ?>
    <span style="background:<?php echo esc_attr( $si['color'] ); ?>; color:#fff; padding:4px 14px; border-radius:20px; font-size:14px;">
        <?php echo esc_html( $si['label'] ); ?>
    </span>
</h2>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-top:16px;">

    <!-- Case Details -->
    <div style="background:#fff; border:1px solid #ccd0d4; border-radius:6px; padding:20px;">
        <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:8px;"><?php esc_html_e( 'Case Details', 'rsyi-sa' ); ?></h3>
        <table style="width:100%; border-collapse:collapse;">
        <?php
        $rows = [
            [ __( 'Student', 'rsyi-sa' ),       $case->english_full_name . ' (' . $case->arabic_full_name . ')' ],
            [ __( 'Total Points', 'rsyi-sa' ),  $case->total_points . ' / 40' ],
            [ __( 'Triggered By', 'rsyi-sa' ),  $case->triggered_by === '40_points' ? __( 'Reached 40 behavior points', 'rsyi-sa' ) : $case->triggered_by ],
            [ __( 'Created', 'rsyi-sa' ),        date_i18n( 'M j, Y H:i', strtotime( $case->created_at ) ) ],
        ];
        if ( $case->dean_decided_at ) {
            $rows[] = [ __( 'Decided By', 'rsyi-sa' ), $dean_user ? $dean_user->display_name : '—' ];
            $rows[] = [ __( 'Decision Date', 'rsyi-sa' ), date_i18n( 'M j, Y', strtotime( $case->dean_decided_at ) ) ];
        }
        if ( $case->dean_notes ) {
            $rows[] = [ __( 'Dean Notes', 'rsyi-sa' ), $case->dean_notes ];
        }
        foreach ( $rows as [$lbl, $val] ) : ?>
        <tr>
            <td style="padding:7px 10px 7px 0; font-weight:600; color:#555; width:40%;"><?php echo esc_html( $lbl ); ?></td>
            <td style="padding:7px 0;"><?php echo esc_html( $val ); ?></td>
        </tr>
        <?php endforeach; ?>
        </table>
        <div style="margin-top:14px; display:flex; gap:8px;">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=rsyi-students&action=view&id=' . $case->profile_id ) ); ?>"
               class="button button-small"><?php esc_html_e( 'View Student', 'rsyi-sa' ); ?></a>
            <?php if ( $case->letter_path && file_exists( $case->letter_path ) ) : ?>
            <a href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=rsyi_download_expulsion_letter&case_id=' . $case->id . '&nonce=' . wp_create_nonce( 'rsyi_download_letter_' . $case->id ) ) ); ?>"
               class="button button-small"><?php esc_html_e( 'Download Letter', 'rsyi-sa' ); ?></a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Dean Action -->
    <div style="background:#fff; border:1px solid #ccd0d4; border-radius:6px; padding:20px;">
        <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:8px;"><?php esc_html_e( 'Dean Decision', 'rsyi-sa' ); ?></h3>

        <?php if ( $case->status === 'pending_dean' && current_user_can( 'rsyi_approve_expulsion' ) ) : ?>
        <p style="color:#555;"><?php esc_html_e( 'Review the case and approve or reject the expulsion.', 'rsyi-sa' ); ?></p>
        <label for="dean_notes" style="font-weight:600; display:block; margin-bottom:6px;"><?php esc_html_e( 'Notes', 'rsyi-sa' ); ?></label>
        <textarea id="dean_notes" rows="4" class="large-text" style="width:100%; margin-bottom:12px;"></textarea>
        <div style="display:flex; gap:10px;">
            <button class="button button-primary" id="rsyi-exp-approve" data-id="<?php echo esc_attr( $case->id ); ?>" style="background:#e74c3c; border-color:#c0392b;">
                <?php esc_html_e( 'Approve Expulsion', 'rsyi-sa' ); ?>
            </button>
            <button class="button" id="rsyi-exp-reject" data-id="<?php echo esc_attr( $case->id ); ?>" style="color:#27ae60; border-color:#27ae60;">
                <?php esc_html_e( 'Reject / Overturn', 'rsyi-sa' ); ?>
            </button>
        </div>
        <span id="rsyi-exp-msg" style="display:none; margin-top:10px; display:block;"></span>

        <?php elseif ( $case->status === 'approved' ) : ?>
        <div style="text-align:center; padding:20px 0;">
            <div style="font-size:48px;">🚫</div>
            <p style="font-size:16px; font-weight:700; color:#e74c3c;"><?php esc_html_e( 'Expulsion Approved', 'rsyi-sa' ); ?></p>
            <p style="color:#555;"><?php esc_html_e( 'Student has been expelled.', 'rsyi-sa' ); ?></p>
            <?php if ( current_user_can( 'rsyi_manage_expulsion' ) && ! $case->letter_path ) : ?>
            <button class="button button-primary" id="rsyi-gen-letter" data-id="<?php echo esc_attr( $case->id ); ?>">
                <?php esc_html_e( 'Generate Expulsion Letter', 'rsyi-sa' ); ?>
            </button>
            <?php endif; ?>
        </div>

        <?php else : ?>
        <div style="text-align:center; padding:20px 0;">
            <div style="font-size:48px;">✅</div>
            <p style="font-size:16px; font-weight:700; color:#27ae60;"><?php esc_html_e( 'Case Rejected – Student Not Expelled', 'rsyi-sa' ); ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(function($){
    function exp_action(action_slug, case_id, notes){
        $.post(rsyiSA.ajaxUrl, { action: action_slug, _nonce: rsyiSA.nonce, case_id: case_id, notes: notes || '' }, function(res){
            if(res.success){ location.reload(); }
            else { $('#rsyi-exp-msg').show().css('color','red').text(res.data.message); }
        });
    }
    $('#rsyi-exp-approve').on('click', function(){
        if(!confirm('<?php echo esc_js( __( 'Are you sure you want to approve this expulsion?', 'rsyi-sa' ) ); ?>')) return;
        exp_action('rsyi_approve_expulsion', $(this).data('id'), $('#dean_notes').val());
    });
    $('#rsyi-exp-reject').on('click', function(){
        exp_action('rsyi_reject_expulsion', $(this).data('id'), $('#dean_notes').val());
    });
    $('#rsyi-gen-letter').on('click', function(){
        var $btn = $(this);
        $btn.prop('disabled', true).text('<?php echo esc_js( __( 'Generating…', 'rsyi-sa' ) ); ?>');
        $.post(rsyiSA.ajaxUrl, { action: 'rsyi_generate_expulsion_letter', _nonce: rsyiSA.nonce, case_id: $btn.data('id') }, function(res){
            if(res.success){ location.reload(); }
            else { $btn.prop('disabled', false).text('<?php echo esc_js( __( 'Generate Expulsion Letter', 'rsyi-sa' ) ); ?>'); alert(res.data.message); }
        });
    });
});
</script>
