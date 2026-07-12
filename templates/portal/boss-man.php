<?php
/**
 * Portal – Boss Man Daily Study Report / لوحة تحكم الحكمدار
 * Variables: $profile, $cohort, $students, $courses
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

$today         = current_time( 'Y-m-d' );
$dashboard_url = get_option( 'rsyi_page_dashboard' ) ? get_permalink( get_option( 'rsyi_page_dashboard' ) ) : '';

// Build courses JSON for JS
$courses_json = wp_json_encode( array_map( fn( $c ) => [
    'id'      => (int) $c->id,
    'name_ar' => $c->name_ar,
    'name_en' => $c->name_en ?: '',
], $courses ) );
?>
<style>
.rsyi-bm-portal .bm-course-row { display:flex; gap:8px; align-items:center; margin-bottom:6px; }
.rsyi-bm-portal .bm-course-row select { flex:2; border:1px solid #ccd0d4; border-radius:6px; padding:6px 10px; font-size:13px; min-width:0; }
.rsyi-bm-portal .bm-course-row input  { flex:2; border:1px solid #ccd0d4; border-radius:6px; padding:6px 10px; font-size:13px; min-width:0; box-sizing:border-box; }
.rsyi-bm-portal .bm-remove-btn { background:none; border:1px solid #e74c3c; color:#e74c3c; border-radius:4px; padding:4px 8px; cursor:pointer; font-size:14px; flex-shrink:0; }
.rsyi-bm-portal .bm-remove-btn:hover { background:#e74c3c; color:#fff; }
.rsyi-bm-portal .bm-add-course-btn { background:none; border:1px dashed #0073aa; color:#0073aa; border-radius:6px; padding:5px 14px; cursor:pointer; font-size:12px; font-weight:600; width:100%; margin-top:4px; }
.rsyi-bm-portal .bm-add-course-btn:hover { background:#e8f4fb; }
</style>

<div class="rsyi-portal rsyi-bm-portal" dir="rtl" style="font-family:sans-serif; max-width:980px; margin:0 auto;">

    <!-- Header -->
    <div style="background:linear-gradient(135deg,#e67e22,#d35400); color:#fff; border-radius:12px; padding:24px 28px; margin-bottom:24px; display:flex; align-items:center; gap:18px;">
        <div style="font-size:48px;">👮</div>
        <div>
            <h2 style="margin:0 0 4px; font-size:20px;">لوحة تحكم الحكمدار / Boss Man Dashboard</h2>
            <p style="margin:0; opacity:.9; font-size:14px;">
                <?php echo esc_html( $profile->arabic_full_name ); ?> —
                دفعة / Cohort: <strong><?php echo esc_html( isset($cohort->name) ? $cohort->name : '—' ); ?></strong>
            </p>
        </div>
        <?php if ( $dashboard_url ) : ?>
        <a href="<?php echo esc_url( $dashboard_url ); ?>"
           style="margin-right:auto; background:rgba(255,255,255,.2); color:#fff; padding:8px 16px; border-radius:6px; text-decoration:none; font-size:13px;">
            ← الرئيسية / Home
        </a>
        <?php endif; ?>
    </div>

    <!-- Tabs -->
    <div style="display:flex; gap:0; margin-bottom:24px; border-bottom:2px solid #dee2e6;">
        <button type="button" class="rsyi-bm-tab active" data-tab="daily"
                style="padding:10px 24px; border:none; background:none; cursor:pointer; font-size:14px; font-weight:700; color:#e67e22; border-bottom:3px solid #e67e22; margin-bottom:-2px;">
            📋 تقرير اليوم / Today's Report
        </button>
        <button type="button" class="rsyi-bm-tab" data-tab="history"
                style="padding:10px 24px; border:none; background:none; cursor:pointer; font-size:14px; color:#666;">
            📅 السجل التاريخي / History
        </button>
    </div>

    <!-- Daily Report Tab -->
    <div id="rsyi-bm-tab-daily">
        <div style="background:#fff; border:1px solid #dee2e6; border-radius:10px; padding:20px; margin-bottom:20px;">

            <div style="display:flex; align-items:center; gap:14px; margin-bottom:20px; flex-wrap:wrap;">
                <label style="font-weight:700; font-size:14px;">📅 التاريخ / Date:</label>
                <input type="date" id="bm-report-date" value="<?php echo esc_attr( $today ); ?>"
                       max="<?php echo esc_attr( $today ); ?>"
                       style="border:1px solid #ccd0d4; border-radius:6px; padding:7px 12px; font-size:14px;">
                <button type="button" id="bm-load-btn" class="button" style="background:#0073aa; color:#fff; border-color:#0073aa; padding:7px 16px;">
                    تحميل البيانات / Load Data
                </button>
            </div>

            <?php if ( empty( $students ) ) : ?>
            <div style="text-align:center; padding:30px; color:#999;">
                <div style="font-size:40px; margin-bottom:10px;">👥</div>
                <p>لا يوجد طلاب نشطون في دفعتك. / No active students in your cohort.</p>
            </div>
            <?php else : ?>

            <div id="bm-notice" style="display:none; margin-bottom:12px;" class="notice"></div>

            <p style="margin:0 0 12px; font-size:12px; color:#888; direction:rtl;">
                💡 يمكن إضافة أكثر من كورس أو نشاط لكل طالب / Each student can have multiple courses or activities
            </p>

            <table style="width:100%; border-collapse:collapse;" id="bm-students-table">
                <thead>
                    <tr style="background:#f8f9fa; border-bottom:2px solid #dee2e6;">
                        <th style="padding:10px 14px; text-align:right; font-size:13px; width:40px;">#</th>
                        <th style="padding:10px 14px; text-align:right; font-size:13px; width:200px;">اسم الطالب / Student</th>
                        <th style="padding:10px 14px; text-align:right; font-size:13px;">الكورسات والأنشطة / Courses & Activities</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i = 1; foreach ( $students as $st ) : ?>
                <tr style="border-bottom:1px solid #f0f0f0; vertical-align:top;" class="bm-student-row"
                    data-id="<?php echo (int) $st->id; ?>">
                    <td style="padding:12px 14px; color:#888; font-size:13px;"><?php echo $i++; ?></td>
                    <td style="padding:12px 14px;">
                        <strong style="font-size:14px;"><?php echo esc_html( $st->arabic_full_name ); ?></strong>
                        <?php if ( $st->english_full_name ) : ?>
                        <span style="display:block; font-size:11px; color:#888;"><?php echo esc_html( $st->english_full_name ); ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:10px 14px;">
                        <!-- Course rows container -->
                        <div class="bm-courses-container">
                            <div class="bm-course-row">
                                <select class="bm-course-select">
                                    <option value="">— اختر الكورس / Select Course —</option>
                                    <?php foreach ( $courses as $cr ) : ?>
                                    <option value="<?php echo (int) $cr->id; ?>">
                                        <?php echo esc_html( $cr->name_ar ); ?><?php if ( $cr->name_en ) : ?> (<?php echo esc_html( $cr->name_en ); ?>)<?php endif; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" class="bm-notes-input"
                                       placeholder="ملاحظة / Note…">
                                <button type="button" class="bm-remove-btn" title="حذف / Remove" style="display:none;">✕</button>
                            </div>
                        </div>
                        <button type="button" class="bm-add-course-btn">+ إضافة كورس / Add Course</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <div style="margin-top:20px; display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                <button type="button" id="bm-save-btn" class="button button-primary"
                        style="background:#e67e22; border-color:#d35400; color:#fff; padding:10px 28px; font-size:15px; font-weight:700;">
                    💾 حفظ التقرير / Save Report
                </button>
                <button type="button" id="bm-clear-btn" class="button" style="padding:10px 18px;">
                    🗑 مسح الكل / Clear All
                </button>
                <span id="bm-save-status" style="font-size:13px; color:#27ae60; display:none;"></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- History Tab -->
    <div id="rsyi-bm-tab-history" style="display:none;">
        <div style="background:#fff; border:1px solid #dee2e6; border-radius:10px; padding:20px;">
            <div style="display:flex; gap:14px; align-items:flex-end; margin-bottom:20px; flex-wrap:wrap;">
                <div>
                    <label style="display:block; font-weight:700; font-size:13px; margin-bottom:4px;">التاريخ / Date</label>
                    <input type="date" id="hist-date" value="<?php echo esc_attr( $today ); ?>"
                           style="border:1px solid #ccd0d4; border-radius:6px; padding:7px 12px;">
                </div>
                <button type="button" id="hist-load-btn" class="button" style="background:#0073aa; color:#fff; border-color:#0073aa; padding:7px 16px;">
                    🔍 عرض / View
                </button>
            </div>
            <div id="hist-content" style="text-align:center; color:#999; padding:30px;">
                اختر تاريخاً واضغط عرض / Choose a date and click View
            </div>
        </div>
    </div>
</div>

<script>
jQuery(function($){
    var ajaxUrl  = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
    var nonce    = '<?php echo esc_js( wp_create_nonce( 'rsyi_sa_portal' ) ); ?>';
    var cohortId = <?php echo (int) ( isset( $cohort->id ) ? $cohort->id : 0 ); ?>;
    var coursesData = <?php echo $courses_json ?: '[]'; ?>;

    // Build a course <select> HTML (re-usable)
    function buildCourseSelect(selectedId) {
        var html = '<select class="bm-course-select"><option value="">— اختر الكورس / Select Course —</option>';
        $.each(coursesData, function(i, c){
            var label = c.name_ar + (c.name_en ? ' (' + c.name_en + ')' : '');
            html += '<option value="' + c.id + '"' + (c.id === selectedId ? ' selected' : '') + '>' + label + '</option>';
        });
        html += '</select>';
        return html;
    }

    function addCourseRow($container, courseId, notes) {
        var $row = $('<div class="bm-course-row"></div>');
        $row.append(buildCourseSelect(courseId || 0));
        $row.append('<input type="text" class="bm-notes-input" placeholder="ملاحظة / Note…" value="' + ($('<div>').text(notes || '').html()) + '">');
        $row.append('<button type="button" class="bm-remove-btn" title="حذف / Remove">✕</button>');
        $container.append($row);
        // Show remove buttons when more than one row
        updateRemoveBtns($container);
    }

    function updateRemoveBtns($container) {
        var rows = $container.find('.bm-course-row');
        rows.find('.bm-remove-btn').toggle(rows.length > 1);
    }

    // Add course button
    $(document).on('click', '.bm-add-course-btn', function(e){
        e.preventDefault();
        var $container = $(this).closest('td').find('.bm-courses-container');
        addCourseRow($container, 0, '');
    });

    // Remove course row
    $(document).on('click', '.bm-remove-btn', function(e){
        e.preventDefault();
        var $container = $(this).closest('.bm-courses-container');
        $(this).closest('.bm-course-row').remove();
        updateRemoveBtns($container);
    });

    // Tab switching
    $('.rsyi-bm-tab').on('click', function(){
        var tab = $(this).data('tab');
        $('.rsyi-bm-tab').css({ color:'#666', borderBottom:'none', fontWeight:'400' });
        $(this).css({ color:'#e67e22', borderBottom:'3px solid #e67e22', fontWeight:'700' });
        $('[id^=rsyi-bm-tab-]').hide();
        $('#rsyi-bm-tab-' + tab).show();
    });

    function showNotice(msg, type){
        $('#bm-notice').removeClass('notice-success notice-error')
            .addClass('notice-' + type).html('<p>' + msg + '</p>').show();
        setTimeout(function(){ $('#bm-notice').fadeOut(); }, 5000);
    }

    // Load existing data for a date
    function loadReport(date){
        // Reset all course rows to one empty row
        $('.bm-student-row').each(function(){
            var $container = $(this).find('.bm-courses-container');
            $container.find('.bm-course-row:not(:first)').remove();
            $container.find('.bm-course-select').val('');
            $container.find('.bm-notes-input').val('');
            updateRemoveBtns($container);
        });

        $.get(ajaxUrl, {
            action      : 'rsyi_get_boss_report',
            _nonce      : nonce,
            report_date : date,
            cohort_id   : cohortId
        }, function(res){
            if (!res.success || !res.data.rows.length) return;

            // Group rows by student_id
            var byStudent = {};
            $.each(res.data.rows, function(i, r){
                if (!byStudent[r.student_id]) byStudent[r.student_id] = [];
                byStudent[r.student_id].push(r);
            });

            $.each(byStudent, function(sid, courses){
                var $row = $('.bm-student-row[data-id="' + sid + '"]');
                var $container = $row.find('.bm-courses-container');
                // Fill first row
                $container.find('.bm-course-row:first .bm-course-select').val(courses[0].course_id || '');
                $container.find('.bm-course-row:first .bm-notes-input').val(courses[0].notes || '');
                // Add additional rows
                for (var i = 1; i < courses.length; i++) {
                    addCourseRow($container, parseInt(courses[i].course_id) || 0, courses[i].notes || '');
                }
                updateRemoveBtns($container);
            });
        });
    }
    loadReport($('#bm-report-date').val());

    $('#bm-load-btn').on('click', function(e){
        e.preventDefault();
        loadReport($('#bm-report-date').val());
    });

    // Save report — flat array: one entry per course per student
    $('#bm-save-btn').on('click', function(){
        var $btn = $(this).prop('disabled', true).text('جاري الحفظ… / Saving…');
        var rows = [];
        $('.bm-student-row').each(function(){
            var sid = $(this).data('id');
            var hasAny = false;
            $(this).find('.bm-course-row').each(function(){
                var courseId = $(this).find('.bm-course-select').val();
                var notes    = $(this).find('.bm-notes-input').val();
                rows.push({ student_id: sid, course_id: courseId, notes: notes });
                if (courseId) hasAny = true;
            });
            // If no course selected, still send one marker row so old records get cleared
            if (!hasAny && $(this).find('.bm-course-row').length === 0) {
                rows.push({ student_id: sid, course_id: '', notes: '' });
            }
        });
        $.post(ajaxUrl, {
            action      : 'rsyi_save_boss_report',
            _nonce      : nonce,
            report_date : $('#bm-report-date').val(),
            rows        : rows
        }, function(res){
            $btn.prop('disabled', false).html('💾 حفظ التقرير / Save Report');
            if (res.success) {
                showNotice('✅ ' + res.data.message, 'success');
                $('#bm-save-status').text('✅ تم الحفظ / Saved').show().delay(3000).fadeOut();
            } else {
                showNotice('❌ ' + (res.data.message || 'حدث خطأ / Error'), 'error');
            }
        }).fail(function(){
            $btn.prop('disabled', false).html('💾 حفظ التقرير / Save Report');
            showNotice('❌ فشل الاتصال / Connection failed.', 'error');
        });
    });

    // Clear all
    $('#bm-clear-btn').on('click', function(e){
        e.preventDefault();
        if (!confirm('هل تريد مسح جميع الاختيارات؟ / Clear all selections?')) return;
        $('.bm-student-row').each(function(){
            var $container = $(this).find('.bm-courses-container');
            $container.find('.bm-course-row:not(:first)').remove();
            $container.find('.bm-course-select').val('');
            $container.find('.bm-notes-input').val('');
            updateRemoveBtns($container);
        });
    });

    // History tab — group by student
    $('#hist-load-btn').on('click', function(e){
        e.preventDefault();
        var date = $('#hist-date').val();
        if (!date) return;
        var $c = $('#hist-content').html('<p style="color:#888;">جاري التحميل… / Loading…</p>');
        $.get(ajaxUrl, {
            action      : 'rsyi_get_boss_report',
            _nonce      : nonce,
            report_date : date,
            cohort_id   : cohortId
        }, function(res){
            if (!res.success || !res.data.rows.length){
                $c.html('<p style="color:#999; text-align:center; padding:20px;">لا توجد بيانات لهذا اليوم / No data for this date</p>');
                return;
            }
            // Group by student
            var students = {};
            var order = [];
            $.each(res.data.rows, function(i, r){
                if (!students[r.student_id]) {
                    students[r.student_id] = { name: r.arabic_full_name, courses: [] };
                    order.push(r.student_id);
                }
                students[r.student_id].courses.push({ name_ar: r.course_name_ar, name_en: r.course_name_en, notes: r.notes });
            });
            var html = '<table style="width:100%; border-collapse:collapse;">' +
                '<thead><tr style="background:#f8f9fa; border-bottom:2px solid #dee2e6;">' +
                '<th style="padding:8px 12px; text-align:right; width:180px;">الطالب / Student</th>' +
                '<th style="padding:8px 12px; text-align:right;">الكورسات والأنشطة / Courses & Activities</th>' +
                '</tr></thead><tbody>';
            $.each(order, function(i, sid){
                var st = students[sid];
                var coursesHtml = '';
                $.each(st.courses, function(j, cr){
                    coursesHtml += '<div style="margin-bottom:4px;">' +
                        (cr.name_ar
                            ? '<span style="background:#e3f2fd;color:#0073aa;padding:2px 10px;border-radius:20px;font-size:12px;">' + cr.name_ar + (cr.name_en ? ' / ' + cr.name_en : '') + '</span>'
                            : '<span style="color:#999;">—</span>') +
                        (cr.notes ? ' <span style="font-size:12px;color:#666;">– ' + cr.notes + '</span>' : '') +
                        '</div>';
                });
                html += '<tr style="border-bottom:1px solid #f0f0f0;">' +
                    '<td style="padding:10px 12px; font-weight:600;">' + st.name + '</td>' +
                    '<td style="padding:10px 12px;">' + coursesHtml + '</td>' +
                    '</tr>';
            });
            html += '</tbody></table>';
            $c.html(html);
        });
    });
});
</script>
