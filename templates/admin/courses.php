<?php
/**
 * Admin – Courses Management & Boss Man Weekly Schedule
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

global $wpdb;

$courses = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}rsyi_courses ORDER BY name_ar ASC"
);
$cohorts = $wpdb->get_results(
    "SELECT id, name FROM {$wpdb->prefix}rsyi_cohorts WHERE is_active = 1 ORDER BY name ASC"
);

// Build weeks (Sun–Sat) starting from this week's Sunday
$today    = current_time( 'Y-m-d' );
$ts_today = strtotime( $today );
$this_sun = date( 'Y-m-d', $ts_today - (int) date( 'w', $ts_today ) * DAY_IN_SECONDS );
$weeks    = [];
for ( $i = 0; $i < 5; $i++ ) {
    $sun = date( 'Y-m-d', strtotime( "+{$i} week", strtotime( $this_sun ) ) );
    $sat = date( 'Y-m-d', strtotime( '+6 days', strtotime( $sun ) ) );
    $weeks[] = [ 'start' => $sun, 'end' => $sat, 'is_current' => ( $i === 0 ) ];
}
// Also include 2 past weeks for reference
$past_weeks = [];
for ( $i = 1; $i <= 2; $i++ ) {
    $sun = date( 'Y-m-d', strtotime( "-{$i} week", strtotime( $this_sun ) ) );
    $sat = date( 'Y-m-d', strtotime( '+6 days', strtotime( $sun ) ) );
    $past_weeks[] = [ 'start' => $sun, 'end' => $sat ];
}
?>
<div id="rsyi-course-notice" style="display:none; margin:12px 0;" class="notice"></div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:32px;">

    <!-- ── Add / Edit Course ── -->
    <div style="background:#fff; border:1px solid #ccd0d4; border-radius:8px; padding:24px;">
        <h2 style="margin-top:0; font-size:16px; border-bottom:2px solid #0073aa; padding-bottom:8px; direction:rtl;">
            ➕ إضافة كورس / Add Course
        </h2>
        <form id="rsyi-course-form">
            <input type="hidden" id="course-id" value="0">
            <table class="form-table" style="margin-top:0;">
                <tr>
                    <th style="width:150px; direction:rtl; text-align:right;">
                        <label for="course-name-ar">الاسم بالعربي / Name (AR) <span style="color:red">*</span></label>
                    </th>
                    <td><input type="text" id="course-name-ar" class="regular-text" placeholder="مثال: كورس الرادار" style="width:100%;"></td>
                </tr>
                <tr>
                    <th style="direction:rtl; text-align:right;"><label for="course-name-en">الاسم بالإنجليزي / Name (EN)</label></th>
                    <td><input type="text" id="course-name-en" class="regular-text" placeholder="e.g. Radar Course" style="width:100%;"></td>
                </tr>
                <tr>
                    <th style="direction:rtl; text-align:right;"><label for="course-desc">وصف / Description</label></th>
                    <td><textarea id="course-desc" rows="3" style="width:100%;"></textarea></td>
                </tr>
            </table>
            <div style="margin-top:16px; display:flex; gap:10px;">
                <button type="submit" class="button button-primary" id="course-save-btn">💾 حفظ / Save</button>
                <button type="button" class="button" id="course-cancel-btn" style="display:none;">إلغاء / Cancel</button>
            </div>
        </form>
    </div>

    <!-- ── Boss Man Weekly Schedule ── -->
    <div style="background:#fff; border:1px solid #ccd0d4; border-radius:8px; padding:24px;">
        <h2 style="margin-top:0; font-size:16px; border-bottom:2px solid #e67e22; padding-bottom:8px; direction:rtl;">
            👮 جدول الحكمداري الأسبوعي / Boss Man Weekly Schedule
        </h2>
        <p style="color:#666; font-size:12px; direction:rtl;">الحكمدار يتغير كل أسبوع — اختر الدفعة وعيّن حكمدار لكل أسبوع.</p>
        <select id="bm-cohort" style="width:100%; margin-bottom:14px; padding:6px;">
            <option value="">— اختر الدفعة / Select Cohort —</option>
            <?php foreach ( $cohorts as $c ) : ?>
            <option value="<?php echo (int) $c->id; ?>"><?php echo esc_html( $c->name ); ?></option>
            <?php endforeach; ?>
        </select>

        <div id="bm-schedule-wrap" style="display:none;">
            <!-- Upcoming + current weeks -->
            <div style="font-size:11px; font-weight:700; color:#0073aa; text-transform:uppercase; margin-bottom:8px;">
                الأسابيع القادمة / Upcoming Weeks
            </div>
            <?php foreach ( $weeks as $w ) :
                $label = ( $w['is_current'] ? '✅ هذا الأسبوع / This Week — ' : '' )
                       . date( 'j M', strtotime( $w['start'] ) ) . ' – ' . date( 'j M Y', strtotime( $w['end'] ) );
            ?>
            <div class="bm-week-row" data-week="<?php echo esc_attr( $w['start'] ); ?>"
                 style="border:1px solid <?php echo $w['is_current'] ? '#27ae60' : '#dee2e6'; ?>; border-radius:6px; padding:10px 12px; margin-bottom:8px; background:<?php echo $w['is_current'] ? '#f0fff4' : '#fafafa'; ?>;">
                <div style="font-size:12px; font-weight:700; color:<?php echo $w['is_current'] ? '#27ae60' : '#333'; ?>; margin-bottom:6px;"><?php echo esc_html( $label ); ?></div>
                <div style="display:flex; gap:8px; align-items:center;">
                    <select class="bm-student-sel" style="flex:1; padding:5px;">
                        <option value="">— لا يوجد حكمدار / No assignment —</option>
                    </select>
                    <button class="button button-small bm-assign-btn" style="background:#e67e22; color:#fff; border-color:#d35400;">
                        حفظ / Save
                    </button>
                </div>
                <div class="bm-current-name" style="font-size:11px; color:#888; margin-top:4px;"></div>
            </div>
            <?php endforeach; ?>

            <!-- Past weeks (read-only) -->
            <div style="font-size:11px; font-weight:700; color:#888; text-transform:uppercase; margin:12px 0 8px;">
                الأسابيع السابقة / Past Weeks
            </div>
            <?php foreach ( $past_weeks as $w ) :
                $label = date( 'j M', strtotime( $w['start'] ) ) . ' – ' . date( 'j M Y', strtotime( $w['end'] ) );
            ?>
            <div class="bm-week-row bm-past-row" data-week="<?php echo esc_attr( $w['start'] ); ?>"
                 style="border:1px solid #eee; border-radius:6px; padding:8px 12px; margin-bottom:6px; background:#fafafa; opacity:.8;">
                <div style="font-size:12px; color:#777;"><?php echo esc_html( $label ); ?> — <span class="bm-current-name" style="color:#555;">—</span></div>
            </div>
            <?php endforeach; ?>
        </div>
        <p id="bm-no-cohort" style="color:#aaa; font-style:italic; font-size:13px; text-align:center; margin:20px 0;">اختر دفعة لعرض الجدول / Select a cohort to view the schedule</p>
    </div>
</div>

<!-- ── Courses Table ── -->
<h2 style="font-size:15px; direction:rtl;">📋 الكورسات المسجلة / Registered Courses (<?php echo count( $courses ); ?>)</h2>

<table class="widefat rsyi-table striped" id="courses-table">
    <thead>
        <tr>
            <th>#</th>
            <th>الاسم بالعربي / Name (AR)</th>
            <th>الاسم بالإنجليزي / Name (EN)</th>
            <th>الوصف / Description</th>
            <th>الحالة / Status</th>
            <th>التاريخ / Date</th>
            <th>إجراءات / Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php if ( empty( $courses ) ) : ?>
        <tr><td colspan="7" style="text-align:center; color:#999; padding:20px;">
            لا توجد كورسات — أضف أول كورس من الأعلى / No courses yet — add one above
        </td></tr>
    <?php else : ?>
        <?php foreach ( $courses as $c ) : ?>
        <tr id="course-row-<?php echo (int) $c->id; ?>">
            <td><?php echo (int) $c->id; ?></td>
            <td><strong><?php echo esc_html( $c->name_ar ); ?></strong></td>
            <td><?php echo esc_html( $c->name_en ?: '—' ); ?></td>
            <td style="font-size:12px; color:#666; max-width:200px;"><?php echo esc_html( $c->description ?: '—' ); ?></td>
            <td>
                <span id="status-badge-<?php echo (int) $c->id; ?>"
                      style="background:<?php echo $c->is_active ? '#27ae60' : '#999'; ?>; color:#fff; padding:3px 10px; border-radius:20px; font-size:11px;">
                    <?php echo $c->is_active ? 'نشط / Active' : 'موقوف / Inactive'; ?>
                </span>
            </td>
            <td style="font-size:12px;"><?php echo esc_html( wp_date( 'Y-m-d', strtotime( $c->created_at ) ) ); ?></td>
            <td style="white-space:nowrap;">
                <button class="button button-small rsyi-edit-course"
                        data-id="<?php echo (int) $c->id; ?>"
                        data-name-ar="<?php echo esc_attr( $c->name_ar ); ?>"
                        data-name-en="<?php echo esc_attr( $c->name_en ); ?>"
                        data-desc="<?php echo esc_attr( $c->description ); ?>">✏️</button>
                <button class="button button-small rsyi-toggle-course" data-id="<?php echo (int) $c->id; ?>"
                        style="color:<?php echo $c->is_active ? '#e67e22' : '#27ae60'; ?>;">
                    <?php echo $c->is_active ? '⏸' : '▶'; ?>
                </button>
                <button class="button button-small rsyi-delete-course" data-id="<?php echo (int) $c->id; ?>"
                        style="color:#e74c3c; border-color:#e74c3c;">🗑</button>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>

<script>
jQuery(function($){
    var ajaxUrl = rsyiSA.ajaxUrl, nonce = rsyiSA.nonce;
    var allStudents = [], currentCohort = 0;
    var allWeeks    = <?php echo wp_json_encode( array_merge( $weeks, $past_weeks ) ); ?>;

    function showNotice(msg, type){
        $('#rsyi-course-notice').removeClass('notice-success notice-error')
            .addClass('notice-' + type).html('<p>' + msg + '</p>').show();
        setTimeout(function(){ $('#rsyi-course-notice').fadeOut(); }, 4000);
    }

    // ── Course CRUD ────────────────────────────────────────────────────────
    $('#rsyi-course-form').on('submit', function(e){
        e.preventDefault();
        $.post(ajaxUrl, {
            action:'rsyi_save_course', _nonce:nonce,
            id:$('#course-id').val(), name_ar:$('#course-name-ar').val(),
            name_en:$('#course-name-en').val(), description:$('#course-desc').val()
        }, function(res){
            if(res.success){ showNotice('✅ ' + res.data.message, 'success'); setTimeout(function(){ location.reload(); }, 1200); }
            else { showNotice('❌ ' + res.data.message, 'error'); }
        });
    });
    $(document).on('click', '.rsyi-edit-course', function(){
        var $b = $(this);
        $('#course-id').val($b.data('id'));
        $('#course-name-ar').val($b.data('name-ar'));
        $('#course-name-en').val($b.data('name-en'));
        $('#course-desc').val($b.data('desc'));
        $('#course-save-btn').text('💾 تحديث / Update');
        $('#course-cancel-btn').show();
        $('html,body').animate({scrollTop:0}, 300);
    });
    $('#course-cancel-btn').on('click', function(){
        $('#course-id').val(0); $('#course-name-ar,#course-name-en,#course-desc').val('');
        $('#course-save-btn').text('💾 حفظ / Save'); $(this).hide();
    });
    $(document).on('click', '.rsyi-toggle-course', function(){
        var $b=$(this), id=$b.data('id');
        $.post(ajaxUrl,{action:'rsyi_toggle_course_status',_nonce:nonce,id:id},function(res){
            if(res.success){
                var a=res.data.is_active;
                $('#status-badge-'+id).text(a?'نشط / Active':'موقوف / Inactive').css('background',a?'#27ae60':'#999');
                $b.text(a?'⏸':'▶').css('color',a?'#e67e22':'#27ae60');
            }
        });
    });
    $(document).on('click', '.rsyi-delete-course', function(){
        if(!confirm('Delete this course? / حذف هذا الكورس؟')) return;
        var $b=$(this), id=$b.data('id');
        $.post(ajaxUrl,{action:'rsyi_delete_course',_nonce:nonce,id:id},function(res){
            if(res.success){ $('#course-row-'+id).fadeOut(300,function(){$(this).remove();}); showNotice('✅ '+res.data.message,'success'); }
            else { showNotice('❌ '+res.data.message,'error'); }
        });
    });

    // ── Boss Man Weekly Schedule ───────────────────────────────────────────
    $('#bm-cohort').on('change', function(){
        currentCohort = $(this).val();
        if(!currentCohort){ $('#bm-schedule-wrap').hide(); $('#bm-no-cohort').show(); return; }
        $.get(ajaxUrl,{action:'rsyi_get_boss_schedule',_nonce:nonce,cohort_id:currentCohort},function(res){
            if(!res.success) return;
            allStudents = res.data.students;

            // Build schedule lookup by week_start
            var schedMap = {};
            $.each(res.data.schedule, function(i,s){ schedMap[s.week_start] = s; });

            // Populate each week row
            $('.bm-week-row').each(function(){
                var $row = $(this);
                var week = $row.data('week');
                var $sel = $row.find('.bm-student-sel');
                var $lbl = $row.find('.bm-current-name');

                if($sel.length){
                    // Build options
                    var html='<option value="">— لا يوجد حكمدار / No assignment —</option>';
                    $.each(allStudents, function(i,s){
                        html+='<option value="'+s.id+'">'+s.arabic_full_name+'</option>';
                    });
                    $sel.html(html);
                }

                // Set current value
                if(schedMap[week]){
                    if($sel.length) $sel.val(schedMap[week].student_id);
                    $lbl.text('👮 ' + (schedMap[week].arabic_full_name || ''));
                } else {
                    $lbl.text('—');
                }
            });

            $('#bm-schedule-wrap').show();
            $('#bm-no-cohort').hide();
        });
    });

    $(document).on('click', '.bm-assign-btn', function(){
        var $row     = $(this).closest('.bm-week-row');
        var week     = $row.data('week');
        var $sel     = $row.find('.bm-student-sel');
        var student  = $sel.val();
        var $btn     = $(this).prop('disabled', true);
        $.post(ajaxUrl,{
            action:'rsyi_save_boss_schedule', _nonce:nonce,
            cohort_id:currentCohort, student_id:student, week_start:week
        }, function(res){
            $btn.prop('disabled', false);
            if(res.success){
                $row.find('.bm-current-name').text(res.data.name ? '👮 ' + res.data.name : '—');
                showNotice('✅ ' + res.data.message, 'success');
            } else {
                showNotice('❌ ' + res.data.message, 'error');
            }
        });
    });
});
</script>
