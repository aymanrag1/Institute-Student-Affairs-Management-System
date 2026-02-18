<?php
/**
 * Portal – Self Registration Form
 * Variables: $cohorts
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="rsyi-portal rsyi-register" dir="rtl">
    <h2><?php esc_html_e( 'تسجيل طالب جديد', 'rsyi-sa' ); ?></h2>
    <p><?php esc_html_e( 'معهد البحر الأحمر للتخطيط البحري – برنامج المنح الدراسية', 'rsyi-sa' ); ?></p>

    <div id="rsyi_reg_messages"></div>

    <form id="rsyi_register_form" novalidate>
        <table class="form-table">
            <tr>
                <th><label for="reg_arabic_name"><?php esc_html_e( 'الاسم الرباعي بالعربية (كما في الشهادة)*', 'rsyi-sa' ); ?></label></th>
                <td><input type="text" id="reg_arabic_name" name="arabic_full_name" class="large-text" dir="rtl" required></td>
            </tr>
            <tr>
                <th><label for="reg_english_name"><?php esc_html_e( 'الاسم الرباعي بالإنجليزية (كما في الشهادة)*', 'rsyi-sa' ); ?></label></th>
                <td><input type="text" id="reg_english_name" name="english_full_name" class="large-text" dir="ltr" required></td>
            </tr>
            <tr>
                <th><label for="reg_username"><?php esc_html_e( 'اسم المستخدم*', 'rsyi-sa' ); ?></label></th>
                <td><input type="text" id="reg_username" name="user_login" class="regular-text" required
                           pattern="[a-zA-Z0-9_\-]+" title="<?php esc_attr_e( 'حروف إنجليزية وأرقام فقط', 'rsyi-sa' ); ?>"></td>
            </tr>
            <tr>
                <th><label for="reg_email"><?php esc_html_e( 'البريد الإلكتروني*', 'rsyi-sa' ); ?></label></th>
                <td><input type="email" id="reg_email" name="user_email" class="regular-text" required></td>
            </tr>
            <tr>
                <th><label for="reg_password"><?php esc_html_e( 'كلمة المرور* (8 أحرف على الأقل)', 'rsyi-sa' ); ?></label></th>
                <td><input type="password" id="reg_password" name="password" class="regular-text" required minlength="8"></td>
            </tr>
            <tr>
                <th><label for="reg_national_id"><?php esc_html_e( 'رقم الهوية الوطنية', 'rsyi-sa' ); ?></label></th>
                <td><input type="text" id="reg_national_id" name="national_id_number" class="regular-text" maxlength="14"></td>
            </tr>
            <tr>
                <th><label for="reg_dob"><?php esc_html_e( 'تاريخ الميلاد', 'rsyi-sa' ); ?></label></th>
                <td><input type="date" id="reg_dob" name="date_of_birth"></td>
            </tr>
            <tr>
                <th><label for="reg_phone"><?php esc_html_e( 'رقم الهاتف', 'rsyi-sa' ); ?></label></th>
                <td><input type="tel" id="reg_phone" name="phone" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="reg_cohort"><?php esc_html_e( 'الفوج*', 'rsyi-sa' ); ?></label></th>
                <td>
                    <select id="reg_cohort" name="cohort_id" required>
                        <option value=""><?php esc_html_e( '— اختر الفوج —', 'rsyi-sa' ); ?></option>
                        <?php foreach ( $cohorts as $c ) : ?>
                            <option value="<?php echo esc_attr( $c->id ); ?>"><?php echo esc_html( $c->name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary button-large">
                <?php esc_html_e( 'إنشاء الحساب', 'rsyi-sa' ); ?>
            </button>
        </p>
    </form>

    <p><?php esc_html_e( 'لديك حساب؟', 'rsyi-sa' ); ?>
        <a href="<?php echo esc_url( wp_login_url() ); ?>"><?php esc_html_e( 'تسجيل الدخول', 'rsyi-sa' ); ?></a>
    </p>
</div>

<script>
jQuery(function($){
    $('#rsyi_register_form').on('submit', function(e){
        e.preventDefault();
        var btn = $(this).find('button[type=submit]');
        btn.prop('disabled', true);
        $('#rsyi_reg_messages').html('');

        var data = $(this).serializeArray().reduce(function(obj, item){ obj[item.name]=item.value; return obj; }, {});
        data.action = 'rsyi_register_student';
        data._nonce = rsyiPortal.nonce;
        // Split english name for first/last
        var parts = (data.english_full_name || '').trim().split(' ');
        data.english_first_name = parts[0] || '';
        data.english_last_name  = parts.slice(1).join(' ') || '';

        $.post(rsyiPortal.ajaxUrl, data, function(res){
            btn.prop('disabled', false);
            if(res.success){
                $('#rsyi_reg_messages').html('<div class="rsyi-alert rsyi-alert-success"><p>✅ ' + res.data.message + '</p></div>');
                setTimeout(function(){ window.location.href = '<?php echo esc_js( home_url( '/portal/documents/' ) ); ?>'; }, 2000);
            } else {
                var errors = res.data.errors || [res.data.message];
                var html = '<div class="rsyi-alert rsyi-alert-danger"><ul>';
                errors.forEach(function(e){ html += '<li>❌ ' + e + '</li>'; });
                html += '</ul></div>';
                $('#rsyi_reg_messages').html(html);
            }
        }).fail(function(){ btn.prop('disabled', false); });
    });
});
</script>
