<?php
/**
 * Admin – Audit Log
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

$page_num = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
$per_page = 50;
$offset   = ( $page_num - 1 ) * $per_page;

$logs = \RSYI_SA\Audit_Log::get_recent( $per_page, $offset );

$entity_labels = [
    'student_profile' => __( 'ملف طالب', 'rsyi-sa' ),
    'document'        => __( 'وثيقة', 'rsyi-sa' ),
    'exit_permit'     => __( 'إذن خروج', 'rsyi-sa' ),
    'overnight_permit'=> __( 'إذن مبيت', 'rsyi-sa' ),
    'violation'       => __( 'مخالفة', 'rsyi-sa' ),
    'cohort'          => __( 'دفعة', 'rsyi-sa' ),
    'cohort_transfer' => __( 'تغيير دفعة', 'rsyi-sa' ),
    'expulsion_case'  => __( 'فصل طالب', 'rsyi-sa' ),
];

$action_labels = [
    'create'           => __( 'إنشاء', 'rsyi-sa' ),
    'update'           => __( 'تحديث', 'rsyi-sa' ),
    'approve'          => __( 'موافقة', 'rsyi-sa' ),
    'approve_step1'    => __( 'موافقة م.1', 'rsyi-sa' ),
    'approve_step2'    => __( 'موافقة م.2', 'rsyi-sa' ),
    'approve_final'    => __( 'موافقة نهائية', 'rsyi-sa' ),
    'approve_and_execute' => __( 'موافقة وتنفيذ', 'rsyi-sa' ),
    'reject'           => __( 'رفض', 'rsyi-sa' ),
    'execute'          => __( 'تنفيذ', 'rsyi-sa' ),
    'activate'         => __( 'تفعيل', 'rsyi-sa' ),
    'overturn'         => __( 'إلغاء مخالفة', 'rsyi-sa' ),
    'excel_import'     => __( 'استيراد Excel', 'rsyi-sa' ),
];
?>
<h1><?php esc_html_e( 'سجل الأحداث', 'rsyi-sa' ); ?></h1>
<hr class="wp-header-end">

<p class="description">
    <?php esc_html_e( 'سجل كامل لجميع العمليات المُنجزة على النظام. لا يمكن حذف هذه السجلات.', 'rsyi-sa' ); ?>
</p>

<table class="widefat rsyi-table striped">
    <thead>
        <tr>
            <th><?php esc_html_e( 'التاريخ والوقت', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'المستخدم', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'الكيان', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'المعرّف', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'الإجراء', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'تفاصيل', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'عنوان IP', 'rsyi-sa' ); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php if ( empty( $logs ) ) : ?>
        <tr><td colspan="7"><?php esc_html_e( 'لا توجد سجلات بعد.', 'rsyi-sa' ); ?></td></tr>
    <?php else : ?>
        <?php foreach ( $logs as $log ) : ?>
        <tr>
            <td style="white-space:nowrap;"><?php echo esc_html( wp_date( 'Y-m-d H:i:s', strtotime( $log->created_at ) ) ); ?></td>
            <td><?php echo esc_html( $log->actor_name ?: __( 'النظام', 'rsyi-sa' ) ); ?></td>
            <td><?php echo esc_html( $entity_labels[ $log->entity_type ] ?? $log->entity_type ); ?></td>
            <td><?php echo esc_html( $log->entity_id ); ?></td>
            <td>
                <code><?php echo esc_html( $action_labels[ $log->action ] ?? $log->action ); ?></code>
            </td>
            <td style="font-size:12px;color:#555;max-width:250px;">
                <?php
                if ( $log->details_json ) {
                    $details = json_decode( $log->details_json, true );
                    if ( is_array( $details ) ) {
                        $parts = [];
                        foreach ( $details as $k => $v ) {
                            $parts[] = esc_html( $k ) . ': <strong>' . esc_html( (string) $v ) . '</strong>';
                        }
                        echo implode( ' | ', $parts ); // phpcs:ignore
                    }
                }
                ?>
            </td>
            <td style="font-size:12px;"><?php echo esc_html( $log->ip_address ?: '—' ); ?></td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>

<?php if ( count( $logs ) === $per_page ) : ?>
<div style="margin-top:12px;">
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=rsyi-audit&paged=' . ( $page_num + 1 ) ) ); ?>"
       class="button">
        <?php esc_html_e( 'الصفحة التالية ←', 'rsyi-sa' ); ?>
    </a>
    <?php if ( $page_num > 1 ) : ?>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=rsyi-audit&paged=' . ( $page_num - 1 ) ) ); ?>"
       class="button" style="margin-right:6px;">
        <?php esc_html_e( '→ الصفحة السابقة', 'rsyi-sa' ); ?>
    </a>
    <?php endif; ?>
</div>
<?php endif; ?>
