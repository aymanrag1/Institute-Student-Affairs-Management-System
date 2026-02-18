<?php
/**
 * Admin – All Documents List
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

global $wpdb;

$status_filter = sanitize_key( $_GET['status'] ?? 'pending' );
$search        = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) );
$page_num      = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
$per_page      = 30;
$offset        = ( $page_num - 1 ) * $per_page;

$where  = '1=1';
$params = [];

if ( $status_filter && $status_filter !== 'all' ) {
    $where   .= ' AND d.status = %s';
    $params[] = $status_filter;
}
if ( $search ) {
    $like     = '%' . $wpdb->esc_like( $search ) . '%';
    $where   .= ' AND (sp.arabic_full_name LIKE %s OR sp.english_full_name LIKE %s)';
    $params[] = $like;
    $params[] = $like;
}

$params[] = $per_page;
$params[] = $offset;

$docs = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT d.*, sp.arabic_full_name, sp.english_full_name, sp.id AS profile_id,
                u.display_name AS reviewed_by_name
         FROM {$wpdb->prefix}rsyi_documents d
         JOIN {$wpdb->prefix}rsyi_student_profiles sp ON sp.id = d.student_id
         LEFT JOIN {$wpdb->users} u ON u.ID = d.reviewed_by
         WHERE {$where}
         ORDER BY FIELD(d.status,'pending','rejected','approved'), d.created_at DESC
         LIMIT %d OFFSET %d",
        ...$params
    )
);

$doc_type_labels = \RSYI_SA\Modules\Accounts::DOC_TYPE_LABELS;

$status_labels = [
    'pending'  => __( 'قيد المراجعة', 'rsyi-sa' ),
    'approved' => __( 'مقبول', 'rsyi-sa' ),
    'rejected' => __( 'مرفوض', 'rsyi-sa' ),
];
?>
<h1 class="wp-heading-inline"><?php esc_html_e( 'الوثائق', 'rsyi-sa' ); ?></h1>
<hr class="wp-header-end">

<form method="get" class="rsyi-filter-form">
    <input type="hidden" name="page" value="rsyi-documents">
    <select name="status">
        <option value="all"  <?php selected( $status_filter, 'all' ); ?>><?php esc_html_e( '— كل الحالات —', 'rsyi-sa' ); ?></option>
        <option value="pending"  <?php selected( $status_filter, 'pending' ); ?>><?php esc_html_e( 'قيد المراجعة', 'rsyi-sa' ); ?></option>
        <option value="approved" <?php selected( $status_filter, 'approved' ); ?>><?php esc_html_e( 'مقبول', 'rsyi-sa' ); ?></option>
        <option value="rejected" <?php selected( $status_filter, 'rejected' ); ?>><?php esc_html_e( 'مرفوض', 'rsyi-sa' ); ?></option>
    </select>
    <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>"
           placeholder="<?php esc_attr_e( 'بحث باسم الطالب…', 'rsyi-sa' ); ?>">
    <button type="submit" class="button"><?php esc_html_e( 'تصفية', 'rsyi-sa' ); ?></button>
</form>

<table class="widefat rsyi-table striped">
    <thead>
        <tr>
            <th><?php esc_html_e( 'الطالب', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'نوع الوثيقة', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'الملف', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'الحالة', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'تاريخ الرفع', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'مراجع بواسطة', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'إجراءات', 'rsyi-sa' ); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php if ( empty( $docs ) ) : ?>
        <tr><td colspan="7"><?php esc_html_e( 'لا توجد وثائق.', 'rsyi-sa' ); ?></td></tr>
    <?php else : ?>
        <?php foreach ( $docs as $d ) : ?>
        <tr class="<?php echo $d->status === 'rejected' ? 'rsyi-row-muted' : ''; ?>">
            <td>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=rsyi-students&action=view&id=' . $d->profile_id ) ); ?>">
                    <?php echo esc_html( $d->arabic_full_name ); ?>
                </a>
            </td>
            <td><?php echo esc_html( $doc_type_labels[ $d->doc_type ] ?? $d->doc_type ); ?></td>
            <td>
                <a href="<?php echo esc_url( \RSYI_SA\Secure_Download::get_url( (int) $d->id ) ); ?>"
                   target="_blank" class="button button-small">
                    📄 <?php esc_html_e( 'عرض', 'rsyi-sa' ); ?>
                </a>
            </td>
            <td>
                <span class="rsyi-badge rsyi-status-<?php echo esc_attr( $d->status ); ?>">
                    <?php echo esc_html( $status_labels[ $d->status ] ?? $d->status ); ?>
                </span>
                <?php if ( $d->status === 'rejected' && $d->rejection_reason ) : ?>
                    <br><small style="color:#c0392b;"><?php echo esc_html( $d->rejection_reason ); ?></small>
                <?php endif; ?>
            </td>
            <td><?php echo esc_html( wp_date( 'Y-m-d', strtotime( $d->created_at ) ) ); ?></td>
            <td><?php echo esc_html( $d->reviewed_by_name ?: '—' ); ?></td>
            <td>
                <?php if ( $d->status === 'pending' && current_user_can( 'rsyi_approve_document' ) ) : ?>
                <button class="button button-small rsyi-doc-approve-btn"
                        data-id="<?php echo esc_attr( $d->id ); ?>"
                        data-confirm-msg="<?php esc_attr_e( 'هل تؤكد قبول هذه الوثيقة؟', 'rsyi-sa' ); ?>">
                    ✅ <?php esc_html_e( 'قبول', 'rsyi-sa' ); ?>
                </button>
                <button class="button button-small rsyi-doc-reject-btn"
                        data-id="<?php echo esc_attr( $d->id ); ?>">
                    ❌ <?php esc_html_e( 'رفض', 'rsyi-sa' ); ?>
                </button>
                <?php elseif ( $d->status !== 'pending' ) : ?>
                    <span class="rsyi-done">—</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>
