<?php
/**
 * Admin Template: Attendance Management
 * Requires capability: rsyi_manage_attendance
 *
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

use RSYI_SA\Modules\Accounts;

if ( ! current_user_can( 'rsyi_manage_attendance' ) ) {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'ليس لديك صلاحية الوصول لهذه الصفحة.', 'rsyi-sa' ) . '</p></div>';
    return;
}

global $wpdb;

// ── Load cohorts for filter ─────────────────────────────────────────────────
$cohorts = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}rsyi_cohorts WHERE is_active = 1 ORDER BY name ASC"
);

$filter_cohort = absint( $_GET['cohort_id'] ?? 0 );
$filter_date   = sanitize_text_field( $_GET['session_date'] ?? date( 'Y-m-d' ) );

// ── Load students for this cohort ───────────────────────────────────────────
$students = [];
if ( $filter_cohort ) {
    $students = $wpdb->get_results( $wpdb->prepare(
        "SELECT sp.id AS profile_id, sp.user_id, sp.arabic_full_name, sp.english_full_name
         FROM {$wpdb->prefix}rsyi_student_profiles sp
         WHERE sp.cohort_id = %d AND sp.status = 'active'
         ORDER BY sp.arabic_full_name ASC",
        $filter_cohort
    ) );
}

// ── Load existing attendance for this date/cohort ──────────────────────────
$existing_att = [];
if ( $filter_cohort && $filter_date ) {
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT student_id, status, notes FROM {$wpdb->prefix}rsyi_attendance
         WHERE cohort_id = %d AND session_date = %s",
        $filter_cohort, $filter_date
    ) );
    foreach ( $rows as $r ) {
        $existing_att[ (int) $r->student_id ] = $r;
    }
}
?>
<h1 class="wp-heading-inline"><?php esc_html_e( 'الحضور والغياب', 'rsyi-sa' ); ?></h1>
<hr class="wp-header-end">

<!-- Filter bar -->
<div style="background:#fff; border:1px solid #ccd0d4; border-radius:4px; padding:16px 20px; margin:16px 0; display:flex; gap:16px; align-items:center; flex-wrap:wrap;" dir="rtl">
    <form method="get" action="" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
        <input type="hidden" name="page" value="rsyi-attendance">
        <label><strong><?php esc_html_e( 'الفوج:', 'rsyi-sa' ); ?></strong>
            <select name="cohort_id" style="margin-right:6px; min-width:160px;">
                <option value=""><?php esc_html_e( '— اختر الفوج —', 'rsyi-sa' ); ?></option>
                <?php foreach ( $cohorts as $c ) : ?>
                <option value="<?php echo esc_attr( $c->id ); ?>" <?php selected( $filter_cohort, $c->id ); ?>>
                    <?php echo esc_html( $c->name ); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label><strong><?php esc_html_e( 'التاريخ:', 'rsyi-sa' ); ?></strong>
            <input type="date" name="session_date" value="<?php echo esc_attr( $filter_date ); ?>" style="margin-right:6px;">
        </label>
        <button type="submit" class="button button-primary"><?php esc_html_e( 'عرض', 'rsyi-sa' ); ?></button>
    </form>
</div>

<?php if ( $filter_cohort && ! empty( $students ) ) : ?>
<form id="rsyi-attendance-form" dir="rtl">
    <?php wp_nonce_field( 'rsyi_sa_admin', '_nonce' ); ?>
    <input type="hidden" name="action" value="rsyi_save_attendance">
    <input type="hidden" name="cohort_id" value="<?php echo esc_attr( $filter_cohort ); ?>">
    <input type="hidden" name="session_date" value="<?php echo esc_attr( $filter_date ); ?>">

    <table class="wp-list-table widefat fixed striped" style="direction:rtl;">
        <thead>
            <tr>
                <th style="width:40px; text-align:center;">#</th>
                <th><?php esc_html_e( 'اسم الطالب', 'rsyi-sa' ); ?></th>
                <th style="width:120px; text-align:center;"><?php esc_html_e( 'حاضر', 'rsyi-sa' ); ?></th>
                <th style="width:120px; text-align:center;"><?php esc_html_e( 'غائب', 'rsyi-sa' ); ?></th>
                <th style="width:120px; text-align:center;"><?php esc_html_e( 'متأخر', 'rsyi-sa' ); ?></th>
                <th><?php esc_html_e( 'ملاحظات', 'rsyi-sa' ); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ( $students as $i => $student ) :
            $att = $existing_att[ (int) $student->profile_id ] ?? null;
            $status = $att ? $att->status : 'present';
            $notes  = $att ? $att->notes  : '';
        ?>
        <tr>
            <td style="text-align:center;"><?php echo $i + 1; ?></td>
            <td>
                <strong><?php echo esc_html( $student->arabic_full_name ); ?></strong>
                <br><small style="color:#888;"><?php echo esc_html( $student->english_full_name ); ?></small>
                <input type="hidden" name="students[]" value="<?php echo esc_attr( $student->profile_id ); ?>">
            </td>
            <td style="text-align:center;">
                <input type="radio" name="status_<?php echo esc_attr( $student->profile_id ); ?>"
                       value="present" <?php checked( $status, 'present' ); ?>>
            </td>
            <td style="text-align:center;">
                <input type="radio" name="status_<?php echo esc_attr( $student->profile_id ); ?>"
                       value="absent" <?php checked( $status, 'absent' ); ?>>
            </td>
            <td style="text-align:center;">
                <input type="radio" name="status_<?php echo esc_attr( $student->profile_id ); ?>"
                       value="late" <?php checked( $status, 'late' ); ?>>
            </td>
            <td>
                <input type="text" name="notes_<?php echo esc_attr( $student->profile_id ); ?>"
                       value="<?php echo esc_attr( $notes ); ?>"
                       style="width:100%;" placeholder="<?php esc_attr_e( 'ملاحظة اختيارية', 'rsyi-sa' ); ?>">
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <p style="margin-top:16px;">
        <button type="submit" class="button button-primary button-large" id="rsyi-att-save">
            <?php esc_html_e( 'حفظ الحضور', 'rsyi-sa' ); ?>
        </button>
        <span id="rsyi-att-msg" style="margin-right:12px;"></span>
    </p>
</form>

<script>
jQuery(function($){
    $('#rsyi-attendance-form').on('submit', function(e){
        e.preventDefault();
        var $btn = $('#rsyi-att-save');
        var $msg = $('#rsyi-att-msg');
        $btn.prop('disabled', true);
        $.post(rsyiSA.ajaxUrl, $(this).serialize(), function(res){
            $btn.prop('disabled', false);
            $msg.css('color', res.success ? 'green' : 'red').text(res.data.message);
        });
    });
});
</script>

<?php elseif ( $filter_cohort && empty( $students ) ) : ?>
<div class="notice notice-warning" dir="rtl"><p><?php esc_html_e( 'لا يوجد طلاب نشطون في هذا الفوج.', 'rsyi-sa' ); ?></p></div>
<?php else : ?>
<div class="notice notice-info" dir="rtl"><p><?php esc_html_e( 'اختر فوجاً وتاريخاً لعرض قائمة الحضور.', 'rsyi-sa' ); ?></p></div>
<?php endif; ?>
