<?php
/**
 * Admin Settings Template
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

if ( ! current_user_can( 'rsyi_manage_settings' ) ) {
    wp_die( __( 'صلاحية غير كافية.', 'rsyi-sa' ) );
}

$institute_name = get_option( 'rsyi_institute_name', 'معهد البحر الأحمر للتخطيط البحري – الجونة' );
$dean_name      = get_option( 'rsyi_dean_name', '' );
$logo_url       = get_option( 'rsyi_logo_url', '' );
$logo_id        = (int) get_option( 'rsyi_logo_attachment_id', 0 );
$github_token   = get_option( 'rsyi_github_token', '' );

global $wpdb;
$violation_types_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}rsyi_violation_types" );
?>
<h1><?php esc_html_e( 'الإعدادات', 'rsyi-sa' ); ?></h1>
<hr class="wp-header-end">
<div id="rsyi-settings-notices"></div>

<!-- ══ Section 1: Institute Info ══════════════════════════════════════════ -->
<div class="rsyi-card" style="max-width:700px;margin-bottom:24px;">
    <h2 style="margin-top:0;"><?php esc_html_e( 'بيانات المعهد', 'rsyi-sa' ); ?></h2>

    <table class="form-table" role="presentation">
        <tr>
            <th><label for="rsyi_institute_name"><?php esc_html_e( 'اسم المعهد', 'rsyi-sa' ); ?></label></th>
            <td>
                <input type="text" id="rsyi_institute_name" class="regular-text"
                       value="<?php echo esc_attr( $institute_name ); ?>"
                       placeholder="<?php esc_attr_e( 'أدخل اسم المعهد', 'rsyi-sa' ); ?>">
                <p class="description"><?php esc_html_e( 'يظهر في لوحة التحكم وتقارير PDF.', 'rsyi-sa' ); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="rsyi_dean_name"><?php esc_html_e( 'اسم العميد', 'rsyi-sa' ); ?></label></th>
            <td>
                <input type="text" id="rsyi_dean_name" class="regular-text"
                       value="<?php echo esc_attr( $dean_name ); ?>"
                       placeholder="<?php esc_attr_e( 'الاسم الكامل للعميد', 'rsyi-sa' ); ?>">
                <p class="description"><?php esc_html_e( 'يظهر في خطابات الفصل وتوقيع التقارير.', 'rsyi-sa' ); ?></p>
            </td>
        </tr>
        <tr>
            <th><?php esc_html_e( 'شعار المعهد (Logo)', 'rsyi-sa' ); ?></th>
            <td>
                <div id="rsyi-logo-preview" style="margin-bottom:10px;">
                    <?php if ( $logo_url ) : ?>
                        <img src="<?php echo esc_url( $logo_url ); ?>" alt="Logo"
                             style="max-height:80px;max-width:200px;border:1px solid #ddd;padding:4px;border-radius:4px;">
                    <?php else : ?>
                        <span style="color:#888;"><?php esc_html_e( 'لم يُرفع شعار بعد', 'rsyi-sa' ); ?></span>
                    <?php endif; ?>
                </div>
                <input type="hidden" id="rsyi_logo_attachment_id" value="<?php echo esc_attr( $logo_id ); ?>">
                <input type="hidden" id="rsyi_logo_url" value="<?php echo esc_attr( $logo_url ); ?>">
                <button type="button" id="rsyi-upload-logo" class="button">
                    📷 <?php esc_html_e( 'اختيار الشعار من المكتبة', 'rsyi-sa' ); ?>
                </button>
                <?php if ( $logo_url ) : ?>
                <button type="button" id="rsyi-remove-logo" class="button" style="margin-right:6px;color:#c0392b;">
                    🗑 <?php esc_html_e( 'إزالة الشعار', 'rsyi-sa' ); ?>
                </button>
                <?php endif; ?>
                <p class="description"><?php esc_html_e( 'يستخدم في تقارير PDF وخطابات الفصل.', 'rsyi-sa' ); ?></p>
            </td>
        </tr>
    </table>

    <p class="submit" style="border-top:1px solid #eee;padding-top:14px;margin-top:4px;">
        <button type="button" id="rsyi-save-settings" class="button button-primary button-large">
            <?php esc_html_e( 'حفظ الإعدادات', 'rsyi-sa' ); ?>
        </button>
        <span id="rsyi-settings-status" style="margin-right:12px;display:none;font-weight:600;"></span>
    </p>
</div>

<!-- ══ Section 2: Violation Types ════════════════════════════════════════ -->
<div class="rsyi-card" style="max-width:700px;margin-bottom:24px;">
    <h2 style="margin-top:0;"><?php esc_html_e( 'أنواع المخالفات', 'rsyi-sa' ); ?></h2>
    <p>
        <?php
        printf(
            esc_html__( 'عدد أنواع المخالفات المُسجَّلة حالياً: %s', 'rsyi-sa' ),
            '<strong>' . esc_html( $violation_types_count ) . '</strong>'
        );
        ?>
    </p>
    <?php if ( $violation_types_count === 0 ) : ?>
    <div class="notice notice-warning inline"><p>
        <?php esc_html_e( 'جدول أنواع المخالفات فارغ. اضغط الزر أدناه لإضافة الأنواع الافتراضية.', 'rsyi-sa' ); ?>
    </p></div>
    <?php endif; ?>
    <p>
        <button type="button" id="rsyi-seed-violations" class="button <?php echo $violation_types_count === 0 ? 'button-primary' : ''; ?>">
            🔄 <?php esc_html_e( 'إعادة زرع أنواع المخالفات الافتراضية', 'rsyi-sa' ); ?>
        </button>
        <span id="rsyi-seed-status" style="margin-right:10px;display:none;font-weight:600;"></span>
    </p>
    <p class="description">
        <?php esc_html_e( 'تنبيه: هذا الإجراء يُضيف الأنواع المفقودة فقط ولا يحذف الأنواع الموجودة.', 'rsyi-sa' ); ?>
    </p>
</div>

<!-- ══ Section 3: GitHub Auto-Update ══════════════════════════════════════ -->
<div class="rsyi-card" style="max-width:700px;margin-bottom:24px;">
    <h2 style="margin-top:0;">
        🔄 <?php esc_html_e( 'التحديث التلقائي عبر GitHub', 'rsyi-sa' ); ?>
    </h2>
    <p class="description" style="margin-bottom:16px;">
        <?php esc_html_e( 'عند نشر إصدار جديد (Release) على GitHub، سيظهر تنبيه التحديث تلقائياً في صفحة الإضافات. اضغط "تحديث الآن" وسيُثبَّت البلاجن الجديد بشكل كامل.', 'rsyi-sa' ); ?>
    </p>
    <table class="form-table" role="presentation">
        <tr>
            <th><?php esc_html_e( 'المستودع', 'rsyi-sa' ); ?></th>
            <td>
                <code style="font-size:13px;">aymanrag1/Institute-Student-Affairs-Management-System</code>
                <p class="description"><?php esc_html_e( 'يجب أن يكون المستودع عاماً (Public)، أو أدخل Personal Access Token أدناه للمستودعات الخاصة.', 'rsyi-sa' ); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="rsyi_github_token"><?php esc_html_e( 'GitHub Token (اختياري)', 'rsyi-sa' ); ?></label></th>
            <td>
                <input type="password" id="rsyi_github_token" class="regular-text"
                       value="<?php echo esc_attr( $github_token ? str_repeat( '•', 20 ) : '' ); ?>"
                       placeholder="ghp_xxxxxxxxxxxxxxxxxxxx"
                       autocomplete="new-password">
                <p class="description">
                    <?php esc_html_e( 'مطلوب فقط للمستودعات الخاصة. أنشئ Token من: GitHub → Settings → Developer settings → Personal access tokens. الصلاحية المطلوبة: repo (read).', 'rsyi-sa' ); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th><?php esc_html_e( 'حالة الاتصال', 'rsyi-sa' ); ?></th>
            <td>
                <?php
                $cached = get_transient( 'rsyi_sa_update_cache' );
                if ( $cached === false ) :
                ?>
                <span style="color:#888;"><?php esc_html_e( 'لم يتم التحقق بعد. سيتحقق WordPress تلقائياً خلال 12 ساعة.', 'rsyi-sa' ); ?></span>
                <?php elseif ( ! $cached ) : ?>
                <span style="color:#c0392b;">&#10008; <?php esc_html_e( 'فشل الاتصال بـ GitHub API. تأكد من أن المستودع عام أو أدخل Token صحيح.', 'rsyi-sa' ); ?></span>
                <?php else : ?>
                <span style="color:#1a7a4a;">&#10004; <?php printf(
                    esc_html__( 'متصل. آخر إصدار على GitHub: %s', 'rsyi-sa' ),
                    '<strong>' . esc_html( $cached->tag_name ?? 'غير معروف' ) . '</strong>'
                ); ?></span>
                <?php endif; ?>
                <br>
                <button type="button" id="rsyi-check-update" class="button" style="margin-top:8px;">
                    <?php esc_html_e( 'التحقق من التحديثات الآن', 'rsyi-sa' ); ?>
                </button>
                <span id="rsyi-check-update-status" style="margin-right:8px;display:none;"></span>
            </td>
        </tr>
    </table>

    <p class="submit" style="border-top:1px solid #eee;padding-top:14px;margin-top:4px;">
        <button type="button" id="rsyi-save-github" class="button button-primary">
            <?php esc_html_e( 'حفظ إعدادات GitHub', 'rsyi-sa' ); ?>
        </button>
        <span id="rsyi-github-status" style="margin-right:12px;display:none;font-weight:600;"></span>
    </p>
</div>

<!-- ══ Section 4: System Integration ════════════════════════════════════ -->
<div class="rsyi-card" style="max-width:700px;">
    <h2 style="margin-top:0;"><?php esc_html_e( 'ربط الأنظمة', 'rsyi-sa' ); ?></h2>
    <p class="description">
        <?php esc_html_e( 'يتيح هذا القسم في المستقبل ربط سيستم شؤون الطلاب بالأنظمة الأخرى للمعهد (المخازن، الموارد البشرية...).', 'rsyi-sa' ); ?>
    </p>
    <table class="form-table" role="presentation">
        <tr>
            <th><?php esc_html_e( 'جدول الموظفين', 'rsyi-sa' ); ?></th>
            <td>
                <?php
                global $wpdb;
                // Auto-detect if an employees table exists from the warehouse system (wp_iw_employees)
                $emp_table = $wpdb->prefix . 'iw_employees';
                $exists    = $wpdb->get_var( "SHOW TABLES LIKE '{$emp_table}'" );
                if ( $exists ) :
                    $emp_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$emp_table}" );
                ?>
                <span class="rsyi-badge rsyi-status-active">✅ <?php esc_html_e( 'مُتصل', 'rsyi-sa' ); ?></span>
                <p class="description">
                    <?php printf( esc_html__( 'تم اكتشاف جدول الموظفين. عدد الموظفين: %d', 'rsyi-sa' ), $emp_count ); ?>
                </p>
                <?php else : ?>
                <span class="rsyi-badge rsyi-status-pending"><?php esc_html_e( 'غير متصل', 'rsyi-sa' ); ?></span>
                <p class="description">
                    <?php esc_html_e( 'لم يُكتشف جدول موظفين. سيُفعَّل هذا الربط تلقائياً عند تثبيت سيستم إدارة الموارد البشرية.', 'rsyi-sa' ); ?>
                </p>
                <?php endif; ?>
            </td>
        </tr>
    </table>
</div>

<script>
(function ($) {

    // ── WP Media uploader for logo ─────────────────────────────────────────
    var mediaFrame;
    $('#rsyi-upload-logo').on('click', function (e) {
        e.preventDefault();
        if (mediaFrame) { mediaFrame.open(); return; }
        mediaFrame = wp.media({
            title   : '<?php echo esc_js( __( 'اختر شعار المعهد', 'rsyi-sa' ) ); ?>',
            button  : { text: '<?php echo esc_js( __( 'استخدام هذه الصورة', 'rsyi-sa' ) ); ?>' },
            multiple: false,
            library : { type: 'image' }
        });
        mediaFrame.on('select', function () {
            var att = mediaFrame.state().get('selection').first().toJSON();
            $('#rsyi_logo_attachment_id').val(att.id);
            $('#rsyi_logo_url').val(att.url);
            $('#rsyi-logo-preview').html(
                '<img src="' + att.url + '" alt="Logo" style="max-height:80px;max-width:200px;border:1px solid #ddd;padding:4px;border-radius:4px;">'
            );
        });
        mediaFrame.open();
    });

    $('#rsyi-remove-logo').on('click', function () {
        $('#rsyi_logo_attachment_id').val('0');
        $('#rsyi_logo_url').val('');
        $('#rsyi-logo-preview').html('<span style="color:#888;"><?php echo esc_js( __( 'لم يُرفع شعار بعد', 'rsyi-sa' ) ); ?></span>');
    });

    // ── Save settings ──────────────────────────────────────────────────────
    $('#rsyi-save-settings').on('click', function () {
        var btn    = $(this).prop('disabled', true);
        var status = $('#rsyi-settings-status');
        status.hide();

        $.post(rsyiSA.ajaxUrl, {
            action              : 'rsyi_save_settings',
            _nonce              : rsyiSA.nonce,
            rsyi_institute_name : $('#rsyi_institute_name').val().trim(),
            rsyi_dean_name      : $('#rsyi_dean_name').val().trim(),
            rsyi_logo_attachment_id: $('#rsyi_logo_attachment_id').val(),
            rsyi_logo_url       : $('#rsyi_logo_url').val()
        }, function (res) {
            btn.prop('disabled', false);
            if (res.success) {
                status.text('✅ ' + res.data.message).css('color', '#1a7a4a').show();
                setTimeout(function () { status.fadeOut(); }, 3000);
            } else {
                status.text('❌ ' + (res.data.message || '<?php echo esc_js( __( 'حدث خطأ.', 'rsyi-sa' ) ); ?>')).css('color', '#c0392b').show();
            }
        }).fail(function () {
            btn.prop('disabled', false);
            status.text('❌ <?php echo esc_js( __( 'فشل الاتصال.', 'rsyi-sa' ) ); ?>').css('color', '#c0392b').show();
        });
    });

    // ── Save GitHub settings ───────────────────────────────────────────────
    $('#rsyi-save-github').on('click', function () {
        var btn    = $(this).prop('disabled', true);
        var status = $('#rsyi-github-status');
        status.hide();

        var tokenVal = $('#rsyi_github_token').val();
        // If the field still shows the masked placeholder, send empty to skip update
        var tokenToSend = /^•+$/.test(tokenVal) ? '__KEEP__' : tokenVal;

        $.post(rsyiSA.ajaxUrl, {
            action           : 'rsyi_save_settings',
            _nonce           : rsyiSA.nonce,
            rsyi_institute_name : $('#rsyi_institute_name').val().trim() || '<?php echo esc_js( $institute_name ); ?>',
            rsyi_dean_name   : $('#rsyi_dean_name').val().trim(),
            rsyi_logo_attachment_id: $('#rsyi_logo_attachment_id').val(),
            rsyi_logo_url    : $('#rsyi_logo_url').val(),
            rsyi_github_token: tokenToSend
        }, function (res) {
            btn.prop('disabled', false);
            if (res.success) {
                status.text('✅ ' + res.data.message).css('color', '#1a7a4a').show();
                setTimeout(function () { status.fadeOut(); }, 3000);
            } else {
                status.text('❌ ' + (res.data.message || '<?php echo esc_js( __( 'خطأ.', 'rsyi-sa' ) ); ?>')).css('color', '#c0392b').show();
            }
        }).fail(function () {
            btn.prop('disabled', false);
            status.text('❌ <?php echo esc_js( __( 'فشل الاتصال.', 'rsyi-sa' ) ); ?>').css('color', '#c0392b').show();
        });
    });

    // ── Force update check ─────────────────────────────────────────────────
    $('#rsyi-check-update').on('click', function () {
        var btn  = $(this).prop('disabled', true);
        var stat = $('#rsyi-check-update-status');
        stat.html('⏳ <?php echo esc_js( __( 'جاري التحقق من GitHub…', 'rsyi-sa' ) ); ?>').css('color', '#666').show();

        $.post(rsyiSA.ajaxUrl, {
            action: 'rsyi_force_update_check',
            _nonce: rsyiSA.nonce
        }, function (res) {
            btn.prop('disabled', false);
            if (res.success) {
                stat.html('✅ ' + res.data.message).css('color', '#1a7a4a');
                setTimeout(function () { location.reload(); }, 2000);
            } else {
                var hints = {
                    'auth'        : '<?php echo esc_js( __( 'تلميح: أنشئ Personal Access Token وأدخله في حقل Token أعلاه.', 'rsyi-sa' ) ); ?>',
                    'no_releases' : '<?php echo esc_js( __( 'تلميح: لم تُنشر أي Releases على GitHub بعد. استخدم: git tag v1.0.0 && git push origin v1.0.0', 'rsyi-sa' ) ); ?>',
                    'network'     : '<?php echo esc_js( __( 'تلميح: تأكد أن السيرفر يستطيع الوصول إلى الإنترنت (github.com).', 'rsyi-sa' ) ); ?>',
                    'not_found'   : '<?php echo esc_js( __( 'تلميح: تأكد من اسم المستودع أو أن المستودع Public.', 'rsyi-sa' ) ); ?>'
                };
                var errorType = res.data.error_type || '';
                var hint      = hints[errorType] ? '<br><small style="color:#888;">' + hints[errorType] + '</small>' : '';
                stat.html('❌ ' + (res.data.message || '<?php echo esc_js( __( 'خطأ غير معروف.', 'rsyi-sa' ) ); ?>') + hint).css('color', '#c0392b');
            }
        }).fail(function () {
            btn.prop('disabled', false);
            stat.html('❌ <?php echo esc_js( __( 'فشل الاتصال بالسيرفر.', 'rsyi-sa' ) ); ?>').css('color', '#c0392b');
        });
    });

    // ── Re-seed violation types ────────────────────────────────────────────
    $('#rsyi-seed-violations').on('click', function () {
        var btn  = $(this).prop('disabled', true);
        var stat = $('#rsyi-seed-status');
        stat.text('<?php echo esc_js( __( 'جاري الإضافة…', 'rsyi-sa' ) ); ?>').css('color', '#666').show();

        $.post(rsyiSA.ajaxUrl, {
            action: 'rsyi_reseed_violation_types',
            _nonce: rsyiSA.nonce
        }, function (res) {
            btn.prop('disabled', false);
            if (res.success) {
                stat.text('✅ ' + res.data.message).css('color', '#1a7a4a');
                setTimeout(function () { location.reload(); }, 1500);
            } else {
                stat.text('❌ ' + (res.data.message || '<?php echo esc_js( __( 'خطأ.', 'rsyi-sa' ) ); ?>')).css('color', '#c0392b');
            }
        }).fail(function () {
            btn.prop('disabled', false);
            stat.text('❌ <?php echo esc_js( __( 'فشل الاتصال.', 'rsyi-sa' ) ); ?>').css('color', '#c0392b');
        });
    });

}(jQuery));
</script>
