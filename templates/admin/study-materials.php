<?php
/**
 * Admin Template: Study Materials
 * Requires capability: rsyi_upload_study_materials
 *
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

if ( ! current_user_can( 'rsyi_upload_study_materials' ) ) {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'ليس لديك صلاحية الوصول لهذه الصفحة.', 'rsyi-sa' ) . '</p></div>';
    return;
}

global $wpdb;

$cohorts   = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}rsyi_cohorts WHERE is_active = 1 ORDER BY name ASC" );
$materials = $wpdb->get_results(
    "SELECT m.*, u.display_name AS uploader_name, c.name AS cohort_name
     FROM {$wpdb->prefix}rsyi_study_materials m
     LEFT JOIN {$wpdb->users} u ON u.ID = m.uploaded_by
     LEFT JOIN {$wpdb->prefix}rsyi_cohorts c ON c.id = m.cohort_id
     ORDER BY m.created_at DESC LIMIT 100"
);
?>
<h1 class="wp-heading-inline"><?php esc_html_e( 'المواد الدراسية', 'rsyi-sa' ); ?></h1>
<hr class="wp-header-end">

<!-- Upload Form -->
<div style="background:#fff; border:1px solid #ccd0d4; border-radius:4px; padding:20px; margin:16px 0; max-width:700px;" dir="rtl">
    <h2 style="margin-top:0;"><?php esc_html_e( 'رفع مادة جديدة', 'rsyi-sa' ); ?></h2>
    <form id="rsyi-material-form" enctype="multipart/form-data">
        <?php wp_nonce_field( 'rsyi_sa_admin', '_nonce' ); ?>
        <input type="hidden" name="action" value="rsyi_upload_material">

        <table class="form-table">
            <tr>
                <th><?php esc_html_e( 'عنوان المادة', 'rsyi-sa' ); ?></th>
                <td><input type="text" name="title" class="regular-text" required></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'المادة / الموضوع', 'rsyi-sa' ); ?></th>
                <td><input type="text" name="subject" class="regular-text" placeholder="<?php esc_attr_e( 'مثال: الملاحة البحرية', 'rsyi-sa' ); ?>"></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'الفوج', 'rsyi-sa' ); ?></th>
                <td>
                    <select name="cohort_id" style="min-width:200px;">
                        <option value=""><?php esc_html_e( '— جميع الأفواج —', 'rsyi-sa' ); ?></option>
                        <?php foreach ( $cohorts as $c ) : ?>
                        <option value="<?php echo esc_attr( $c->id ); ?>"><?php echo esc_html( $c->name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'وصف', 'rsyi-sa' ); ?></th>
                <td><textarea name="description" rows="3" style="width:100%;"></textarea></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'الملف', 'rsyi-sa' ); ?></th>
                <td>
                    <input type="file" name="material_file" required accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip">
                    <p class="description"><?php esc_html_e( 'الأنواع المسموحة: PDF, Word, PowerPoint, Excel, ZIP. الحجم الأقصى: 20 ميجابايت.', 'rsyi-sa' ); ?></p>
                </td>
            </tr>
        </table>

        <p>
            <button type="submit" class="button button-primary" id="rsyi-mat-submit">
                <?php esc_html_e( 'رفع المادة', 'rsyi-sa' ); ?>
            </button>
            <span id="rsyi-mat-msg" style="margin-right:12px;"></span>
        </p>
    </form>
</div>

<!-- Materials List -->
<h2 dir="rtl"><?php esc_html_e( 'المواد المرفوعة', 'rsyi-sa' ); ?></h2>
<?php if ( empty( $materials ) ) : ?>
<p dir="rtl"><?php esc_html_e( 'لا توجد مواد مرفوعة بعد.', 'rsyi-sa' ); ?></p>
<?php else : ?>
<table class="wp-list-table widefat fixed striped" dir="rtl">
    <thead>
        <tr>
            <th><?php esc_html_e( 'العنوان', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'المادة', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'الفوج', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'الرافع', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'التاريخ', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'الحالة', 'rsyi-sa' ); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ( $materials as $m ) : ?>
    <tr id="material-row-<?php echo esc_attr( $m->id ); ?>">
        <td><?php echo esc_html( $m->title ); ?><br><small style="color:#888;"><?php echo esc_html( $m->file_name_orig ); ?></small></td>
        <td><?php echo $m->subject ? esc_html( $m->subject ) : '—'; ?></td>
        <td><?php echo $m->cohort_name ? esc_html( $m->cohort_name ) : esc_html__( 'الكل', 'rsyi-sa' ); ?></td>
        <td><?php echo esc_html( $m->uploader_name ); ?></td>
        <td><?php echo esc_html( date_i18n( 'j M Y', strtotime( $m->created_at ) ) ); ?></td>
        <td>
            <span style="color:<?php echo $m->is_active ? 'green' : '#999'; ?>; font-weight:600;">
                <?php echo $m->is_active ? esc_html__( 'نشط', 'rsyi-sa' ) : esc_html__( 'معطل', 'rsyi-sa' ); ?>
            </span>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<script>
jQuery(function($){
    $('#rsyi-material-form').on('submit', function(e){
        e.preventDefault();
        var $btn = $('#rsyi-mat-submit');
        var $msg = $('#rsyi-mat-msg');
        $btn.prop('disabled', true);
        var formData = new FormData(this);
        $.ajax({
            url: rsyiSA.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res){
                $btn.prop('disabled', false);
                $msg.css('color', res.success ? 'green' : 'red').text(res.data.message);
                if(res.success){ location.reload(); }
            }
        });
    });
});
</script>
