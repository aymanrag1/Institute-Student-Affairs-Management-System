<?php
/**
 * Admin – Bulk Import Students from Excel/CSV
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

if ( ! current_user_can( 'rsyi_create_student' ) ) {
    wp_die( __( 'صلاحية غير كافية.', 'rsyi-sa' ) );
}

$cohorts = \RSYI_SA\Modules\Cohorts::get_all_cohorts();
?>
<h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=rsyi-students' ) ); ?>" style="text-decoration:none;">
        &rarr; <?php esc_html_e( 'الطلاب', 'rsyi-sa' ); ?>
    </a>
    <?php esc_html_e( 'استيراد الطلاب من Excel', 'rsyi-sa' ); ?>
</h1>
<hr class="wp-header-end">

<div id="rsyi-import-notices"></div>

<div style="display:flex;gap:20px;flex-wrap:wrap;align-items:flex-start;">

    <!-- ── Import Form ────────────────────────────────────────────── -->
    <div class="rsyi-card" style="flex:1;min-width:340px;max-width:580px;">
        <h2 style="margin-top:0;"><?php esc_html_e( 'رفع ملف الطلاب', 'rsyi-sa' ); ?></h2>

        <table class="form-table" role="presentation">
            <tr>
                <th><label for="rsyi-import-cohort"><?php esc_html_e( 'الفوج', 'rsyi-sa' ); ?> <span style="color:red">*</span></label></th>
                <td>
                    <select id="rsyi-import-cohort">
                        <option value="0"><?php esc_html_e( '— اختر الفوج —', 'rsyi-sa' ); ?></option>
                        <?php foreach ( $cohorts as $c ) : ?>
                            <option value="<?php echo esc_attr( $c->id ); ?>">
                                <?php echo esc_html( $c->name . ' (' . $c->code . ')' ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ( empty( $cohorts ) ) : ?>
                        <p class="description" style="color:#c0392b;">
                            <?php esc_html_e( 'لا يوجد أفواج. يرجى إنشاء فوج أولاً.', 'rsyi-sa' ); ?>
                        </p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><label for="rsyi-import-file"><?php esc_html_e( 'ملف Excel / CSV', 'rsyi-sa' ); ?> <span style="color:red">*</span></label></th>
                <td>
                    <input type="file" id="rsyi-import-file" accept=".xlsx,.xls,.csv">
                    <p class="description">
                        <?php esc_html_e( 'الصيغ المدعومة: .xlsx, .csv', 'rsyi-sa' ); ?>
                    </p>
                </td>
            </tr>
        </table>

        <p style="border-top:1px solid #eee;padding-top:14px;margin-top:4px;">
            <button type="button" id="rsyi-start-import" class="button button-primary button-large">
                <?php esc_html_e( 'بدء الاستيراد', 'rsyi-sa' ); ?>
            </button>
        </p>

        <!-- Progress bar (hidden initially) -->
        <div id="rsyi-import-progress" style="display:none;margin-top:12px;">
            <div style="background:#e0e0e0;border-radius:4px;height:14px;overflow:hidden;">
                <div id="rsyi-progress-bar"
                     style="height:100%;width:0%;background:#1a5f7a;transition:width .3s;"></div>
            </div>
            <p id="rsyi-progress-text" style="font-size:13px;color:#555;margin-top:6px;"></p>
        </div>
    </div>

    <!-- ── Instructions + Template Download ──────────────────────── -->
    <div class="rsyi-card" style="flex:1;min-width:280px;max-width:400px;">
        <h2 style="margin-top:0;"><?php esc_html_e( 'تعليمات', 'rsyi-sa' ); ?></h2>

        <p><?php esc_html_e( 'يجب أن يحتوي الملف على الأعمدة التالية (الصف الأول عناوين):', 'rsyi-sa' ); ?></p>
        <table class="widefat rsyi-table" style="font-size:12px;">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'العمود', 'rsyi-sa' ); ?></th>
                    <th><?php esc_html_e( 'إلزامي', 'rsyi-sa' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr><td><?php esc_html_e( 'الاسم العربي الكامل', 'rsyi-sa' ); ?></td><td>✅</td></tr>
                <tr><td><?php esc_html_e( 'الاسم الإنجليزي الكامل', 'rsyi-sa' ); ?></td><td>✅</td></tr>
                <tr><td><?php esc_html_e( 'البريد الإلكتروني', 'rsyi-sa' ); ?></td><td>✅</td></tr>
                <tr><td><?php esc_html_e( 'كلمة المرور', 'rsyi-sa' ); ?></td><td><?php esc_html_e( 'اختياري', 'rsyi-sa' ); ?></td></tr>
                <tr><td><?php esc_html_e( 'رقم الهوية القومية', 'rsyi-sa' ); ?></td><td><?php esc_html_e( 'اختياري', 'rsyi-sa' ); ?></td></tr>
                <tr><td><?php esc_html_e( 'تاريخ الميلاد (YYYY-MM-DD)', 'rsyi-sa' ); ?></td><td><?php esc_html_e( 'اختياري', 'rsyi-sa' ); ?></td></tr>
                <tr><td><?php esc_html_e( 'رقم الهاتف', 'rsyi-sa' ); ?></td><td><?php esc_html_e( 'اختياري', 'rsyi-sa' ); ?></td></tr>
            </tbody>
        </table>

        <p style="margin-top:16px;">
            <a href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=rsyi_download_import_template&_nonce=' . wp_create_nonce( 'rsyi_sa_admin' ) ) ); ?>"
               class="button">
                ⬇️ <?php esc_html_e( 'تحميل نموذج Excel (CSV)', 'rsyi-sa' ); ?>
            </a>
        </p>
        <p class="description">
            <?php esc_html_e( 'افتح الملف في Excel لتعبئة بيانات الطلاب ثم احفظه.', 'rsyi-sa' ); ?>
        </p>
    </div>

</div>

<!-- ── Results Table (shown after import) ─────────────────────────────────── -->
<div id="rsyi-import-results" style="display:none;margin-top:24px;">
    <h2><?php esc_html_e( 'نتيجة الاستيراد', 'rsyi-sa' ); ?></h2>
    <p id="rsyi-import-summary"></p>
    <table class="widefat rsyi-table striped" id="rsyi-results-table">
        <thead>
            <tr>
                <th>#</th>
                <th><?php esc_html_e( 'الاسم', 'rsyi-sa' ); ?></th>
                <th><?php esc_html_e( 'البريد الإلكتروني', 'rsyi-sa' ); ?></th>
                <th><?php esc_html_e( 'الحالة', 'rsyi-sa' ); ?></th>
                <th><?php esc_html_e( 'ملاحظة', 'rsyi-sa' ); ?></th>
            </tr>
        </thead>
        <tbody id="rsyi-results-body"></tbody>
    </table>
</div>

<script>
(function ($) {
    var BATCH_SIZE = 10;  // rows per AJAX call

    $('#rsyi-start-import').on('click', function () {
        var cohort = $('#rsyi-import-cohort').val();
        var file   = $('#rsyi-import-file')[0].files[0];
        var notices = $('#rsyi-import-notices');
        notices.empty();

        if (!cohort || cohort === '0') {
            notices.html('<div class="notice notice-error"><p><?php echo esc_js( __( 'يرجى اختيار الفوج أولاً.', 'rsyi-sa' ) ); ?></p></div>');
            return;
        }
        if (!file) {
            notices.html('<div class="notice notice-error"><p><?php echo esc_js( __( 'يرجى اختيار ملف.', 'rsyi-sa' ) ); ?></p></div>');
            return;
        }

        var btn = $(this).prop('disabled', true);

        // Upload file to server for parsing
        var fd = new FormData();
        fd.append('action',    'rsyi_parse_import_file');
        fd.append('_nonce',    rsyiSA.nonce);
        fd.append('cohort_id', cohort);
        fd.append('import_file', file);

        $('#rsyi-import-progress').show();
        $('#rsyi-progress-bar').css('width', '10%');
        $('#rsyi-progress-text').text('<?php echo esc_js( __( 'جاري تحليل الملف…', 'rsyi-sa' ) ); ?>');

        $.ajax({
            url        : rsyiSA.ajaxUrl,
            type       : 'POST',
            data       : fd,
            processData: false,
            contentType: false,
            success    : function (res) {
                if (!res.success) {
                    btn.prop('disabled', false);
                    $('#rsyi-import-progress').hide();
                    notices.html('<div class="notice notice-error"><p>' + (res.data.message || '<?php echo esc_js( __( 'فشل تحليل الملف.', 'rsyi-sa' ) ); ?>') + '</p></div>');
                    return;
                }
                var rows = res.data.rows;
                if (!rows || rows.length === 0) {
                    btn.prop('disabled', false);
                    $('#rsyi-import-progress').hide();
                    notices.html('<div class="notice notice-warning"><p><?php echo esc_js( __( 'الملف لا يحتوي على بيانات.', 'rsyi-sa' ) ); ?></p></div>');
                    return;
                }

                processInBatches(rows, cohort, btn);
            },
            error: function () {
                btn.prop('disabled', false);
                $('#rsyi-import-progress').hide();
                notices.html('<div class="notice notice-error"><p><?php echo esc_js( __( 'فشل الاتصال بالخادم.', 'rsyi-sa' ) ); ?></p></div>');
            }
        });
    });

    function processInBatches(allRows, cohortId, btn) {
        var total    = allRows.length;
        var done     = 0;
        var success  = 0;
        var errors   = 0;
        var results  = [];

        $('#rsyi-import-results').show();
        $('#rsyi-results-body').empty();
        $('#rsyi-import-summary').text('');

        function sendBatch(offset) {
            if (offset >= total) {
                // All done
                btn.prop('disabled', false);
                var pct = Math.round((done / total) * 100);
                $('#rsyi-progress-bar').css('width', '100%');
                $('#rsyi-progress-text').text('<?php echo esc_js( __( 'اكتمل الاستيراد', 'rsyi-sa' ) ); ?>');
                $('#rsyi-import-summary').html(
                    '<strong>' + done + '</strong> <?php echo esc_js( __( 'صف إجمالي', 'rsyi-sa' ) ); ?> – ' +
                    '<span style="color:#1a7a4a"><strong>' + success + '</strong> <?php echo esc_js( __( 'نجح', 'rsyi-sa' ) ); ?></span> – ' +
                    '<span style="color:#c0392b"><strong>' + errors + '</strong> <?php echo esc_js( __( 'فشل', 'rsyi-sa' ) ); ?></span>'
                );
                return;
            }

            var batch = allRows.slice(offset, offset + BATCH_SIZE);
            var pct   = Math.round(((offset + batch.length) / total) * 100);

            $.post(rsyiSA.ajaxUrl, {
                action    : 'rsyi_import_students_batch',
                _nonce    : rsyiSA.nonce,
                cohort_id : cohortId,
                rows      : JSON.stringify(batch)
            }, function (res) {
                if (res.success && res.data.results) {
                    res.data.results.forEach(function (r) {
                        done++;
                        var rowIndex = offset + r.row + 1;
                        if (r.success) { success++; } else { errors++; }
                        var statusHtml = r.success
                            ? '<span class="rsyi-badge rsyi-status-active">✅ <?php echo esc_js( __( 'نجح', 'rsyi-sa' ) ); ?></span>'
                            : '<span class="rsyi-badge rsyi-status-rejected">❌ <?php echo esc_js( __( 'فشل', 'rsyi-sa' ) ); ?></span>';
                        $('#rsyi-results-body').append(
                            '<tr>' +
                            '<td>' + rowIndex + '</td>' +
                            '<td>' + $('<div>').text(r.name || '').html() + '</td>' +
                            '<td>' + $('<div>').text(r.email || '').html() + '</td>' +
                            '<td>' + statusHtml + '</td>' +
                            '<td style="font-size:12px;color:#555;">' + $('<div>').text(r.message || '').html() + '</td>' +
                            '</tr>'
                        );
                    });
                }

                $('#rsyi-progress-bar').css('width', pct + '%');
                $('#rsyi-progress-text').text(done + ' / ' + total + ' <?php echo esc_js( __( 'صف', 'rsyi-sa' ) ); ?>');

                sendBatch(offset + BATCH_SIZE);
            }).fail(function () {
                // Mark entire batch as failed and continue
                batch.forEach(function (r, i) {
                    done++; errors++;
                    $('#rsyi-results-body').append(
                        '<tr><td>' + (offset + i + 1) + '</td><td>—</td><td>—</td>' +
                        '<td><span class="rsyi-badge rsyi-status-rejected">❌</span></td>' +
                        '<td><?php echo esc_js( __( 'خطأ في الاتصال', 'rsyi-sa' ) ); ?></td></tr>'
                    );
                });
                sendBatch(offset + BATCH_SIZE);
            });
        }

        sendBatch(0);
    }
}(jQuery));
</script>
