/**
 * Boss Man Portal — all event handlers.
 * Config is injected by wp_localize_script as window.rsyiBMConfig.
 * This file is enqueued in wp_footer with jQuery dependency, so:
 *   • jQuery is always available
 *   • DOM is fully built
 *   • Script-tag stripping in content cannot affect this file
 */
(function ($) {
    'use strict';

    /* Bail silently if not on the boss man page */
    if (typeof window.rsyiBMConfig === 'undefined') { return; }

    var ajaxUrl     = window.rsyiBMConfig.ajaxUrl     || '';
    var nonce       = window.rsyiBMConfig.nonce       || '';
    var cohortId    = window.rsyiBMConfig.cohortId    || 0;
    var coursesData = window.rsyiBMConfig.coursesData || [];

    /* ── Course select builder ─────────────────────────────────────────── */
    function buildCourseSelect(selectedId) {
        var html = '<select class="bm-course-select">'
            + '<option value="">— اختر الكورس / Select Course —</option>';
        $.each(coursesData, function (i, c) {
            var label = c.name_ar + (c.name_en ? ' (' + c.name_en + ')' : '');
            var sel   = (parseInt(c.id, 10) === parseInt(selectedId, 10)) ? ' selected' : '';
            html += '<option value="' + c.id + '"' + sel + '>' + label + '</option>';
        });
        html += '</select>';
        return html;
    }

    /* ── Add a course row to a container ──────────────────────────────── */
    function addCourseRow($container, courseId, notes) {
        var $row = $('<div class="bm-course-row"></div>');
        $row.append(buildCourseSelect(courseId || 0));
        $row.append('<input type="text" class="bm-notes-input" placeholder="ملاحظة / Note…">');
        $row.find('.bm-notes-input').val(notes || '');
        $row.append('<button type="button" class="bm-remove-btn" title="حذف / Remove">✕</button>');
        $container.append($row);
        _syncRemoveBtns($container);
    }

    function _syncRemoveBtns($container) {
        var rows = $container.find('.bm-course-row');
        rows.find('.bm-remove-btn').toggle(rows.length > 1);
    }

    /* ── Notice banner ─────────────────────────────────────────────────── */
    function showNotice(msg, type) {
        var $n = $('#bm-notice');
        $n.removeClass('success error').addClass(type).html(msg).show();
        setTimeout(function () { $n.fadeOut(); }, 5000);
    }

    /* ══ Tab switching ═══════════════════════════════════════════════════ */
    $(document).on('click', '.rsyi-bm-tab', function () {
        var tab = $(this).data('tab');
        $('.rsyi-bm-tab').removeClass('active');
        $(this).addClass('active');
        $('[id^="rsyi-bm-tab-"]').hide();
        $('#rsyi-bm-tab-' + tab).show();
    });

    /* ══ Add / Remove course row ═════════════════════════════════════════ */
    $(document).on('click', '.bm-add-course-btn', function () {
        var $container = $(this).closest('td').find('.bm-courses-container');
        addCourseRow($container, 0, '');
    });

    $(document).on('click', '.bm-remove-btn', function () {
        var $container = $(this).closest('.bm-courses-container');
        $(this).closest('.bm-course-row').remove();
        _syncRemoveBtns($container);
    });

    /* ══ Load report ═════════════════════════════════════════════════════ */
    function loadReport(date) {
        /* Reset every student row to one blank row */
        $('.bm-student-row').each(function () {
            var $c = $(this).find('.bm-courses-container');
            $c.find('.bm-course-row:not(:first)').remove();
            $c.find('.bm-course-select').val('');
            $c.find('.bm-notes-input').val('');
            _syncRemoveBtns($c);
        });

        if (!date) { return; }

        $.get(ajaxUrl, {
            action      : 'rsyi_get_boss_report',
            _nonce      : nonce,
            report_date : date,
            cohort_id   : cohortId
        }, function (res) {
            if (!res || !res.success || !res.data || !res.data.rows.length) { return; }

            /* Group rows by student_id */
            var byStudent = {};
            $.each(res.data.rows, function (i, r) {
                if (!byStudent[r.student_id]) { byStudent[r.student_id] = []; }
                byStudent[r.student_id].push(r);
            });

            $.each(byStudent, function (sid, entries) {
                var $row = $('.bm-student-row[data-id="' + sid + '"]');
                var $c   = $row.find('.bm-courses-container');

                /* Fill the first row */
                $c.find('.bm-course-row:first .bm-course-select').val(entries[0].course_id || '');
                $c.find('.bm-course-row:first .bm-notes-input').val(entries[0].notes || '');

                /* Append additional rows */
                for (var i = 1; i < entries.length; i++) {
                    addCourseRow($c, parseInt(entries[i].course_id, 10) || 0, entries[i].notes || '');
                }
                _syncRemoveBtns($c);
            });
        });
    }

    /* Auto-load today on page open (DOM is ready since we're in wp_footer) */
    loadReport($('#bm-report-date').val());

    $('#bm-load-btn').on('click', function () {
        loadReport($('#bm-report-date').val());
    });

    /* ══ Save report ═════════════════════════════════════════════════════ */
    $('#bm-save-btn').on('click', function () {
        var $btn = $(this).prop('disabled', true).text('جاري الحفظ… / Saving…');
        var rows = [];

        $('.bm-student-row').each(function () {
            var sid = $(this).data('id');
            $(this).find('.bm-course-row').each(function () {
                rows.push({
                    student_id : sid,
                    course_id  : $(this).find('.bm-course-select').val(),
                    notes      : $(this).find('.bm-notes-input').val()
                });
            });
        });

        $.post(ajaxUrl, {
            action      : 'rsyi_save_boss_report',
            _nonce      : nonce,
            report_date : $('#bm-report-date').val(),
            rows        : rows
        }, function (res) {
            $btn.prop('disabled', false).html('💾 حفظ التقرير / Save Report');
            if (res && res.success) {
                showNotice('✅ ' + res.data.message, 'success');
                $('#bm-save-status').text('✅ تم الحفظ').show();
                setTimeout(function () { $('#bm-save-status').fadeOut(); }, 3000);
            } else {
                var msg = (res && res.data && res.data.message) ? res.data.message : 'حدث خطأ / Error';
                showNotice('❌ ' + msg, 'error');
            }
        }).fail(function () {
            $btn.prop('disabled', false).html('💾 حفظ التقرير / Save Report');
            showNotice('❌ فشل الاتصال / Connection failed', 'error');
        });
    });

    /* ══ Clear all ═══════════════════════════════════════════════════════ */
    $('#bm-clear-btn').on('click', function () {
        if (!window.confirm('هل تريد مسح جميع الاختيارات؟ / Clear all selections?')) { return; }
        $('.bm-student-row').each(function () {
            var $c = $(this).find('.bm-courses-container');
            $c.find('.bm-course-row:not(:first)').remove();
            $c.find('.bm-course-select').val('');
            $c.find('.bm-notes-input').val('');
            _syncRemoveBtns($c);
        });
    });

    /* ══ History tab ═════════════════════════════════════════════════════ */
    $('#hist-load-btn').on('click', function () {
        var date = $('#hist-date').val();
        if (!date) { return; }
        var $c = $('#hist-content').html('<p style="color:#888;text-align:center;padding:20px;">جاري التحميل… / Loading…</p>');

        $.get(ajaxUrl, {
            action      : 'rsyi_get_boss_report',
            _nonce      : nonce,
            report_date : date,
            cohort_id   : cohortId
        }, function (res) {
            if (!res || !res.success || !res.data || !res.data.rows.length) {
                $c.html('<p style="color:#999;text-align:center;padding:20px;">لا توجد بيانات لهذا اليوم / No data for this date</p>');
                return;
            }

            var students = {}, order = [];
            $.each(res.data.rows, function (i, r) {
                if (!students[r.student_id]) {
                    students[r.student_id] = { name: r.arabic_full_name, courses: [] };
                    order.push(r.student_id);
                }
                students[r.student_id].courses.push({
                    name_ar : r.course_name_ar,
                    name_en : r.course_name_en,
                    notes   : r.notes
                });
            });

            var html = '<table style="width:100%;border-collapse:collapse;">'
                + '<thead><tr style="background:#f8f9fa;border-bottom:2px solid #dee2e6;">'
                + '<th style="padding:8px 12px;text-align:right;width:180px;">الطالب / Student</th>'
                + '<th style="padding:8px 12px;text-align:right;">الكورسات / Courses</th>'
                + '</tr></thead><tbody>';

            $.each(order, function (i, sid) {
                var st = students[sid];
                var ch = '';
                $.each(st.courses, function (j, cr) {
                    var nm = cr.name_ar
                        ? (cr.name_ar + (cr.name_en ? ' / ' + cr.name_en : ''))
                        : '—';
                    ch += '<div style="margin-bottom:4px;">'
                        + '<span style="background:#e3f2fd;color:#0073aa;padding:2px 10px;border-radius:20px;font-size:12px;">'
                        + $('<span>').text(nm).html()
                        + '</span>'
                        + (cr.notes
                            ? ' <span style="font-size:12px;color:#666;">– ' + $('<span>').text(cr.notes).html() + '</span>'
                            : '')
                        + '</div>';
                });
                html += '<tr style="border-bottom:1px solid #f0f0f0;">'
                    + '<td style="padding:10px 12px;font-weight:600;">' + $('<span>').text(st.name).html() + '</td>'
                    + '<td style="padding:10px 12px;">' + ch + '</td>'
                    + '</tr>';
            });

            html += '</tbody></table>';
            $c.html(html);
        });
    });

}(jQuery));
