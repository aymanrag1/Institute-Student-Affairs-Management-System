<?php
/**
 * Admin – Courses Management
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

global $wpdb;
$courses = $wpdb->get_results(
    "SELECT c.*, u.display_name AS creator FROM {$wpdb->prefix}rsyi_courses c
     LEFT JOIN {$wpdb->users} u ON u.ID = c.created_by
     ORDER BY c.name_ar ASC"
);

// Build cohorts + students for boss man management
$cohorts = $wpdb->get_results(
    "SELECT id, name FROM {$wpdb->prefix}rsyi_cohorts WHERE is_active = 1 ORDER BY name ASC"
);
?>
<h1 style="margin-bottom:4px;">📚 إدارة الكورسات</h1>
<p style="color:#666; margin-bottom:20px;">أضف وعدّل الكورسات التي يمكن تخصيص الطلاب لها في تقرير المتابعة اليومي.</p>

<div id="rsyi-course-notice" style="display:none; margin:12px 0;" class="notice"></div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:32px;">

    <!-- Add / Edit Course -->
    <div style="background:#fff; border:1px solid #ccd0d4; border-radius:8px; padding:24px;">
        <h2 style="margin-top:0; font-size:16px; border-bottom:2px solid #0073aa; padding-bottom:8px;">
            ➕ إضافة كورس جديد
        </h2>
        <form id="rsyi-course-form">
            <input type="hidden" id="course-id" value="0">
            <table class="form-table" style="margin-top:0;">
                <tr>
                    <th style="width:130px;"><label for="course-name-ar">الاسم بالعربي <span style="color:red">*</span></label></th>
                    <td><input type="text" id="course-name-ar" class="regular-text" placeholder="مثال: كورس الرادار" style="width:100%;"></td>
                </tr>
                <tr>
                    <th><label for="course-name-en">الاسم بالإنجليزي</label></th>
                    <td><input type="text" id="course-name-en" class="regular-text" placeholder="e.g. Radar Course" style="width:100%;"></td>
                </tr>
                <tr>
                    <th><label for="course-desc">وصف مختصر</label></th>
                    <td><textarea id="course-desc" rows="3" style="width:100%;"></textarea></td>
                </tr>
            </table>
            <div style="margin-top:16px; display:flex; gap:10px;">
                <button type="submit" class="button button-primary" id="course-save-btn">💾 حفظ الكورس</button>
                <button type="button" class="button" id="course-cancel-btn" style="display:none;">إلغاء</button>
            </div>
        </form>
    </div>

    <!-- Boss Man Management -->
    <div style="background:#fff; border:1px solid #ccd0d4; border-radius:8px; padding:24px;">
        <h2 style="margin-top:0; font-size:16px; border-bottom:2px solid #e67e22; padding-bottom:8px;">
            👮 تعيين حكمدار الدفعة
        </h2>
        <p style="color:#666; font-size:13px;">اختر الدفعة ثم حدد الطالب الذي سيكون حكمداراً لها.</p>
        <select id="bm-cohort" style="width:100%; margin-bottom:12px;">
            <option value="">— اختر الدفعة —</option>
            <?php foreach ( $cohorts as $c ) : ?>
            <option value="<?php echo esc_attr( $c->id ); ?>"><?php echo esc_html( $c->name ); ?></option>
            <?php endforeach; ?>
        </select>
        <div id="bm-students-list" style="max-height:250px; overflow-y:auto; border:1px solid #dee2e6; border-radius:6px; padding:10px; display:none;"></div>
        <p id="bm-no-cohort" style="color:#999; font-style:italic;">اختر دفعة لعرض الطلاب</p>
    </div>
</div>

<!-- Courses Table -->
<h2 style="font-size:16px;">📋 الكورسات المسجلة (<?php echo count( $courses ); ?>)</h2>
<table class="widefat rsyi-table striped" id="courses-table">
    <thead>
        <tr>
            <th>#</th>
            <th>الاسم بالعربي</th>
            <th>الاسم بالإنجليزي</th>
            <th>الوصف</th>
            <th>الحالة</th>
            <th>تاريخ الإضافة</th>
            <th>إجراءات</th>
        </tr>
    </thead>
    <tbody>
    <?php if ( empty( $courses ) ) : ?>
        <tr><td colspan="7" style="text-align:center; color:#999; padding:20px;">لا توجد كورسات مسجلة. أضف أول كورس من النموذج أعلاه.</td></tr>
    <?php else : ?>
        <?php foreach ( $courses as $c ) : ?>
        <tr id="course-row-<?php echo (int) $c->id; ?>">
            <td><?php echo (int) $c->id; ?></td>
            <td><strong><?php echo esc_html( $c->name_ar ); ?></strong></td>
            <td><?php echo esc_html( $c->name_en ?: '—' ); ?></td>
            <td style="font-size:12px; color:#666; max-width:200px;"><?php echo esc_html( $c->description ?: '—' ); ?></td>
            <td>
                <span class="rsyi-status-badge" id="status-badge-<?php echo (int) $c->id; ?>"
                      style="background:<?php echo $c->is_active ? '#27ae60' : '#999'; ?>; color:#fff; padding:3px 10px; border-radius:20px; font-size:12px;">
                    <?php echo $c->is_active ? 'نشط' : 'موقوف'; ?>
                </span>
            </td>
            <td style="font-size:12px;"><?php echo esc_html( wp_date( 'Y-m-d', strtotime( $c->created_at ) ) ); ?></td>
            <td>
                <button class="button button-small rsyi-edit-course"
                        data-id="<?php echo (int) $c->id; ?>"
                        data-name-ar="<?php echo esc_attr( $c->name_ar ); ?>"
                        data-name-en="<?php echo esc_attr( $c->name_en ); ?>"
                        data-desc="<?php echo esc_attr( $c->description ); ?>">
                    ✏️ تعديل
                </button>
                <button class="button button-small rsyi-toggle-course"
                        data-id="<?php echo (int) $c->id; ?>"
                        data-active="<?php echo (int) $c->is_active; ?>"
                        style="color:<?php echo $c->is_active ? '#e67e22' : '#27ae60'; ?>;">
                    <?php echo $c->is_active ? '⏸ إيقاف' : '▶ تفعيل'; ?>
                </button>
                <button class="button button-small rsyi-delete-course"
                        data-id="<?php echo (int) $c->id; ?>"
                        style="color:#e74c3c; border-color:#e74c3c;">
                    🗑 حذف
                </button>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>

<script>
jQuery(function($){
    var ajaxUrl = rsyiSA.ajaxUrl, nonce = rsyiSA.nonce;

    function showNotice(msg, type){
        var $n = $('#rsyi-course-notice');
        $n.removeClass('notice-success notice-error').addClass('notice-' + type)
          .html('<p>' + msg + '</p>').show();
        setTimeout(function(){ $n.fadeOut(); }, 4000);
    }

    // ── Save course ───────────────────────────────────────────────────────────
    $('#rsyi-course-form').on('submit', function(e){
        e.preventDefault();
        var id = $('#course-id').val();
        $.post(ajaxUrl, {
            action     : 'rsyi_save_course',
            _nonce     : nonce,
            id         : id,
            name_ar    : $('#course-name-ar').val(),
            name_en    : $('#course-name-en').val(),
            description: $('#course-desc').val()
        }, function(res){
            if(res.success){
                showNotice('✅ ' + res.data.message, 'success');
                setTimeout(function(){ location.reload(); }, 1200);
            } else {
                showNotice('❌ ' + res.data.message, 'error');
            }
        });
    });

    // ── Edit course (fill form) ───────────────────────────────────────────────
    $(document).on('click', '.rsyi-edit-course', function(){
        var $b = $(this);
        $('#course-id').val($b.data('id'));
        $('#course-name-ar').val($b.data('name-ar'));
        $('#course-name-en').val($b.data('name-en'));
        $('#course-desc').val($b.data('desc'));
        $('#course-save-btn').text('💾 تحديث الكورس');
        $('#course-cancel-btn').show();
        $('html, body').animate({ scrollTop: 0 }, 400);
    });

    $('#course-cancel-btn').on('click', function(){
        $('#course-id').val(0);
        $('#course-name-ar, #course-name-en, #course-desc').val('');
        $('#course-save-btn').text('💾 حفظ الكورس');
        $(this).hide();
    });

    // ── Toggle status ─────────────────────────────────────────────────────────
    $(document).on('click', '.rsyi-toggle-course', function(){
        var $b = $(this), id = $b.data('id');
        $.post(ajaxUrl, { action:'rsyi_toggle_course_status', _nonce:nonce, id:id }, function(res){
            if(res.success){
                var active = res.data.is_active;
                $('#status-badge-' + id).text(active ? 'نشط' : 'موقوف')
                    .css('background', active ? '#27ae60' : '#999');
                $b.text(active ? '⏸ إيقاف' : '▶ تفعيل')
                  .css('color', active ? '#e67e22' : '#27ae60')
                  .data('active', active ? 1 : 0);
            }
        });
    });

    // ── Delete course ─────────────────────────────────────────────────────────
    $(document).on('click', '.rsyi-delete-course', function(){
        if(!confirm('هل تريد حذف هذا الكورس؟ سيتم إزالته من جميع التقارير السابقة.')) return;
        var $b = $(this), id = $b.data('id');
        $.post(ajaxUrl, { action:'rsyi_delete_course', _nonce:nonce, id:id }, function(res){
            if(res.success){
                $('#course-row-' + id).fadeOut(400, function(){ $(this).remove(); });
                showNotice('✅ ' + res.data.message, 'success');
            } else {
                showNotice('❌ ' + res.data.message, 'error');
            }
        });
    });

    // ── Boss Man: load students by cohort ─────────────────────────────────────
    $('#bm-cohort').on('change', function(){
        var cohortId = $(this).val();
        if(!cohortId){ $('#bm-students-list').hide(); $('#bm-no-cohort').show(); return; }
        $.get(ajaxUrl, { action:'rsyi_get_cohort_students_for_bm', _nonce:nonce, cohort_id:cohortId }, function(res){
            if(res.success && res.data.students){
                var html = '';
                $.each(res.data.students, function(i, s){
                    html += '<div style="display:flex; align-items:center; justify-content:space-between; padding:8px 0; border-bottom:1px solid #f0f0f0;">' +
                        '<span>' + s.arabic_full_name + '</span>' +
                        '<button class="button button-small rsyi-bm-toggle" data-id="' + s.id + '" data-bm="' + s.is_boss_man + '" style="' + (s.is_boss_man ? 'color:#e67e22;background:#fff8f0;' : '') + '">' +
                        (s.is_boss_man ? '👮 حكمدار' : 'تعيين حكمداراً') + '</button>' +
                        '</div>';
                });
                $('#bm-students-list').html(html || '<p style="color:#999;">لا يوجد طلاب نشطون</p>').show();
                $('#bm-no-cohort').hide();
            }
        });
    });

    $(document).on('click', '.rsyi-bm-toggle', function(){
        var $b = $(this), id = $b.data('id');
        $.post(ajaxUrl, { action:'rsyi_toggle_boss_man', _nonce:nonce, student_id:id }, function(res){
            if(res.success){
                var isBm = res.data.is_boss_man;
                $b.data('bm', isBm ? 1 : 0)
                  .text(isBm ? '👮 حكمدار' : 'تعيين حكمداراً')
                  .css({ color: isBm ? '#e67e22' : '', background: isBm ? '#fff8f0' : '' });
                showNotice(isBm ? '✅ تم تعيين الطالب حكمداراً' : 'تم إلغاء التعيين', 'success');
            }
        });
    });
});
</script>

