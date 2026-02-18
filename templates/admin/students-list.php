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
    'pending_docs' => __( 'انتظار وثائق', 'rsyi-sa' ),
    'active'       => __( 'نشط', 'rsyi-sa' ),
    'suspended'    => __( 'موقوف', 'rsyi-sa' ),
    'expelled'     => __( 'مطرود', 'rsyi-sa' ),
];
?>
<h1 class="wp-heading-inline"><?php esc_html_e( 'الطلاب', 'rsyi-sa' ); ?></h1>
<a href="<?php echo esc_url( admin_url( 'admin.php?page=rsyi-students&action=add' ) ); ?>" class="page-title-action">
    <?php esc_html_e( '+ إضافة طالب', 'rsyi-sa' ); ?>
</a>
<a href="<?php echo esc_url( admin_url( 'admin.php?page=rsyi-students&action=import' ) ); ?>" class="page-title-action">
    📥 <?php esc_html_e( 'استيراد من Excel', 'rsyi-sa' ); ?>
</a>
<hr class="wp-header-end">

<form method="get" class="rsyi-filter-form">
    <input type="hidden" name="page" value="rsyi-students">
    <select name="cohort_id">
        <option value="0"><?php esc_html_e( '— كل الأفواج —', 'rsyi-sa' ); ?></option>
        <?php foreach ( $cohorts as $c ) : ?>
            <option value="<?php echo esc_attr( $c->id ); ?>" <?php selected( $cohort_filter, $c->id ); ?>>
                <?php echo esc_html( $c->name ); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <select name="status">
        <option value=""><?php esc_html_e( '— كل الحالات —', 'rsyi-sa' ); ?></option>
        <?php foreach ( $status_labels as $val => $label ) : ?>
            <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $status_filter, $val ); ?>>
                <?php echo esc_html( $label ); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>"
           placeholder="<?php esc_attr_e( 'بحث...', 'rsyi-sa' ); ?>">
    <button type="submit" class="button"><?php esc_html_e( 'تصفية', 'rsyi-sa' ); ?></button>
</form>

<table class="widefat rsyi-table striped">
    <thead>
        <tr>
            <th><?php esc_html_e( 'الاسم العربي', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'الاسم الإنجليزي', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'البريد الإلكتروني', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'الفوج', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'الحالة', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'النقاط', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'إجراءات', 'rsyi-sa' ); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php if ( empty( $students ) ) : ?>
        <tr><td colspan="7"><?php esc_html_e( 'لا يوجد طلاب.', 'rsyi-sa' ); ?></td></tr>
    <?php else : ?>
        <?php foreach ( $students as $s ) :
            $total_pts = \RSYI_SA\Modules\Behavior::get_total_points( (int) $s->id );
            $pts_class = $total_pts >= 30 ? 'rsyi-pts-danger' : ( $total_pts >= 20 ? 'rsyi-pts-warning' : '' );
        ?>
        <tr>
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
                    <?php esc_html_e( 'عرض', 'rsyi-sa' ); ?>
                </a> |
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=rsyi-documents&student_id=' . $s->id ) ); ?>">
                    <?php esc_html_e( 'وثائق', 'rsyi-sa' ); ?>
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>
