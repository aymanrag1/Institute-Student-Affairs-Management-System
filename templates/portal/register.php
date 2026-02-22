<?php
/**
 * Portal – Self Registration Form
 * Variables: $cohorts
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

$docs_page_id  = get_option( 'rsyi_page_documents' );
$docs_page_url = $docs_page_id ? get_permalink( $docs_page_id ) : home_url( '/student-documents/' );
?>
<div class="rsyi-portal rsyi-register" dir="ltr" style="max-width:680px; margin:0 auto; font-family:sans-serif;">

    <div style="text-align:center; margin-bottom:28px;">
        <?php
        $logo = get_option( 'rsyi_logo_url' );
        if ( $logo ) : ?>
            <img src="<?php echo esc_url( $logo ); ?>" alt="Institute Logo" style="max-height:80px; margin-bottom:12px;">
        <?php endif; ?>
        <h2 style="margin:0 0 4px; color:#0073aa;"><?php echo esc_html( get_option( 'rsyi_institute_name', 'Red Sea Yacht Institute' ) ); ?></h2>
        <p style="color:#666; margin:0;"><?php esc_html_e( 'Student Registration Portal', 'rsyi-sa' ); ?></p>
    </div>

    <div id="rsyi_reg_messages"></div>

    <form id="rsyi_register_form" novalidate
          style="background:#fff; border:1px solid #dee2e6; border-radius:8px; padding:28px; box-shadow:0 2px 8px rgba(0,0,0,.06);">

        <h3 style="margin-top:0; border-bottom:2px solid #0073aa; padding-bottom:10px; color:#0073aa;">
            <?php esc_html_e( 'New Student Account', 'rsyi-sa' ); ?>
        </h3>

        <table class="form-table">
            <tr>
                <th><label for="reg_arabic_name"><?php esc_html_e( 'Full Name in Arabic *', 'rsyi-sa' ); ?></label></th>
                <td><input type="text" id="reg_arabic_name" name="arabic_full_name" class="large-text" dir="rtl" required
                           placeholder="الاسم الرباعي كما في الشهادة"></td>
            </tr>
            <tr>
                <th><label for="reg_english_name"><?php esc_html_e( 'Full Name in English *', 'rsyi-sa' ); ?></label></th>
                <td><input type="text" id="reg_english_name" name="english_full_name" class="large-text" required
                           placeholder="As it appears on your certificate"></td>
            </tr>
            <tr>
                <th><label for="reg_username"><?php esc_html_e( 'Username *', 'rsyi-sa' ); ?></label></th>
                <td>
                    <input type="text" id="reg_username" name="user_login" class="regular-text" required
                           pattern="[a-zA-Z0-9_\-]+" placeholder="letters, numbers, _ only">
                    <p class="description"><?php esc_html_e( 'English letters and numbers only. Cannot be changed later.', 'rsyi-sa' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="reg_email"><?php esc_html_e( 'Email Address *', 'rsyi-sa' ); ?></label></th>
                <td><input type="email" id="reg_email" name="user_email" class="regular-text" required
                           placeholder="your@email.com"></td>
            </tr>
            <tr>
                <th><label for="reg_password"><?php esc_html_e( 'Password *', 'rsyi-sa' ); ?></label></th>
                <td>
                    <input type="password" id="reg_password" name="password" class="regular-text" required minlength="8"
                           placeholder="At least 8 characters">
                </td>
            </tr>
            <tr>
                <th><label for="reg_national_id"><?php esc_html_e( 'National ID Number', 'rsyi-sa' ); ?></label></th>
                <td><input type="text" id="reg_national_id" name="national_id_number" class="regular-text" maxlength="14"></td>
            </tr>
            <tr>
                <th><label for="reg_dob"><?php esc_html_e( 'Date of Birth', 'rsyi-sa' ); ?></label></th>
                <td><input type="date" id="reg_dob" name="date_of_birth"></td>
            </tr>
            <tr>
                <th><label for="reg_phone"><?php esc_html_e( 'Phone Number', 'rsyi-sa' ); ?></label></th>
                <td><input type="tel" id="reg_phone" name="phone" class="regular-text" placeholder="+20 1XX XXX XXXX"></td>
            </tr>
            <tr>
                <th><label for="reg_cohort"><?php esc_html_e( 'Cohort *', 'rsyi-sa' ); ?></label></th>
                <td>
                    <select id="reg_cohort" name="cohort_id" required style="min-width:240px;">
                        <option value=""><?php esc_html_e( '— Select Your Cohort —', 'rsyi-sa' ); ?></option>
                        <?php foreach ( $cohorts as $c ) : ?>
                            <option value="<?php echo esc_attr( $c->id ); ?>"><?php echo esc_html( $c->name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>

        <p class="submit" style="margin-top:20px;">
            <button type="submit" class="button button-primary button-large" style="min-width:200px; padding:10px 20px; font-size:15px;">
                <?php esc_html_e( 'Create My Account', 'rsyi-sa' ); ?>
            </button>
        </p>
    </form>

    <p style="text-align:center; margin-top:16px; color:#666;">
        <?php esc_html_e( 'Already have an account?', 'rsyi-sa' ); ?>
        <a href="<?php echo esc_url( wp_login_url() ); ?>"><?php esc_html_e( 'Sign In', 'rsyi-sa' ); ?></a>
    </p>
</div>

<style>
.rsyi-alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; }
.rsyi-alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
.rsyi-alert-danger  { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
.rsyi-alert ul { margin: 6px 0 0; padding-left: 20px; }
</style>

<script>
jQuery(function($){
    $('#rsyi_register_form').on('submit', function(e){
        e.preventDefault();
        var btn = $(this).find('button[type=submit]');
        btn.prop('disabled', true).text('<?php echo esc_js( __( 'Creating account…', 'rsyi-sa' ) ); ?>');
        $('#rsyi_reg_messages').html('');

        var data = $(this).serializeArray().reduce(function(obj, item){ obj[item.name]=item.value; return obj; }, {});
        data.action = 'rsyi_register_student';
        data._nonce = rsyiPortal.nonce;
        var parts = (data.english_full_name || '').trim().split(' ');
        data.english_first_name = parts[0] || '';
        data.english_last_name  = parts.slice(1).join(' ') || '';

        $.post(rsyiPortal.ajaxUrl, data, function(res){
            btn.prop('disabled', false).text('<?php echo esc_js( __( 'Create My Account', 'rsyi-sa' ) ); ?>');
            if(res.success){
                $('#rsyi_reg_messages').html('<div class="rsyi-alert rsyi-alert-success"><p>✅ ' + res.data.message + '</p><p><?php echo esc_js( __( 'Redirecting to document upload…', 'rsyi-sa' ) ); ?></p></div>');
                setTimeout(function(){ window.location.href = <?php echo wp_json_encode( $docs_page_url ); ?>; }, 2000);
            } else {
                var errors = res.data.errors || [res.data.message];
                var html = '<div class="rsyi-alert rsyi-alert-danger"><ul>';
                errors.forEach(function(e){ html += '<li>❌ ' + e + '</li>'; });
                html += '</ul></div>';
                $('#rsyi_reg_messages').html(html);
            }
        }).fail(function(){
            btn.prop('disabled', false).text('<?php echo esc_js( __( 'Create My Account', 'rsyi-sa' ) ); ?>');
        });
    });
});
</script>
