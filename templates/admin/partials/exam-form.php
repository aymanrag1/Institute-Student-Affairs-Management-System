<?php
/**
 * Partial: Exam Create / Edit Form
 * Variables: $active_tab, $selected_exam (null for create), $cohorts, $type_labels, $status_labels
 *
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

$is_edit = ( $active_tab === 'edit' && ! empty( $selected_exam ) );
$e       = $is_edit ? $selected_exam : null;

$form_id   = $is_edit ? 'rsyi-update-exam-form' : 'rsyi-create-exam-form';
$action    = $is_edit ? 'rsyi_update_exam' : 'rsyi_create_exam';
$msg_id    = $is_edit ? 'rsyi-exam-update-msg' : 'rsyi-exam-create-msg';
$btn_label = $is_edit ? __( 'حفظ التعديلات', 'rsyi-sa' ) : __( 'إنشاء الامتحان', 'rsyi-sa' );
$title     = $is_edit
    ? esc_html( $e->title ) . ' — ' . __( 'تعديل', 'rsyi-sa' )
    : __( 'إنشاء امتحان جديد', 'rsyi-sa' );
?>
<div style="max-width:680px; background:#fff; border:1px solid #ccd0d4; border-radius:4px; padding:24px;" dir="rtl">
    <h2 style="margin-top:0;"><?php echo esc_html( $title ); ?></h2>
    <form id="<?php echo esc_attr( $form_id ); ?>">
        <?php wp_nonce_field( 'rsyi_sa_admin', '_nonce' ); ?>
        <input type="hidden" name="action" value="<?php echo esc_attr( $action ); ?>">
        <?php if ( $is_edit ) : ?>
        <input type="hidden" name="exam_id" value="<?php echo esc_attr( $e->id ); ?>">
        <?php endif; ?>

        <table class="form-table">
            <tr>
                <th><?php esc_html_e( 'عنوان الامتحان', 'rsyi-sa' ); ?> *</th>
                <td><input type="text" name="title" class="regular-text" required
                           value="<?php echo esc_attr( $e->title ?? '' ); ?>"></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'المادة', 'rsyi-sa' ); ?></th>
                <td><input type="text" name="subject" class="regular-text"
                           value="<?php echo esc_attr( $e->subject ?? '' ); ?>"></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'نوع الامتحان', 'rsyi-sa' ); ?></th>
                <td>
                    <select name="exam_type">
                        <?php foreach ( $type_labels as $val => $lbl ) : ?>
                        <option value="<?php echo esc_attr( $val ); ?>"
                            <?php selected( ( $e->exam_type ?? 'written' ), $val ); ?>>
                            <?php echo esc_html( $lbl ); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'الفوج', 'rsyi-sa' ); ?></th>
                <td>
                    <select name="cohort_id" style="min-width:200px;">
                        <option value=""><?php esc_html_e( '— اختر الفوج —', 'rsyi-sa' ); ?></option>
                        <?php foreach ( $cohorts as $c ) : ?>
                        <option value="<?php echo esc_attr( $c->id ); ?>"
                            <?php selected( ( $e->cohort_id ?? '' ), $c->id ); ?>>
                            <?php echo esc_html( $c->name ); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'بداية الامتحان', 'rsyi-sa' ); ?></th>
                <td>
                    <input type="datetime-local" name="starts_at" style="min-width:220px;"
                           value="<?php echo esc_attr( rsyi_dt_local( $e->starts_at ?? null ) ); ?>">
                    <p class="description"><?php esc_html_e( 'التاريخ والوقت بالدقيقة (سيُتاح الامتحان للطلاب من هذا الوقت).', 'rsyi-sa' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'نهاية الامتحان', 'rsyi-sa' ); ?></th>
                <td>
                    <input type="datetime-local" name="ends_at" style="min-width:220px;"
                           value="<?php echo esc_attr( rsyi_dt_local( $e->ends_at ?? null ) ); ?>">
                    <p class="description"><?php esc_html_e( 'التاريخ والوقت بالدقيقة (يُغلق الامتحان تلقائياً).', 'rsyi-sa' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'المدة (دقيقة)', 'rsyi-sa' ); ?></th>
                <td>
                    <input type="number" name="duration_min" min="1" style="width:90px;"
                           value="<?php echo esc_attr( $e->duration_min ?? '' ); ?>">
                    <p class="description"><?php esc_html_e( 'للعرض فقط — يُحسب Timer الطالب من وقت البداية/النهاية.', 'rsyi-sa' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'الدرجة القصوى', 'rsyi-sa' ); ?></th>
                <td><input type="number" name="max_score" value="<?php echo esc_attr( $e->max_score ?? 100 ); ?>"
                           min="1" style="width:90px;" required></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'درجة النجاح', 'rsyi-sa' ); ?></th>
                <td>
                    <input type="number" name="passing_score" min="0" style="width:90px;"
                           value="<?php echo esc_attr( $e->passing_score ?? '' ); ?>"
                           placeholder="<?php esc_attr_e( 'الافتراضي: 50%', 'rsyi-sa' ); ?>">
                    <p class="description"><?php esc_html_e( 'اتركه فارغاً لاستخدام 50% من الدرجة القصوى.', 'rsyi-sa' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'الحالة', 'rsyi-sa' ); ?></th>
                <td>
                    <select name="status">
                        <?php foreach ( $status_labels as $val => $lbl ) : ?>
                        <option value="<?php echo esc_attr( $val ); ?>"
                            <?php selected( ( $e->status ?? 'published' ), $val ); ?>>
                            <?php echo esc_html( $lbl ); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>

        <hr style="margin:20px 0;">
        <h3 style="margin-top:0; color:#23282d;"><?php esc_html_e( 'إعدادات التصحيح والنتائج', 'rsyi-sa' ); ?></h3>

        <table class="form-table">
            <tr>
                <th><?php esc_html_e( 'إظهار النتيجة للطالب', 'rsyi-sa' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="show_results" value="1"
                               <?php checked( $e->show_results ?? 1, 1 ); ?>>
                        <?php esc_html_e( 'الطالب يرى درجته بعد انتهاء الامتحان', 'rsyi-sa' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'التصحيح التلقائي', 'rsyi-sa' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="auto_grade" value="1" id="rsyi-auto-grade-check"
                               <?php checked( $e->auto_grade ?? 1, 1 ); ?>>
                        <?php esc_html_e( 'السيستم يصحح أسئلة MCQ / صح-خطأ / توصيل / إكمال / ترتيب تلقائياً', 'rsyi-sa' ); ?>
                    </label>
                    <p class="description"><?php esc_html_e( 'الإجابات القصيرة والمقالات تظل تحتاج تصحيحاً يدوياً.', 'rsyi-sa' ); ?></p>
                </td>
            </tr>
            <tr id="rsyi-allow-regrade-row" style="<?php echo empty( $e->auto_grade ) && ! ( $e === null ) ? 'display:none;' : ''; ?>">
                <th><?php esc_html_e( 'السماح بإعادة التصحيح', 'rsyi-sa' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="allow_regrade" value="1"
                               <?php checked( $e->allow_regrade ?? 1, 1 ); ?>>
                        <?php esc_html_e( 'المدرس يستطيع تعديل الدرجة بعد التصحيح التلقائي', 'rsyi-sa' ); ?>
                    </label>
                </td>
            </tr>
        </table>

        <hr style="margin:20px 0;">

        <table class="form-table">
            <tr>
                <th><?php esc_html_e( 'وصف', 'rsyi-sa' ); ?></th>
                <td><textarea name="description" rows="3" style="width:100%;"><?php echo esc_textarea( $e->description ?? '' ); ?></textarea></td>
            </tr>
        </table>

        <p>
            <button type="submit" class="button button-primary button-large"><?php echo esc_html( $btn_label ); ?></button>
            <span id="<?php echo esc_attr( $msg_id ); ?>" style="margin-right:12px;"></span>
        </p>
    </form>
</div>

<script>
jQuery(function($){
    $('#rsyi-auto-grade-check').on('change', function(){
        if($(this).is(':checked')){
            $('#rsyi-allow-regrade-row').show();
        } else {
            $('#rsyi-allow-regrade-row').hide();
        }
    });
});
</script>
