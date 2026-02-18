<?php
/**
 * Admin – Student Dismissal Cases (فصل طالب)
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
    $where   .= ' AND ec.status = %s';
    $params[] = $status_filter;
}

$params[] = $per_page;
$params[] = $offset;

$cases = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT ec.*, sp.arabic_full_name, sp.english_full_name, sp.id AS profile_id,
                du.display_name AS dean_name
         FROM {$wpdb->prefix}rsyi_expulsion_cases ec
         JOIN {$wpdb->prefix}rsyi_student_profiles sp ON sp.id = ec.student_id
         LEFT JOIN {$wpdb->users} du ON du.ID = ec.dean_id
         WHERE {$where}
         ORDER BY FIELD(ec.status,'pending_dean','approved','rejected','executed'), ec.created_at DESC
         LIMIT %d OFFSET %d",
        ...$params
    )
);

$status_labels = [
    'pending_dean' => __( 'انتظار قرار العميد', 'rsyi-sa' ),
    'approved'     => __( 'مُقرَّر الفصل', 'rsyi-sa' ),
    'rejected'     => __( 'رُفض الفصل', 'rsyi-sa' ),
    'executed'     => __( 'مُنفَّذ', 'rsyi-sa' ),
];
?>
<h1 class="wp-heading-inline"><?php esc_html_e( 'فصل طالب', 'rsyi-sa' ); ?></h1>
<hr class="wp-header-end">

<form method="get" class="rsyi-filter-form">
    <input type="hidden" name="page" value="rsyi-expulsion">
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
            <th><?php esc_html_e( 'سبب الفصل', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'إجمالي النقاط', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'الحالة', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'العميد', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'التاريخ', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'إجراءات', 'rsyi-sa' ); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php if ( empty( $cases ) ) : ?>
        <tr><td colspan="8"><?php esc_html_e( 'لا توجد قضايا فصل.', 'rsyi-sa' ); ?></td></tr>
    <?php else : ?>
        <?php foreach ( $cases as $c ) : ?>
        <tr>
            <td><?php echo esc_html( $c->id ); ?></td>
            <td>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=rsyi-students&action=view&id=' . $c->profile_id ) ); ?>">
                    <strong><?php echo esc_html( $c->arabic_full_name ); ?></strong>
                </a>
                <br><small><?php echo esc_html( $c->english_full_name ); ?></small>
            </td>
            <td>
                <?php
                $triggers = [
                    '40_points'      => __( 'تجاوز 40 نقطة', 'rsyi-sa' ),
                    'dean_initiated' => __( 'بقرار العميد', 'rsyi-sa' ),
                ];
                echo esc_html( $triggers[ $c->triggered_by ] ?? $c->triggered_by );
                ?>
            </td>
            <td><strong style="color:#c0392b;"><?php echo esc_html( $c->total_points ); ?></strong></td>
            <td>
                <span class="rsyi-badge rsyi-status-<?php echo esc_attr( $c->status ); ?>">
                    <?php echo esc_html( $status_labels[ $c->status ] ?? $c->status ); ?>
                </span>
                <?php if ( $c->dean_notes ) : ?>
                    <br><small style="color:#666;"><?php echo esc_html( wp_trim_words( $c->dean_notes, 10, '…' ) ); ?></small>
                <?php endif; ?>
            </td>
            <td><?php echo esc_html( $c->dean_name ?: '—' ); ?></td>
            <td><?php echo esc_html( wp_date( 'Y-m-d', strtotime( $c->created_at ) ) ); ?></td>
            <td>
                <?php if ( $c->status === 'pending_dean' && current_user_can( 'rsyi_approve_expulsion' ) ) : ?>
                <button class="button button-small rsyi-expulsion-approve-btn"
                        data-id="<?php echo esc_attr( $c->id ); ?>">
                    ✅ <?php esc_html_e( 'الموافقة على الفصل', 'rsyi-sa' ); ?>
                </button>
                <button class="button button-small rsyi-expulsion-reject-btn"
                        data-id="<?php echo esc_attr( $c->id ); ?>">
                    ❌ <?php esc_html_e( 'رفض الفصل', 'rsyi-sa' ); ?>
                </button>
                <?php elseif ( $c->status === 'approved' && current_user_can( 'rsyi_manage_expulsion' ) ) : ?>
                <button class="button button-small rsyi-expulsion-execute-btn"
                        data-id="<?php echo esc_attr( $c->id ); ?>"
                        data-confirm-msg="<?php esc_attr_e( 'تأكيد تنفيذ قرار الفصل نهائياً؟', 'rsyi-sa' ); ?>">
                    🔴 <?php esc_html_e( 'تنفيذ الفصل', 'rsyi-sa' ); ?>
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
