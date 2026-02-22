<?php
/**
 * Admin Students List
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

$cohort_filter = (int) ( $_GET['cohort_id'] ?? 0 );
$status_filter = sanitize_key( $_GET['status'] ?? '' );
$search        = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) );
$page_num      = max( 1, (int) ( $_GET['paged'] ?? 1 ) );

$students = \RSYI_SA\Modules\Accounts::get_all_students( [
    'cohort_id' => $cohort_filter,
    'status'    => $status_filter,
    'search'    => $search,
    'per_page'  => 20,
    'page'      => $page_num,
] );

$cohorts = \RSYI_SA\Modules\Cohorts::get_all_cohorts();

$status_labels = [
    'pending_docs' => __( 'Pending Documents', 'rsyi-sa' ),
    'active'       => __( 'Active', 'rsyi-sa' ),
    'suspended'    => __( 'Suspended', 'rsyi-sa' ),
    'expelled'     => __( 'Expelled', 'rsyi-sa' ),
];

$can_delete = current_user_can( 'rsyi_delete_student' );
?>
<h1 class="wp-heading-inline"><?php esc_html_e( 'Students', 'rsyi-sa' ); ?></h1>
<a href="<?php echo esc_url( admin_url( 'admin.php?page=rsyi-students&action=add' ) ); ?>" class="page-title-action">
    + <?php esc_html_e( 'Add Student', 'rsyi-sa' ); ?>
</a>
<a href="<?php echo esc_url( admin_url( 'admin.php?page=rsyi-students&action=import' ) ); ?>" class="page-title-action">
    📥 <?php esc_html_e( 'Import from Excel', 'rsyi-sa' ); ?>
</a>
<hr class="wp-header-end">

<form method="get" class="rsyi-filter-form">
    <input type="hidden" name="page" value="rsyi-students">
    <select name="cohort_id">
        <option value="0"><?php esc_html_e( '— All Cohorts —', 'rsyi-sa' ); ?></option>
        <?php foreach ( $cohorts as $c ) : ?>
            <option value="<?php echo esc_attr( $c->id ); ?>" <?php selected( $cohort_filter, $c->id ); ?>>
                <?php echo esc_html( $c->name ); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <select name="status">
        <option value=""><?php esc_html_e( '— All Statuses —', 'rsyi-sa' ); ?></option>
        <?php foreach ( $status_labels as $val => $label ) : ?>
            <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $status_filter, $val ); ?>>
                <?php echo esc_html( $label ); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>"
           placeholder="<?php esc_attr_e( 'Search…', 'rsyi-sa' ); ?>">
    <button type="submit" class="button"><?php esc_html_e( 'Filter', 'rsyi-sa' ); ?></button>
</form>

<div id="rsyi-delete-msg" style="display:none; margin-top:10px;" class="notice"></div>

<table class="widefat rsyi-table striped">
    <thead>
        <tr>
            <th><?php esc_html_e( 'Arabic Name', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'English Name', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'Email', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'Cohort', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'Status', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'Points', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'Actions', 'rsyi-sa' ); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php if ( empty( $students ) ) : ?>
        <tr><td colspan="7"><?php esc_html_e( 'No students found.', 'rsyi-sa' ); ?></td></tr>
    <?php else : ?>
        <?php foreach ( $students as $s ) :
            $total_pts = \RSYI_SA\Modules\Behavior::get_total_points( (int) $s->id );
            $pts_class = $total_pts >= 30 ? 'rsyi-pts-danger' : ( $total_pts >= 20 ? 'rsyi-pts-warning' : '' );
        ?>
        <tr id="student-row-<?php echo esc_attr( $s->id ); ?>">
            <td><?php echo esc_html( $s->arabic_full_name ); ?></td>
            <td><?php echo esc_html( $s->english_full_name ); ?></td>
            <td><?php echo esc_html( $s->user_email ); ?></td>
            <td><?php echo esc_html( $s->cohort_name ); ?></td>
            <td>
                <span class="rsyi-badge rsyi-status-<?php echo esc_attr( $s->status ); ?>">
                    <?php echo esc_html( $status_labels[ $s->status ] ?? $s->status ); ?>
                </span>
            </td>
            <td class="<?php echo esc_attr( $pts_class ); ?>"><?php echo esc_html( $total_pts ); ?></td>
            <td>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=rsyi-students&action=view&id=' . $s->id ) ); ?>">
                    <?php esc_html_e( 'View', 'rsyi-sa' ); ?>
                </a> |
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=rsyi-documents&student_id=' . $s->id ) ); ?>">
                    <?php esc_html_e( 'Documents', 'rsyi-sa' ); ?>
                </a>
                <?php if ( $can_delete ) : ?>
                | <a href="#" class="rsyi-delete-student" style="color:#c0392b;"
                     data-id="<?php echo esc_attr( $s->id ); ?>"
                     data-name="<?php echo esc_attr( $s->english_full_name ); ?>">
                    <?php esc_html_e( 'Delete', 'rsyi-sa' ); ?>
                </a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>

<?php if ( $can_delete ) : ?>
<script>
jQuery(function($){
    $('.rsyi-delete-student').on('click', function(e){
        e.preventDefault();
        var id   = $(this).data('id');
        var name = $(this).data('name');
        if ( ! confirm('<?php echo esc_js( __( 'Are you sure you want to permanently delete student:', 'rsyi-sa' ) ); ?> "' + name + '"?\n\n<?php echo esc_js( __( 'This will delete the student account and all related records. This action cannot be undone.', 'rsyi-sa' ) ); ?>') ) return;

        var $row = $('#student-row-' + id);
        var $msg = $('#rsyi-delete-msg');

        $.post(rsyiSA.ajaxUrl, {
            action     : 'rsyi_delete_student',
            _nonce     : rsyiSA.nonce,
            profile_id : id
        }, function(res){
            if ( res.success ) {
                $row.fadeOut(400, function(){ $(this).remove(); });
                $msg.removeClass('notice-error').addClass('notice-success')
                    .html('<p>✅ ' + res.data.message + '</p>').show();
                setTimeout(function(){ $msg.fadeOut(); }, 3000);
            } else {
                $msg.removeClass('notice-success').addClass('notice-error')
                    .html('<p>❌ ' + (res.data.message || '<?php echo esc_js( __( 'An error occurred.', 'rsyi-sa' ) ); ?>') + '</p>').show();
            }
        }).fail(function(){
            $msg.removeClass('notice-success').addClass('notice-error')
                .html('<p>❌ <?php echo esc_js( __( 'Connection failed.', 'rsyi-sa' ) ); ?></p>').show();
        });
    });
});
</script>
<?php endif; ?>
