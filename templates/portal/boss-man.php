<?php
/**
 * Portal – Boss Man Daily Study Report
 * Variables: $profile, $cohort, $students, $courses
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

$today = current_time( 'Y-m-d' );
$dashboard_url = get_option( 'rsyi_page_dashboard' ) ? get_permalink( get_option( 'rsyi_page_dashboard' ) ) : '';
?>
<div class="rsyi-portal rsyi-bm-portal" dir="rtl" style="font-family:sans-serif; max-width:960px; margin:0 auto;">

    <!-- Header -->
    <div style="background:linear-gradient(135deg,#e67e22,#d35400); color:#fff; border-radius:12px; padding:24px 28px; margin-bottom:24px; display:flex; align-items:center; gap:18px;">
        <div style="font-size:48px;">👮</div>
        <div>
            <h2 style="margin:0 0 4px; font-size:20px;">لوحة تحكم الحكمدار</h2>
            <p style="margin:0; opacity:.9; font-size:14px;">
                <?php echo esc_html( $profile->arabic_full_name ); ?> —
                دفعة: <strong><?php echo esc_html( isset($cohort->name) ? $cohort->name : '—' ); ?></strong>
            </p>
        </div>
        <?php if ( $dashboard_url ) : ?>
        <a href="<?php echo esc_url( $dashboard_url ); ?>"
           style="margin-right:auto; background:rgba(255,255,255,.2); color:#fff; padding:8px 16px; border-radius:6px; text-decoration:none; font-size:13px;">
            ← الرئيسية
        </a>
        <?php endif; ?>
    </div>

    <!-- Tabs -->
    <div style="display:flex; gap:0; margin-bottom:24px; border-bottom:2px solid #dee2e6;">
        <button class="rsyi-bm-tab active" data-tab="daily"
                style="padding:10px 24px; border:none; background:none; cursor:pointer; font-size:14px; font-weight:700; color:#e67e22; border-bottom:3px solid #e67e22; margin-bottom:-2px;">
            📋 تقرير اليوم
        </button>
        <button class="rsyi-bm-tab" data-tab="history"
                style="padding:10px 24px; border:none; background:none; cursor:pointer; font-size:14px; color:#666;">
            📅 السجل التاريخي
        </button>
    </div>

    <!-- Daily Report Tab -->
    <div id="rsyi-bm-tab-daily">
        <div style="background:#fff; border:1px solid #dee2e6; border-radius:10px; padding:20px; margin-bottom:20px;">
            <div style="display:flex; align-items:center; gap:14px; margin-bottom:20px;">
                <label style="font-weight:700; font-size:14px;">📅 التاريخ:</label>
                <input type="date" id="bm-report-date" value="<?php echo esc_attr( $today ); ?>"
                       max="<?php echo esc_attr( $today ); ?>"
                       style="border:1px solid #ccd0d4; border-radius:6px; padding:7px 12px; font-size:14px;">
                <button id="bm-load-btn" class="button" style="background:#0073aa; color:#fff; border-color:#0073aa; padding:7px 16px;">
                    تحميل البيانات
                </button>
            </div>

            <?php if ( empty( $students ) ) : ?>
            <div style="text-align:center; padding:30px; color:#999;">
                <div style="font-size:40px; margin-bottom:10px;">👥</div>
                <p>لا يوجد طلاب نشطون في دفعتك حالياً.</p>
            </div>
            <?php else : ?>

            <div id="bm-notice" style="display:none; margin-bottom:12px;" class="notice"></div>

            <table style="width:100%; border-collapse:collapse;" id="bm-students-table">
                <thead>
                    <tr style="background:#f8f9fa; border-bottom:2px solid #dee2e6;">
                        <th style="padding:10px 14px; text-align:right; font-size:13px;">#</th>
                        <th style="padding:10px 14px; text-align:right; font-size:13px;">اسم الطالب</th>
                        <th style="padding:10px 14px; text-align:right; font-size:13px; min-width:200px;">الكورس</th>
                        <th style="padding:10px 14px; text-align:right; font-size:13px;">ملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i = 1; foreach ( $students as $st ) : ?>
                <tr style="border-bottom:1px solid #f0f0f0;" class="bm-student-row"
                    data-id="<?php echo (int) $st->id; ?>">
                    <td style="padding:10px 14px; color:#888; font-size:13px;"><?php echo $i++; ?></td>
                    <td style="padding:10px 14px;">
                        <strong style="font-size:14px;"><?php echo esc_html( $st->arabic_full_name ); ?></strong>
                    </td>
                    <td style="padding:10px 14px;">
                        <select class="bm-course-select" data-sid="<?php echo (int) $st->id; ?>"
                                style="width:100%; border:1px solid #ccd0d4; border-radius:6px; padding:7px 10px; font-size:13px;">
                            <option value="">— اختر الكورس —</option>
                            <?php foreach ( $courses as $cr ) : ?>
                            <option value="<?php echo (int) $cr->id; ?>">
                                <?php echo esc_html( $cr->name_ar ); ?>
                                <?php if ( $cr->name_en ) : ?> (<?php echo esc_html( $cr->name_en ); ?>)<?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td style="padding:10px 14px;">
                        <input type="text" class="bm-notes-input" data-sid="<?php echo (int) $st->id; ?>"
                               placeholder="ملاحظة اختيارية…"
                               style="width:100%; border:1px solid #ccd0d4; border-radius:6px; padding:7px 10px; font-size:13px; box-sizing:border-box;">
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <div style="margin-top:20px; display:flex; gap:12px; align-items:center;">
                <button id="bm-save-btn" class="button button-primary"
                        style="background:#e67e22; border-color:#d35400; color:#fff; padding:10px 28px; font-size:15px; font-weight:700;">
                    💾 حفظ تقرير اليوم
                </button>
                <button id="bm-clear-btn" class="button" style="padding:10px 18px;">
                    🗑 مسح الكل
                </button>
                <span id="bm-save-status" style="font-size:13px; color:#27ae60; display:none;"></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- History Tab -->
    <div id="rsyi-bm-tab-history" style="display:none;">
        <div style="background:#fff; border:1px solid #dee2e6; border-radius:10px; padding:20px;">
            <div style="display:flex; gap:14px; align-items:flex-end; margin-bottom:20px;">
                <div>
                    <label style="display:block; font-weight:700; font-size:13px; margin-bottom:4px;">التاريخ</label>
                    <input type="date" id="hist-date" value="<?php echo esc_attr( $today ); ?>"
                           style="border:1px solid #ccd0d4; border-radius:6px; padding:7px 12px;">
                </div>
                <button id="hist-load-btn" class="button" style="background:#0073aa; color:#fff; border-color:#0073aa; padding:7px 16px;">
                    🔍 عرض
                </button>
            </div>
            <div id="hist-content" style="text-align:center; color:#999; padding:30px;">
                اختر تاريخاً واضغط عرض
            </div>
        </div>
    </div>
</div>

<script>
jQuery(function($){
    var ajaxUrl = rsyiPortal.ajaxUrl, nonce = rsyiPortal.nonce;
    var cohortId = <?php echo (int) ($cohort->id ?? 0); ?>;

    function showNotice(msg, type){
        $('#bm-notice').removeClass('notice-success notice-error')
            .addClass('notice-' + type).html('<p>' + msg + '</p>').show();
        setTimeout(function(){ $('#bm-notice').fadeOut(); }, 4000);
    }

    // ── Tab switching ─────────────────────────────────────────────────────────
    $('.rsyi-bm-tab').on('click', function(){
        var tab = $(this).data('tab');
        $('.rsyi-bm-tab').css({ color:'#666', borderBottom:'none', fontWeight:'400' });
        $(this).css({ color:'#e67e22', borderBottom:'3px solid #e67e22', fontWeight:'700' });
        $('[id^=rsyi-bm-tab-]').hide();
        $('#rsyi-bm-tab-' + tab).show();
    });

    // ── Load existing data for a date ─────────────────────────────────────────
    function loadReport(date){
        $.get(ajaxUrl, {
            action      : 'rsyi_get_boss_report',
            _nonce      : nonce,
            report_date : date,
            cohort_id   : cohortId
        }, function(res){
            if(!res.success || !res.data.rows.length) return;
            $.each(res.data.rows, function(i, r){
                var sid = r.student_id;
                $('[data-sid="' + sid + '"].bm-course-select').val(r.course_id || '');
                $('[data-sid="' + sid + '"].bm-notes-input').val(r.notes || '');
            });
        });
    }
    loadReport($('#bm-report-date').val());

    $('#bm-load-btn').on('click', function(){
        loadReport($('#bm-report-date').val());
    });

    // ── Save report ───────────────────────────────────────────────────────────
    $('#bm-save-btn').on('click', function(){
        var $btn = $(this).prop('disabled', true).text('جاري الحفظ…');
        var rows = [];
        $('.bm-student-row').each(function(){
            var sid = $(this).data('id');
            rows.push({
                student_id : sid,
                course_id  : $(this).find('.bm-course-select').val(),
                notes      : $(this).find('.bm-notes-input').val()
            });
        });
        $.post(ajaxUrl, {
            action      : 'rsyi_save_boss_report',
            _nonce      : nonce,
            report_date : $('#bm-report-date').val(),
            rows        : rows
        }, function(res){
            $btn.prop('disabled', false).text('💾 حفظ تقرير اليوم');
            if(res.success){
                showNotice('✅ ' + res.data.message, 'success');
                $('#bm-save-status').text('✅ تم الحفظ').show().delay(3000).fadeOut();
            } else {
                showNotice('❌ ' + (res.data.message || 'حدث خطأ'), 'error');
            }
        }).fail(function(){
            $btn.prop('disabled', false).text('💾 حفظ تقرير اليوم');
            showNotice('❌ فشل الاتصال. حاول مجدداً.', 'error');
        });
    });

    // ── Clear all selections ──────────────────────────────────────────────────
    $('#bm-clear-btn').on('click', function(){
        if(!confirm('هل تريد مسح جميع اختيارات الكورسات؟')) return;
        $('.bm-course-select').val('');
        $('.bm-notes-input').val('');
    });

    // ── History tab ───────────────────────────────────────────────────────────
    $('#hist-load-btn').on('click', function(){
        var date = $('#hist-date').val();
        if(!date){ return; }
        var $c = $('#hist-content').html('<p style="color:#888;">جاري التحميل…</p>');
        $.get(ajaxUrl, {
            action      : 'rsyi_get_boss_report',
            _nonce      : nonce,
            report_date : date,
            cohort_id   : cohortId
        }, function(res){
            if(!res.success || !res.data.rows.length){
                $c.html('<p style="color:#999; text-align:center; padding:20px;">لا توجد بيانات لهذا اليوم</p>');
                return;
            }
            var html = '<table style="width:100%; border-collapse:collapse;">' +
                '<thead><tr style="background:#f8f9fa; border-bottom:2px solid #dee2e6;">' +
                '<th style="padding:8px 12px; text-align:right;">الطالب</th>' +
                '<th style="padding:8px 12px; text-align:right;">الكورس</th>' +
                '<th style="padding:8px 12px; text-align:right;">ملاحظات</th>' +
                '</tr></thead><tbody>';
            $.each(res.data.rows, function(i, r){
                html += '<tr style="border-bottom:1px solid #f0f0f0;">' +
                    '<td style="padding:9px 12px;">' + (r.arabic_full_name || '') + '</td>' +
                    '<td style="padding:9px 12px;">' +
                    (r.course_name_ar ? '<span style="background:#e3f2fd;color:#0073aa;padding:2px 10px;border-radius:20px;font-size:12px;">' + r.course_name_ar + '</span>' : '<span style="color:#999;">—</span>') +
                    '</td>' +
                    '<td style="padding:9px 12px; font-size:12px; color:#666;">' + (r.notes || '—') + '</td>' +
                    '</tr>';
            });
            html += '</tbody></table>';
            $c.html(html);
        });
    });
});
</script>
