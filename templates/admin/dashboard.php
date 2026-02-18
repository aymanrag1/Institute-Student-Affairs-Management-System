<?php
/**
 * Admin Dashboard Template
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

global $wpdb;

// Stats
$total_students  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}rsyi_student_profiles" );
$active_students = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}rsyi_student_profiles WHERE status = 'active'" );
$pending_docs    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}rsyi_documents WHERE status = 'pending'" );
$pending_exit    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}rsyi_exit_permits WHERE status IN ('pending_dorm','pending_manager')" );
$pending_overnight = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}rsyi_overnight_permits WHERE status IN ('pending_supervisor','pending_manager','pending_dean')" );
$pending_expulsion = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}rsyi_expulsion_cases WHERE status = 'pending_dean'" );
?>
<h1><?php esc_html_e( 'لوحة تحكم شؤون الطلاب', 'rsyi-sa' ); ?></h1>
<p class="rsyi-subtitle"><?php echo esc_html( get_option( 'rsyi_institute_name', 'معهد البحر الأحمر للتخطيط البحري – الجونة' ) ); ?></p>

<div class="rsyi-stats-grid">
    <div class="rsyi-stat-card">
        <span class="rsyi-stat-number"><?php echo esc_html( $total_students ); ?></span>
        <span class="rsyi-stat-label"><?php esc_html_e( 'إجمالي الطلاب', 'rsyi-sa' ); ?></span>
    </div>
    <div class="rsyi-stat-card rsyi-stat-green">
        <span class="rsyi-stat-number"><?php echo esc_html( $active_students ); ?></span>
        <span class="rsyi-stat-label"><?php esc_html_e( 'طلاب نشطون', 'rsyi-sa' ); ?></span>
    </div>
    <div class="rsyi-stat-card rsyi-stat-orange">
        <span class="rsyi-stat-number"><?php echo esc_html( $pending_docs ); ?></span>
        <span class="rsyi-stat-label"><?php esc_html_e( 'وثائق قيد المراجعة', 'rsyi-sa' ); ?></span>
    </div>
    <div class="rsyi-stat-card rsyi-stat-blue">
        <span class="rsyi-stat-number"><?php echo esc_html( $pending_exit ); ?></span>
        <span class="rsyi-stat-label"><?php esc_html_e( 'أذونات خروج معلقة', 'rsyi-sa' ); ?></span>
    </div>
    <div class="rsyi-stat-card rsyi-stat-blue">
        <span class="rsyi-stat-number"><?php echo esc_html( $pending_overnight ); ?></span>
        <span class="rsyi-stat-label"><?php esc_html_e( 'أذونات مبيت معلقة', 'rsyi-sa' ); ?></span>
    </div>
    <?php if ( current_user_can( 'rsyi_manage_expulsion' ) ) : ?>
    <div class="rsyi-stat-card rsyi-stat-red">
        <span class="rsyi-stat-number"><?php echo esc_html( $pending_expulsion ); ?></span>
        <span class="rsyi-stat-label"><?php esc_html_e( 'قضايا طرد معلقة', 'rsyi-sa' ); ?></span>
    </div>
    <?php endif; ?>
</div>

<div class="rsyi-quick-actions">
    <h2><?php esc_html_e( 'إجراءات سريعة', 'rsyi-sa' ); ?></h2>
    <a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=rsyi-students&action=add' ) ); ?>">
        ➕ <?php esc_html_e( 'إضافة طالب', 'rsyi-sa' ); ?>
    </a>
    <?php if ( current_user_can( 'rsyi_print_daily_report' ) ) : ?>
    <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=rsyi-daily-report' ) ); ?>">
        📄 <?php esc_html_e( 'طباعة التقرير اليومي', 'rsyi-sa' ); ?>
    </a>
    <?php endif; ?>
</div>

<?php
// Recent audit entries
$log = \RSYI_SA\Audit_Log::get_recent( 10 );
if ( ! empty( $log ) ) : ?>
<h2><?php esc_html_e( 'آخر الأحداث', 'rsyi-sa' ); ?></h2>
<table class="widefat rsyi-table">
    <thead>
        <tr>
            <th><?php esc_html_e( 'التاريخ', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'المستخدم', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'الكيان', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'الإجراء', 'rsyi-sa' ); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ( $log as $entry ) : ?>
        <tr>
            <td><?php echo esc_html( $entry->created_at ); ?></td>
            <td><?php echo esc_html( $entry->actor_name ); ?></td>
            <td><?php echo esc_html( $entry->entity_type . ' #' . $entry->entity_id ); ?></td>
            <td><code><?php echo esc_html( $entry->action ); ?></code></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
