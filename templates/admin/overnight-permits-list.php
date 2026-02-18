<?php
/**
 * Admin – Overnight Permits List
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

global $wpdb;

$status_filter = sanitize_key( $_GET['status'] ?? '' );
$page_num      = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
$per_page      = 25;
$offset        = ( $page_num - 1 ) * $per_page;

$where  = '1=1';
$params = [];

if ( $status_filter ) {
    $where   .= ' AND op.status = %s';
    $params[] = $status_filter;
}

$params[] = $per_page;
$params[] = $offset;

$permits = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT op.*, sp.arabic_full_name, sp.id AS profile_id
         FROM {$wpdb->prefix}rsyi_overnight_permits op
         JOIN {$wpdb->prefix}rsyi_student_profiles sp ON sp.id = op.student_id
         WHERE {$where}
         ORDER BY FIELD(op.status,'pending_supervisor','pending_manager','pending_dean','approved','executed','rejected'), op.created_at DESC
         LIMIT %d OFFSET %d",
        ...$params
    )
);

$status_labels = [
    'pending_supervisor' => __( 'انتظار المشرف الأكاديمي', 'rsyi-sa' ),
    'pending_manager'    => __( 'انتظار مدير شؤون الطلاب', 'rsyi-sa' ),
    'pending_dean'       => __( 'انتظار العميد', 'rsyi-sa' ),
    'approved'           => __( 'مُوافق عليه', 'rsyi-sa' ),
    'executed'           => __( 'منفَّذ', 'rsyi-sa' ),
    'rejected'           => __( 'مرفوض', 'rsyi-sa' ),
];

$pending_statuses = [ 'pending_supervisor', 'pending_manager', 'pending_dean' ];
?>
<h1 class="wp-heading-inline"><?php esc_html_e( 'أذونات المبيت', 'rsyi-sa' ); ?></h1>
<hr class="wp-header-end">

<form method="get" class="rsyi-filter-form">
    <input type="hidden" name="page" value="rsyi-overnight">
    <select name="status">
        <option value=""><?php esc_html_e( '— كل الحالات —', 'rsyi-sa' ); ?></option>
        <?php foreach ( $status_labels as $val => $label ) : ?>
            <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $status_filter, $val ); ?>>
                <?php echo esc_html( $label ); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="button"><?php esc_html_e( 'تصفية', 'rsyi-sa' ); ?></button>
</form>

<table class="widefat rsyi-table striped">
    <thead>
        <tr>
            <th>#</th>
            <th><?php esc_html_e( 'الطالب', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'من', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'إلى', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'السبب', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'الحالة', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'إجراءات', 'rsyi-sa' ); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php if ( empty( $permits ) ) : ?>
        <tr><td colspan="7"><?php esc_html_e( 'لا توجد أذونات.', 'rsyi-sa' ); ?></td></tr>
    <?php else : ?>
        <?php foreach ( $permits as $p ) :
            $is_pending = in_array( $p->status, $pending_statuses, true );
        ?>
        <tr>
            <td><?php echo esc_html( $p->id ); ?></td>
            <td>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=rsyi-students&action=view&id=' . $p->profile_id ) ); ?>">
                    <?php echo esc_html( $p->arabic_full_name ); ?>
                </a>
            </td>
            <td><?php echo esc_html( wp_date( 'Y-m-d H:i', strtotime( $p->from_datetime ) ) ); ?></td>
            <td><?php echo esc_html( wp_date( 'Y-m-d H:i', strtotime( $p->to_datetime ) ) ); ?></td>
            <td style="max-width:200px;"><?php echo esc_html( wp_trim_words( $p->reason, 12, '…' ) ); ?></td>
            <td>
                <span class="rsyi-badge rsyi-status-<?php echo esc_attr( $p->status ); ?>">
                    <?php echo esc_html( $status_labels[ $p->status ] ?? $p->status ); ?>
                </span>
            </td>
            <td>
                <?php if ( $is_pending && current_user_can( 'rsyi_approve_overnight_permit' ) ) : ?>
                <button class="button button-small rsyi-overnight-approve-btn"
                        data-id="<?php echo esc_attr( $p->id ); ?>"
                        data-confirm-msg="<?php esc_attr_e( 'هل تؤكد الموافقة؟', 'rsyi-sa' ); ?>">
                    ✅ <?php esc_html_e( 'موافقة', 'rsyi-sa' ); ?>
                </button>
                <button class="button button-small rsyi-overnight-reject-btn"
                        data-id="<?php echo esc_attr( $p->id ); ?>">
                    ❌ <?php esc_html_e( 'رفض', 'rsyi-sa' ); ?>
                </button>
                <?php elseif ( $p->status === 'approved' && current_user_can( 'rsyi_approve_exit_permit' ) ) : ?>
                <button class="button button-small rsyi-overnight-execute-btn"
                        data-id="<?php echo esc_attr( $p->id ); ?>"
                        data-confirm-msg="<?php esc_attr_e( 'تأكيد تنفيذ الإذن؟', 'rsyi-sa' ); ?>">
                    🛌 <?php esc_html_e( 'تنفيذ', 'rsyi-sa' ); ?>
                </button>
                <?php else : ?>
                    <span>—</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>
