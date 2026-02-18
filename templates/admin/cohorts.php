<?php
/**
 * Admin – الدفعات (Cohorts & Transfers)
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

$tab     = sanitize_key( $tab ?? 'cohorts' );
$cohorts = \RSYI_SA\Modules\Cohorts::get_all_cohorts();

global $wpdb;
$transfers = $wpdb->get_results(
    "SELECT ct.*, sp.arabic_full_name,
            fc.name AS from_cohort_name, tc.name AS to_cohort_name,
            ru.display_name AS requested_by_name, du.display_name AS dean_name
     FROM {$wpdb->prefix}rsyi_cohort_transfers ct
     JOIN {$wpdb->prefix}rsyi_student_profiles sp ON sp.id = ct.student_id
     LEFT JOIN {$wpdb->prefix}rsyi_cohorts fc ON fc.id = ct.from_cohort_id
     LEFT JOIN {$wpdb->prefix}rsyi_cohorts tc ON tc.id = ct.to_cohort_id
     LEFT JOIN {$wpdb->users} ru ON ru.ID = ct.requested_by
     LEFT JOIN {$wpdb->users} du ON du.ID = ct.dean_id
     ORDER BY FIELD(ct.status,'pending_dean','approved','rejected'), ct.created_at DESC
     LIMIT 50"
);
?>
<h1><?php esc_html_e( 'الدفعات', 'rsyi-sa' ); ?></h1>
<hr class="wp-header-end">

<nav class="nav-tab-wrapper" style="margin-bottom:16px;">
    <a class="nav-tab rsyi-tab-btn <?php echo $tab === 'cohorts' ? 'nav-tab-active' : ''; ?>"
       href="?page=rsyi-cohorts&tab=cohorts"><?php esc_html_e( 'إدارة الدفعات', 'rsyi-sa' ); ?></a>
    <a class="nav-tab rsyi-tab-btn <?php echo $tab === 'transfers' ? 'nav-tab-active' : ''; ?>"
       href="?page=rsyi-cohorts&tab=transfers"><?php esc_html_e( 'طلبات تغيير الدفعة', 'rsyi-sa' ); ?></a>
</nav>

<?php if ( $tab === 'cohorts' ) : ?>

<!-- ── Cohorts Tab ─────────────────────────────────────────────── -->
<?php if ( current_user_can( 'rsyi_manage_cohorts' ) ) : ?>
<div class="rsyi-card" style="max-width:640px;margin-bottom:24px;">
    <h2 style="margin-top:0;"><?php esc_html_e( 'إنشاء دفعة جديدة', 'rsyi-sa' ); ?></h2>
    <table class="form-table">
        <tr>
            <th><label for="cohort_name"><?php esc_html_e( 'اسم الدفعة', 'rsyi-sa' ); ?> *</label></th>
            <td><input type="text" id="cohort_name" class="regular-text" placeholder="<?php esc_attr_e( 'مثال: الدفعة الأولى 2024', 'rsyi-sa' ); ?>"></td>
        </tr>
        <tr>
            <th><label for="cohort_code"><?php esc_html_e( 'الرمز (Code)', 'rsyi-sa' ); ?> *</label></th>
            <td><input type="text" id="cohort_code" class="regular-text" placeholder="<?php esc_attr_e( 'مثال: BATCH-2024-01', 'rsyi-sa' ); ?>"></td>
        </tr>
        <tr>
            <th><label for="cohort_start"><?php esc_html_e( 'تاريخ البداية', 'rsyi-sa' ); ?></label></th>
            <td><input type="date" id="cohort_start"></td>
        </tr>
        <tr>
            <th><label for="cohort_end"><?php esc_html_e( 'تاريخ النهاية', 'rsyi-sa' ); ?></label></th>
            <td><input type="date" id="cohort_end"></td>
        </tr>
    </table>
    <button type="button" id="rsyi-create-cohort" class="button button-primary">
        <?php esc_html_e( 'إنشاء الدفعة', 'rsyi-sa' ); ?>
    </button>
    <span id="cohort-status" style="margin-right:10px;"></span>
</div>
<?php endif; ?>

<table class="widefat rsyi-table striped">
    <thead>
        <tr>
            <th>#</th>
            <th><?php esc_html_e( 'اسم الدفعة', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'الرمز', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'تاريخ البداية', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'تاريخ النهاية', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'الحالة', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'عدد الطلاب', 'rsyi-sa' ); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php if ( empty( $cohorts ) ) : ?>
        <tr><td colspan="7"><?php esc_html_e( 'لا توجد دفعات بعد.', 'rsyi-sa' ); ?></td></tr>
    <?php else : ?>
        <?php foreach ( $cohorts as $c ) :
            $count = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}rsyi_student_profiles WHERE cohort_id = %d", $c->id
            ) );
        ?>
        <tr>
            <td><?php echo esc_html( $c->id ); ?></td>
            <td><strong><?php echo esc_html( $c->name ); ?></strong></td>
            <td><code><?php echo esc_html( $c->code ); ?></code></td>
            <td><?php echo esc_html( $c->start_date ?: '—' ); ?></td>
            <td><?php echo esc_html( $c->end_date ?: '—' ); ?></td>
            <td>
                <span class="rsyi-badge <?php echo $c->is_active ? 'rsyi-status-active' : 'rsyi-status-suspended'; ?>">
                    <?php echo $c->is_active ? esc_html__( 'نشطة', 'rsyi-sa' ) : esc_html__( 'مُغلقة', 'rsyi-sa' ); ?>
                </span>
            </td>
            <td>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=rsyi-students&cohort_id=' . $c->id ) ); ?>">
                    <?php echo esc_html( $count ); ?> <?php esc_html_e( 'طالب', 'rsyi-sa' ); ?>
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>

<?php else : ?>

<!-- ── Transfers Tab ──────────────────────────────────────────── -->
<table class="widefat rsyi-table striped">
    <thead>
        <tr>
            <th><?php esc_html_e( 'الطالب', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'من دفعة', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'إلى دفعة', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'السبب', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'طُلب بواسطة', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'الحالة', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'إجراءات', 'rsyi-sa' ); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php if ( empty( $transfers ) ) : ?>
        <tr><td colspan="7"><?php esc_html_e( 'لا توجد طلبات تغيير دفعة.', 'rsyi-sa' ); ?></td></tr>
    <?php else : ?>
        <?php
        $transfer_statuses = [
            'pending_dean' => __( 'انتظار العميد', 'rsyi-sa' ),
            'approved'     => __( 'مُوافق عليه', 'rsyi-sa' ),
            'rejected'     => __( 'مرفوض', 'rsyi-sa' ),
        ];
        foreach ( $transfers as $t ) : ?>
        <tr class="<?php echo $t->status !== 'pending_dean' ? 'rsyi-row-muted' : ''; ?>">
            <td><?php echo esc_html( $t->arabic_full_name ); ?></td>
            <td><?php echo esc_html( $t->from_cohort_name ); ?></td>
            <td><?php echo esc_html( $t->to_cohort_name ); ?></td>
            <td><?php echo esc_html( wp_trim_words( $t->reason ?? '', 10, '…' ) ); ?></td>
            <td><?php echo esc_html( $t->requested_by_name ); ?></td>
            <td>
                <span class="rsyi-badge rsyi-status-<?php echo esc_attr( $t->status ); ?>">
                    <?php echo esc_html( $transfer_statuses[ $t->status ] ?? $t->status ); ?>
                </span>
            </td>
            <td>
                <?php if ( $t->status === 'pending_dean' && current_user_can( 'rsyi_approve_cohort_transfer' ) ) : ?>
                <button class="button button-small rsyi-transfer-approve-btn"
                        data-id="<?php echo esc_attr( $t->id ); ?>"
                        data-confirm-msg="<?php esc_attr_e( 'هل تؤكد الموافقة على تغيير الدفعة؟', 'rsyi-sa' ); ?>">
                    ✅ <?php esc_html_e( 'موافقة', 'rsyi-sa' ); ?>
                </button>
                <button class="button button-small rsyi-transfer-reject-btn"
                        data-id="<?php echo esc_attr( $t->id ); ?>">
                    ❌ <?php esc_html_e( 'رفض', 'rsyi-sa' ); ?>
                </button>
                <?php else : ?>
                    <span><?php echo esc_html( $t->dean_name ?: '—' ); ?></span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>

<?php endif; ?>

<script>
jQuery(function($){
    $('#rsyi-create-cohort').on('click', function(){
        var btn  = $(this).prop('disabled', true);
        var stat = $('#cohort-status');
        stat.text('');
        $.post(rsyiSA.ajaxUrl, {
            action    : 'rsyi_create_cohort',
            _nonce    : rsyiSA.nonce,
            name      : $('#cohort_name').val(),
            code      : $('#cohort_code').val(),
            start_date: $('#cohort_start').val(),
            end_date  : $('#cohort_end').val()
        }, function(res){
            btn.prop('disabled', false);
            if(res.success){
                stat.text('✅ ' + res.data.message).css('color','#1a7a4a');
                setTimeout(function(){ location.reload(); }, 1200);
            } else {
                stat.text('❌ ' + res.data.message).css('color','#c0392b');
            }
        }).fail(function(){ btn.prop('disabled', false); });
    });
});
</script>
