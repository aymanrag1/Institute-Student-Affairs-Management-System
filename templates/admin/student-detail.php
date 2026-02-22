<?php
/**
 * Admin Template: Student Detail
 * Variables: $student_id (int)
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

use RSYI_SA\Modules\Accounts;
use RSYI_SA\Modules\Documents;
use RSYI_SA\Modules\Behavior;

$profile = Accounts::get_profile_by_id( $student_id );
if ( ! $profile ) {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'Student not found.', 'rsyi-sa' ) . '</p></div>';
    return;
}

$user    = get_userdata( $profile->user_id );
$doc_map = Documents::get_student_documents_map( (int) $profile->id );
$total_pts = Behavior::get_total_points( (int) $profile->id );

global $wpdb;
$cohort = $wpdb->get_row( $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}rsyi_cohorts WHERE id = %d",
    $profile->cohort_id
) );

$violations = $wpdb->get_results( $wpdb->prepare(
    "SELECT v.*, vt.name_en AS type_en FROM {$wpdb->prefix}rsyi_violations v
     JOIN {$wpdb->prefix}rsyi_violation_types vt ON vt.id = v.violation_type_id
     WHERE v.student_id = %d AND v.status = 'active'
     ORDER BY v.incident_date DESC LIMIT 5",
    $profile->id
) );

$status_labels = [
    'pending_docs' => __( 'Pending Documents', 'rsyi-sa' ),
    'active'       => __( 'Active', 'rsyi-sa' ),
    'suspended'    => __( 'Suspended', 'rsyi-sa' ),
    'expelled'     => __( 'Expelled', 'rsyi-sa' ),
];
$badge_colors = [
    'pending_docs' => '#f39c12',
    'active'       => '#27ae60',
    'suspended'    => '#e67e22',
    'expelled'     => '#e74c3c',
];

$approved_docs = count( array_filter( $doc_map, fn( $d ) => $d && $d->status === 'approved' ) );
$total_docs    = count( Accounts::MANDATORY_DOC_TYPES );
?>
<h1 style="display:flex; align-items:center; gap:14px;">
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=rsyi-students' ) ); ?>"
       style="font-size:14px; color:#555; text-decoration:none;">
        ← <?php esc_html_e( 'Back to Students', 'rsyi-sa' ); ?>
    </a>
    <span><?php echo esc_html( $profile->english_full_name ); ?></span>
    <span style="background:<?php echo esc_attr( $badge_colors[ $profile->status ] ?? '#999' ); ?>;
                 color:#fff; padding:4px 14px; border-radius:20px; font-size:14px; font-weight:700;">
        <?php echo esc_html( $status_labels[ $profile->status ] ?? $profile->status ); ?>
    </span>
</h1>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-top:16px;">

    <!-- Profile Info -->
    <div style="background:#fff; border:1px solid #ccd0d4; border-radius:6px; padding:20px;">
        <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:8px;">
            <?php esc_html_e( 'Profile Information', 'rsyi-sa' ); ?>
        </h3>
        <table style="width:100%; border-collapse:collapse;">
            <?php
            $rows = [
                [ __( 'English Name', 'rsyi-sa' ),   $profile->english_full_name ],
                [ __( 'Arabic Name', 'rsyi-sa' ),    $profile->arabic_full_name ],
                [ __( 'Email', 'rsyi-sa' ),          $user ? $user->user_email : '—' ],
                [ __( 'National ID', 'rsyi-sa' ),    $profile->national_id_number ?: '—' ],
                [ __( 'Date of Birth', 'rsyi-sa' ),  $profile->date_of_birth ? date_i18n( 'M j, Y', strtotime( $profile->date_of_birth ) ) : '—' ],
                [ __( 'Phone', 'rsyi-sa' ),          $profile->phone ?: '—' ],
                [ __( 'Cohort', 'rsyi-sa' ),         $cohort ? $cohort->name . ' (' . $cohort->code . ')' : '—' ],
                [ __( 'Registered', 'rsyi-sa' ),     date_i18n( 'M j, Y', strtotime( $profile->created_at ) ) ],
            ];
            foreach ( $rows as [$label, $value] ) : ?>
            <tr>
                <td style="padding:7px 10px 7px 0; color:#555; white-space:nowrap; font-weight:600; width:40%;"><?php echo esc_html( $label ); ?></td>
                <td style="padding:7px 0;"><?php echo esc_html( $value ); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php if ( current_user_can( 'rsyi_edit_student' ) ) : ?>
        <?php
        $all_cohorts = $wpdb->get_results( "SELECT id, name, code FROM {$wpdb->prefix}rsyi_cohorts WHERE is_active = 1 ORDER BY name ASC" );
        ?>
        <div style="margin-top:14px;">
            <button type="button" class="button button-primary" id="rsyi-edit-toggle">
                <?php esc_html_e( 'تعديل بيانات الطالب', 'rsyi-sa' ); ?>
            </button>
        </div>
        <form id="rsyi-edit-form" style="display:none; margin-top:16px; border-top:1px solid #eee; padding-top:14px;">
            <table class="form-table" style="margin:0;">
                <tr>
                    <th><?php esc_html_e( 'الاسم العربي', 'rsyi-sa' ); ?></th>
                    <td><input type="text" name="arabic_full_name" class="regular-text" dir="rtl"
                               value="<?php echo esc_attr( $profile->arabic_full_name ); ?>" required></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'الاسم الإنجليزي', 'rsyi-sa' ); ?></th>
                    <td><input type="text" name="english_full_name" class="regular-text"
                               value="<?php echo esc_attr( $profile->english_full_name ); ?>" required></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'رقم الهوية القومية', 'rsyi-sa' ); ?></th>
                    <td><input type="text" name="national_id_number" class="regular-text"
                               value="<?php echo esc_attr( $profile->national_id_number ); ?>"></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'رقم الهاتف', 'rsyi-sa' ); ?></th>
                    <td><input type="tel" name="phone" class="regular-text"
                               value="<?php echo esc_attr( $profile->phone ); ?>"></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'تاريخ الميلاد', 'rsyi-sa' ); ?></th>
                    <td><input type="date" name="date_of_birth"
                               value="<?php echo esc_attr( $profile->date_of_birth ); ?>"></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'الفوج', 'rsyi-sa' ); ?></th>
                    <td>
                        <select name="cohort_id" style="min-width:200px;">
                            <?php foreach ( $all_cohorts as $c ) : ?>
                            <option value="<?php echo esc_attr( $c->id ); ?>"
                                <?php selected( (int) $profile->cohort_id, (int) $c->id ); ?>>
                                <?php echo esc_html( $c->name . ' (' . $c->code . ')' ); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            <input type="hidden" name="action" value="rsyi_update_profile">
            <input type="hidden" name="profile_id" value="<?php echo esc_attr( $profile->id ); ?>">
            <input type="hidden" name="_nonce" value="<?php echo esc_attr( wp_create_nonce( 'rsyi_sa_admin' ) ); ?>">
            <p>
                <button type="submit" class="button button-primary" id="rsyi-edit-submit">
                    <?php esc_html_e( 'حفظ التغييرات', 'rsyi-sa' ); ?>
                </button>
                <button type="button" class="button" id="rsyi-edit-cancel">
                    <?php esc_html_e( 'إلغاء', 'rsyi-sa' ); ?>
                </button>
                <span id="rsyi-edit-msg" style="margin-right:10px;"></span>
            </p>
        </form>
        <?php endif; ?>
    </div>

    <!-- Quick Stats -->
    <div style="display:flex; flex-direction:column; gap:16px;">
        <!-- Documents -->
        <div style="background:#fff; border:1px solid #ccd0d4; border-radius:6px; padding:20px;">
            <h3 style="margin-top:0;"><?php esc_html_e( 'Documents', 'rsyi-sa' ); ?></h3>
            <div style="font-size:32px; font-weight:700; color:<?php echo $approved_docs === $total_docs ? '#27ae60' : '#f39c12'; ?>;">
                <?php echo esc_html( $approved_docs . ' / ' . $total_docs ); ?>
            </div>
            <p style="color:#555; margin:4px 0 12px;"><?php esc_html_e( 'documents approved', 'rsyi-sa' ); ?></p>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=rsyi-documents&student_id=' . $profile->id ) ); ?>" class="button">
                <?php esc_html_e( 'Manage Documents', 'rsyi-sa' ); ?>
            </a>
        </div>
        <!-- Behavior Points -->
        <div style="background:#fff; border:1px solid #ccd0d4; border-radius:6px; padding:20px;">
            <h3 style="margin-top:0;"><?php esc_html_e( 'Behavior Points', 'rsyi-sa' ); ?></h3>
            <div style="font-size:32px; font-weight:700; color:<?php echo $total_pts >= 30 ? '#e74c3c' : ( $total_pts >= 20 ? '#e67e22' : '#27ae60' ); ?>;">
                <?php echo esc_html( $total_pts ); ?> / 40
            </div>
            <div style="background:#eee; border-radius:8px; height:8px; margin:8px 0 12px; overflow:hidden;">
                <div style="background:<?php echo $total_pts >= 30 ? '#e74c3c' : '#f39c12'; ?>; width:<?php echo min( 100, round( ( $total_pts / 40 ) * 100 ) ); ?>%; height:100%;"></div>
            </div>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=rsyi-violations' ) ); ?>" class="button">
                <?php esc_html_e( 'View Violations', 'rsyi-sa' ); ?>
            </a>
        </div>
    </div>
</div>

<!-- Recent Violations -->
<?php if ( ! empty( $violations ) ) : ?>
<div style="background:#fff; border:1px solid #ccd0d4; border-radius:6px; padding:20px; margin-top:20px;">
    <h3 style="margin-top:0;"><?php esc_html_e( 'Recent Active Violations', 'rsyi-sa' ); ?></h3>
    <table class="wp-list-table widefat fixed striped" style="margin:0;">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Type', 'rsyi-sa' ); ?></th>
                <th><?php esc_html_e( 'Points', 'rsyi-sa' ); ?></th>
                <th><?php esc_html_e( 'Date', 'rsyi-sa' ); ?></th>
                <th><?php esc_html_e( 'Assigned By', 'rsyi-sa' ); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ( $violations as $v ) :
            $assigner = get_userdata( $v->assigned_by );
        ?>
        <tr>
            <td><?php echo esc_html( $v->type_en ); ?></td>
            <td><strong style="color:#e74c3c;"><?php echo esc_html( $v->points_assigned ); ?></strong></td>
            <td><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $v->incident_date ) ) ); ?></td>
            <td><?php echo esc_html( $assigner ? $assigner->display_name : '—' ); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<script>
jQuery(function($){
    $('#rsyi-edit-toggle').on('click', function(){
        $('#rsyi-edit-form').slideToggle();
        $(this).hide();
    });
    $('#rsyi-edit-cancel').on('click', function(){
        $('#rsyi-edit-form').slideUp();
        $('#rsyi-edit-toggle').show();
    });
    $('#rsyi-edit-form').on('submit', function(e){
        e.preventDefault();
        var $btn = $('#rsyi-edit-submit');
        $btn.prop('disabled', true);
        $.post(rsyiSA.ajaxUrl, $(this).serialize(), function(res){
            $btn.prop('disabled', false);
            $('#rsyi-edit-msg').css('color', res.success ? 'green' : 'red').text(res.data.message);
            if(res.success) setTimeout(function(){ location.reload(); }, 1500);
        });
    });
});
</script>
