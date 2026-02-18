<?php
/**
 * Admin – Add Student Template
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

if ( ! current_user_can( 'rsyi_create_student' ) ) {
    wp_die( __( 'صلاحية غير كافية.', 'rsyi-sa' ) );
}

$cohorts = \RSYI_SA\Modules\Cohorts::get_all_cohorts();
?>
<h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=rsyi-students' ) ); ?>" class="page-title-action" style="text-decoration:none;">
        &rarr; <?php esc_html_e( 'الطلاب', 'rsyi-sa' ); ?>
    </a>
    <?php esc_html_e( 'إضافة طالب جديد', 'rsyi-sa' ); ?>
</h1>
<hr class="wp-header-end">

<div id="rsyi-add-student-notices"></div>

<form id="rsyi-add-student-form" novalidate>
    <div class="rsyi-card" style="max-width:720px;">
        <h2 style="margin-top:0;"><?php esc_html_e( 'البيانات الشخصية', 'rsyi-sa' ); ?></h2>

        <table class="form-table" role="presentation">
            <tr>
                <th><label for="arabic_full_name"><?php esc_html_e( 'الاسم العربي الكامل', 'rsyi-sa' ); ?> <span style="color:red">*</span></label></th>
                <td>
                    <input type="text" id="arabic_full_name" name="arabic_full_name"
                           class="regular-text" required
                           placeholder="<?php esc_attr_e( 'مثال: محمد أحمد علي', 'rsyi-sa' ); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="english_full_name"><?php esc_html_e( 'الاسم الإنجليزي الكامل', 'rsyi-sa' ); ?> <span style="color:red">*</span></label></th>
                <td>
                    <input type="text" id="english_full_name" name="english_full_name"
                           class="regular-text" required
                           placeholder="<?php esc_attr_e( 'e.g. Mohamed Ahmed Ali', 'rsyi-sa' ); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="national_id_number"><?php esc_html_e( 'رقم الهوية القومية', 'rsyi-sa' ); ?></label></th>
                <td>
                    <input type="text" id="national_id_number" name="national_id_number"
                           class="regular-text" maxlength="20">
                </td>
            </tr>
            <tr>
                <th><label for="date_of_birth"><?php esc_html_e( 'تاريخ الميلاد', 'rsyi-sa' ); ?></label></th>
                <td><input type="date" id="date_of_birth" name="date_of_birth"></td>
            </tr>
            <tr>
                <th><label for="phone"><?php esc_html_e( 'رقم الهاتف', 'rsyi-sa' ); ?></label></th>
                <td>
                    <input type="text" id="phone" name="phone" class="regular-text"
                           placeholder="<?php esc_attr_e( '01xxxxxxxxx', 'rsyi-sa' ); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="cohort_id"><?php esc_html_e( 'الفوج', 'rsyi-sa' ); ?> <span style="color:red">*</span></label></th>
                <td>
                    <select id="cohort_id" name="cohort_id" required>
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
        </table>

        <h2><?php esc_html_e( 'بيانات الحساب', 'rsyi-sa' ); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th><label for="user_email"><?php esc_html_e( 'البريد الإلكتروني', 'rsyi-sa' ); ?> <span style="color:red">*</span></label></th>
                <td>
                    <input type="email" id="user_email" name="user_email"
                           class="regular-text" required
                           placeholder="<?php esc_attr_e( 'student@example.com', 'rsyi-sa' ); ?>">
                    <p class="description">
                        <?php esc_html_e( 'سيُستخدم أيضاً كاسم للمستخدم تلقائياً.', 'rsyi-sa' ); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><label for="user_login"><?php esc_html_e( 'اسم المستخدم', 'rsyi-sa' ); ?></label></th>
                <td>
                    <input type="text" id="user_login" name="user_login"
                           class="regular-text"
                           placeholder="<?php esc_attr_e( 'يُملأ تلقائياً من البريد الإلكتروني', 'rsyi-sa' ); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="password"><?php esc_html_e( 'كلمة المرور', 'rsyi-sa' ); ?> <span style="color:red">*</span></label></th>
                <td>
                    <input type="text" id="password" name="password"
                           class="regular-text" required minlength="8"
                           placeholder="<?php esc_attr_e( '8 أحرف على الأقل', 'rsyi-sa' ); ?>">
                    <button type="button" id="rsyi-gen-pass" class="button" style="margin-right:6px;">
                        <?php esc_html_e( 'توليد تلقائي', 'rsyi-sa' ); ?>
                    </button>
                </td>
            </tr>
        </table>

        <p class="submit" style="border-top:1px solid #eee;padding-top:16px;margin-top:8px;">
            <button type="submit" id="rsyi-submit-student" class="button button-primary button-large">
                <?php esc_html_e( 'إنشاء حساب الطالب', 'rsyi-sa' ); ?>
            </button>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=rsyi-students' ) ); ?>" class="button button-large" style="margin-right:8px;">
                <?php esc_html_e( 'إلغاء', 'rsyi-sa' ); ?>
            </a>
        </p>
    </div>
</form>

<script>
(function ($) {
    // Auto-fill username from email
    $('#user_email').on('blur', function () {
        var login = $('#user_login');
        if (!login.val()) {
            login.val($(this).val().split('@')[0].replace(/[^a-z0-9_.-]/gi, '').toLowerCase());
        }
    });

    // Generate random password
    $('#rsyi-gen-pass').on('click', function () {
        var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789@#$!';
        var pass  = '';
        for (var i = 0; i < 12; i++) {
            pass += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        $('#password').val(pass);
    });

    // Submit
    $('#rsyi-add-student-form').on('submit', function (e) {
        e.preventDefault();

        var btn    = $('#rsyi-submit-student');
        var notices = $('#rsyi-add-student-notices');
        notices.empty();

        // Basic client validation
        var cohort = $('#cohort_id').val();
        if (!cohort || cohort === '0') {
            notices.html('<div class="notice notice-error"><p><?php echo esc_js( __( 'يرجى اختيار الفوج.', 'rsyi-sa' ) ); ?></p></div>');
            return;
        }

        var data = {
            action           : 'rsyi_staff_create_student',
            _nonce           : rsyiSA.nonce,
            arabic_full_name : $('#arabic_full_name').val(),
            english_full_name: $('#english_full_name').val(),
            user_email       : $('#user_email').val(),
            user_login       : $('#user_login').val() || $('#user_email').val().split('@')[0],
            password         : $('#password').val(),
            national_id_number: $('#national_id_number').val(),
            date_of_birth    : $('#date_of_birth').val(),
            phone            : $('#phone').val(),
            cohort_id        : cohort
        };

        btn.prop('disabled', true).text('<?php echo esc_js( __( 'جاري الإنشاء…', 'rsyi-sa' ) ); ?>');

        $.post(rsyiSA.ajaxUrl, data, function (res) {
            btn.prop('disabled', false).text('<?php echo esc_js( __( 'إنشاء حساب الطالب', 'rsyi-sa' ) ); ?>');
            if (res.success) {
                notices.html('<div class="notice notice-success"><p>' +
                    $('<div>').text(res.data.message).html() +
                    ' <a href="<?php echo esc_url( admin_url( 'admin.php?page=rsyi-students&action=view&id=' ) ); ?>' + res.data.profile_id + '">' +
                    '<?php echo esc_js( __( 'عرض الملف', 'rsyi-sa' ) ); ?></a></p></div>');
                $('#rsyi-add-student-form')[0].reset();
                window.scrollTo(0, 0);
            } else {
                var msgs = res.data.errors ? res.data.errors.join('<br>') : (res.data.message || '<?php echo esc_js( __( 'حدث خطأ.', 'rsyi-sa' ) ); ?>');
                notices.html('<div class="notice notice-error"><p>' + msgs + '</p></div>');
            }
        }).fail(function () {
            btn.prop('disabled', false).text('<?php echo esc_js( __( 'إنشاء حساب الطالب', 'rsyi-sa' ) ); ?>');
            notices.html('<div class="notice notice-error"><p><?php echo esc_js( __( 'فشل الاتصال بالخادم.', 'rsyi-sa' ) ); ?></p></div>');
        });
    });
}(jQuery));
</script>
