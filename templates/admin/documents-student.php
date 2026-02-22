<?php
/**
 * Admin Template: Student Documents
 * Variables: $student_id (int)
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

use RSYI_SA\Modules\Accounts;
use RSYI_SA\Modules\Documents;

$profile  = Accounts::get_profile_by_id( $student_id );
if ( ! $profile ) {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'Student not found.', 'rsyi-sa' ) . '</p></div>';
    return;
}

$user     = get_userdata( $profile->user_id );
$doc_map  = Documents::get_student_documents_map( (int) $profile->id );
$labels   = Accounts::DOC_TYPE_LABELS;

global $wpdb;
$cohort = $wpdb->get_row( $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}rsyi_cohorts WHERE id = %d",
    $profile->cohort_id
) );

$back_url      = admin_url( 'admin.php?page=rsyi-documents' );
$student_url   = admin_url( 'admin.php?page=rsyi-students&action=view&id=' . $profile->id );

$status_colors = [
    'pending'  => '#f39c12',
    'approved' => '#27ae60',
    'rejected' => '#e74c3c',
];
?>
<h1>
    <a href="<?php echo esc_url( $back_url ); ?>" style="text-decoration:none; color:#555; font-size:14px;">
        ← <?php esc_html_e( 'Back to Documents', 'rsyi-sa' ); ?>
    </a>
</h1>

<!-- Student Info Card -->
<div style="background:#fff; border:1px solid #ccd0d4; border-radius:6px; padding:20px; margin:16px 0; display:flex; gap:24px; align-items:center;">
    <?php echo get_avatar( $profile->user_id, 64, '', '', [ 'style' => 'border-radius:50%;' ] ); ?>
    <div>
        <h2 style="margin:0 0 4px;"><?php echo esc_html( $profile->english_full_name ); ?></h2>
        <p style="margin:0; color:#555;">
            <?php echo esc_html( $profile->arabic_full_name ); ?> &nbsp;|&nbsp;
            <?php echo esc_html( $user ? $user->user_email : '—' ); ?> &nbsp;|&nbsp;
            <?php esc_html_e( 'Cohort:', 'rsyi-sa' ); ?> <strong><?php echo esc_html( $cohort ? $cohort->name : '—' ); ?></strong>
        </p>
        <p style="margin:4px 0 0;">
            <?php
            $status_labels = [
                'pending_docs' => __( 'Pending Documents', 'rsyi-sa' ),
                'active'       => __( 'Active', 'rsyi-sa' ),
                'suspended'    => __( 'Suspended', 'rsyi-sa' ),
                'expelled'     => __( 'Expelled', 'rsyi-sa' ),
            ];
            $sc = $status_labels[ $profile->status ] ?? $profile->status;
            $badge_colors = [ 'pending_docs' => '#f39c12', 'active' => '#27ae60', 'suspended' => '#e67e22', 'expelled' => '#e74c3c' ];
            $bc = $badge_colors[ $profile->status ] ?? '#999';
            ?>
            <span style="background:<?php echo esc_attr( $bc ); ?>; color:#fff; padding:3px 12px; border-radius:20px; font-size:12px; font-weight:700;">
                <?php echo esc_html( $sc ); ?>
            </span>
            &nbsp;
            <a href="<?php echo esc_url( $student_url ); ?>" class="button button-small">
                <?php esc_html_e( 'View Full Profile', 'rsyi-sa' ); ?>
            </a>
        </p>
    </div>
</div>

<!-- Progress Summary -->
<?php
$approved_count = 0;
foreach ( Accounts::MANDATORY_DOC_TYPES as $dt ) {
    if ( isset( $doc_map[ $dt ] ) && $doc_map[ $dt ]->status === 'approved' ) {
        $approved_count++;
    }
}
$total_required = count( Accounts::MANDATORY_DOC_TYPES );
$pct = $total_required > 0 ? round( ( $approved_count / $total_required ) * 100 ) : 0;
?>
<div style="background:#f8f9fa; border:1px solid #dee2e6; border-radius:6px; padding:16px; margin-bottom:20px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
        <span style="font-weight:700;"><?php esc_html_e( 'Document Completion', 'rsyi-sa' ); ?></span>
        <span style="font-weight:700; color:<?php echo $pct === 100 ? '#27ae60' : '#f39c12'; ?>;">
            <?php echo esc_html( $approved_count . ' / ' . $total_required ); ?> <?php esc_html_e( 'approved', 'rsyi-sa' ); ?>
        </span>
    </div>
    <div style="background:#ddd; border-radius:10px; height:10px; overflow:hidden;">
        <div style="background:<?php echo $pct === 100 ? '#27ae60' : '#f39c12'; ?>; width:<?php echo esc_attr( $pct ); ?>%; height:100%; transition:width .3s;"></div>
    </div>
</div>

<!-- Documents Grid -->
<div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(340px, 1fr)); gap:16px;">
<?php foreach ( Accounts::MANDATORY_DOC_TYPES as $doc_type ) :
    $doc   = $doc_map[ $doc_type ] ?? null;
    $label = $labels[ $doc_type ] ?? $doc_type;
    $status = $doc ? $doc->status : 'missing';
    $sc_color = $status_colors[ $status ] ?? '#999';
?>
<div style="background:#fff; border:1px solid #dee2e6; border-radius:8px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.06);">
    <!-- Card header -->
    <div style="background:<?php echo esc_attr( $sc_color ); ?>; color:#fff; padding:10px 16px; display:flex; justify-content:space-between; align-items:center;">
        <strong style="font-size:13px;"><?php echo esc_html( $label ); ?></strong>
        <span style="font-size:11px; font-weight:700; text-transform:uppercase;">
            <?php echo esc_html( $status === 'missing' ? __( 'Not Uploaded', 'rsyi-sa' ) : ucfirst( $status ) ); ?>
        </span>
    </div>
    <!-- Card body -->
    <div style="padding:14px 16px;">
        <?php if ( $doc ) : ?>
            <p style="margin:0 0 8px; font-size:13px; color:#555;">
                <strong><?php esc_html_e( 'File:', 'rsyi-sa' ); ?></strong>
                <?php echo esc_html( $doc->file_name_orig ?? basename( $doc->file_path ) ); ?>
            </p>
            <p style="margin:0 0 8px; font-size:13px; color:#555;">
                <strong><?php esc_html_e( 'Uploaded:', 'rsyi-sa' ); ?></strong>
                <?php echo esc_html( date_i18n( 'M j, Y', strtotime( $doc->created_at ) ) ); ?>
            </p>
            <?php if ( $doc->status === 'rejected' && $doc->rejection_reason ) : ?>
            <p style="margin:0 0 8px; font-size:13px; color:#e74c3c;">
                <strong><?php esc_html_e( 'Rejection reason:', 'rsyi-sa' ); ?></strong>
                <?php echo esc_html( $doc->rejection_reason ); ?>
            </p>
            <?php endif; ?>
            <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:10px;">
                <?php
                $dl_url = admin_url( 'admin-ajax.php?action=rsyi_secure_download&doc_id=' . $doc->id . '&nonce=' . wp_create_nonce( 'rsyi_download_' . $doc->id ) );
                ?>
                <a href="<?php echo esc_url( $dl_url ); ?>" target="_blank" class="button button-small">
                    <?php esc_html_e( 'View File', 'rsyi-sa' ); ?>
                </a>
                <?php if ( $doc->status === 'pending' && current_user_can( 'rsyi_approve_document' ) ) : ?>
                <button type="button" class="button button-small rsyi-approve-doc button-primary"
                        data-doc="<?php echo esc_attr( $doc->id ); ?>">
                    <?php esc_html_e( 'Approve', 'rsyi-sa' ); ?>
                </button>
                <button type="button" class="button button-small rsyi-reject-doc"
                        data-doc="<?php echo esc_attr( $doc->id ); ?>"
                        style="color:#e74c3c; border-color:#e74c3c;">
                    <?php esc_html_e( 'Reject', 'rsyi-sa' ); ?>
                </button>
                <?php elseif ( $doc->status === 'approved' ) : ?>
                <span style="color:#27ae60; font-weight:700; font-size:13px;">✓ <?php esc_html_e( 'Approved', 'rsyi-sa' ); ?></span>
                <?php endif; ?>
            </div>
            <span class="rsyi-doc-msg-<?php echo esc_attr( $doc->id ); ?>" style="display:none; font-size:12px; margin-top:6px; display:block;"></span>
        <?php else : ?>
            <p style="color:#999; font-style:italic; margin:0;"><?php esc_html_e( 'No file uploaded yet.', 'rsyi-sa' ); ?></p>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>

<script>
jQuery(function($){
    // Approve document
    $(document).on('click', '.rsyi-approve-doc', function(){
        if( ! confirm('<?php echo esc_js( __( 'Approve this document?', 'rsyi-sa' ) ); ?>') ) return;
        var $btn = $(this);
        var doc_id = $btn.data('doc');
        $.post(rsyiSA.ajaxUrl, {
            action: 'rsyi_approve_document',
            _nonce: rsyiSA.nonce,
            doc_id: doc_id
        }, function(res){
            if(res.success){ location.reload(); }
            else { alert(res.data.message); }
        });
    });

    // Reject document
    $(document).on('click', '.rsyi-reject-doc', function(){
        var reason = prompt('<?php echo esc_js( __( 'Enter rejection reason:', 'rsyi-sa' ) ); ?>');
        if( reason === null ) return;
        var doc_id = $(this).data('doc');
        $.post(rsyiSA.ajaxUrl, {
            action: 'rsyi_reject_document',
            _nonce: rsyiSA.nonce,
            doc_id: doc_id,
            reason: reason
        }, function(res){
            if(res.success){ location.reload(); }
            else { alert(res.data.message); }
        });
    });
});
</script>
