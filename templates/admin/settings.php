<?php
/**
 * Admin Settings Template
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

if ( ! current_user_can( 'rsyi_manage_settings' ) ) {
    wp_die( __( 'Insufficient permissions.', 'rsyi-sa' ) );
}

$institute_name = get_option( 'rsyi_institute_name', 'Red Sea Yacht Institute' );
$dean_name      = get_option( 'rsyi_dean_name', '' );
$logo_url       = get_option( 'rsyi_logo_url', '' );
$logo_id        = (int) get_option( 'rsyi_logo_attachment_id', 0 );
$github_token   = get_option( 'rsyi_github_token', '' );

global $wpdb;
$violation_types_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}rsyi_violation_types" );

// Check which portal pages exist
$portal_pages = [
    'rsyi_page_register'   => [ 'label' => 'Student Registration',    'slug' => 'student-register' ],
    'rsyi_page_dashboard'  => [ 'label' => 'Student Dashboard',       'slug' => 'student-dashboard' ],
    'rsyi_page_documents'  => [ 'label' => 'My Documents',            'slug' => 'student-documents' ],
    'rsyi_page_requests'   => [ 'label' => 'Permits & Requests',      'slug' => 'student-requests' ],
    'rsyi_page_behavior'   => [ 'label' => 'Behavior Record',         'slug' => 'student-behavior' ],
    'rsyi_page_evaluation' => [ 'label' => 'Cohort Peer Evaluation',  'slug' => 'student-evaluation' ],
];
$missing_pages = 0;
foreach ( $portal_pages as $option => $info ) {
    $pid = get_option( $option );
    if ( ! $pid || ! get_post( $pid ) ) $missing_pages++;
}
?>
<h1><?php esc_html_e( 'Settings', 'rsyi-sa' ); ?></h1>
<hr class="wp-header-end">
<div id="rsyi-settings-notices"></div>

<!-- ══ Section 0: Portal Pages ═══════════════════════════════════════════ -->
<div class="rsyi-card" style="max-width:700px;margin-bottom:24px;">
    <h2 style="margin-top:0;">🌐 <?php esc_html_e( 'Student Portal Pages', 'rsyi-sa' ); ?></h2>
    <p class="description" style="margin-bottom:14px;">
        <?php esc_html_e( 'The plugin needs 6 WordPress pages for the student portal. Click the button below to create them automatically.', 'rsyi-sa' ); ?>
    </p>

    <?php if ( $missing_pages > 0 ) : ?>
    <div class="notice notice-warning inline" style="margin-bottom:14px;"><p>
        <?php printf(
            esc_html__( '%d portal page(s) are missing. Click "Create Portal Pages" to fix this.', 'rsyi-sa' ),
            $missing_pages
        ); ?>
    </p></div>
    <?php endif; ?>

    <table class="widefat" style="margin-bottom:14px;">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Page', 'rsyi-sa' ); ?></th>
                <th><?php esc_html_e( 'Status', 'rsyi-sa' ); ?></th>
                <th><?php esc_html_e( 'URL', 'rsyi-sa' ); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ( $portal_pages as $option => $info ) :
            $pid    = get_option( $option );
            $exists = $pid && get_post( $pid );
        ?>
        <tr>
            <td><strong><?php echo esc_html( $info['label'] ); ?></strong></td>
            <td>
                <?php if ( $exists ) : ?>
                <span style="color:#27ae60; font-weight:700;">✅ <?php esc_html_e( 'Created', 'rsyi-sa' ); ?></span>
                <?php else : ?>
                <span style="color:#e74c3c; font-weight:700;">❌ <?php esc_html_e( 'Missing', 'rsyi-sa' ); ?></span>
                <?php endif; ?>
            </td>
            <td>
                <?php if ( $exists ) : ?>
                <a href="<?php echo esc_url( get_permalink( $pid ) ); ?>" target="_blank" style="font-size:12px;">
                    <?php echo esc_url( get_permalink( $pid ) ); ?>
                </a>
                <?php else : ?>
                <span style="color:#888; font-size:12px;">—</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <p>
        <button type="button" id="rsyi-create-pages" class="button <?php echo $missing_pages > 0 ? 'button-primary' : ''; ?> button-large">
            🌐 <?php esc_html_e( 'Create Portal Pages', 'rsyi-sa' ); ?>
        </button>
        <span id="rsyi-pages-status" style="margin-left:12px;display:none;font-weight:600;"></span>
    </p>
    <p class="description">
        <?php esc_html_e( 'This is safe to run multiple times – existing pages will not be overwritten.', 'rsyi-sa' ); ?>
    </p>
</div>

<!-- ══ Section 1: Institute Info ══════════════════════════════════════════ -->
<div class="rsyi-card" style="max-width:700px;margin-bottom:24px;">
    <h2 style="margin-top:0;"><?php esc_html_e( 'Institute Information', 'rsyi-sa' ); ?></h2>

    <table class="form-table" role="presentation">
        <tr>
            <th><label for="rsyi_institute_name"><?php esc_html_e( 'Institute Name', 'rsyi-sa' ); ?></label></th>
            <td>
                <input type="text" id="rsyi_institute_name" class="regular-text"
                       value="<?php echo esc_attr( $institute_name ); ?>"
                       placeholder="<?php esc_attr_e( 'Enter institute name', 'rsyi-sa' ); ?>">
                <p class="description"><?php esc_html_e( 'Appears on the student dashboard and PDF reports.', 'rsyi-sa' ); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="rsyi_dean_name"><?php esc_html_e( 'Dean Name', 'rsyi-sa' ); ?></label></th>
            <td>
                <input type="text" id="rsyi_dean_name" class="regular-text"
                       value="<?php echo esc_attr( $dean_name ); ?>"
                       placeholder="<?php esc_attr_e( 'Full name of the dean', 'rsyi-sa' ); ?>">
                <p class="description"><?php esc_html_e( 'Used in expulsion letters and report signatures.', 'rsyi-sa' ); ?></p>
            </td>
        </tr>
        <tr>
            <th><?php esc_html_e( 'Institute Logo', 'rsyi-sa' ); ?></th>
            <td>
                <div id="rsyi-logo-preview" style="margin-bottom:10px;">
                    <?php if ( $logo_url ) : ?>
                        <img src="<?php echo esc_url( $logo_url ); ?>" alt="Logo"
                             style="max-height:80px;max-width:200px;border:1px solid #ddd;padding:4px;border-radius:4px;">
                    <?php else : ?>
                        <span style="color:#888;"><?php esc_html_e( 'No logo uploaded yet.', 'rsyi-sa' ); ?></span>
                    <?php endif; ?>
                </div>
                <input type="hidden" id="rsyi_logo_attachment_id" value="<?php echo esc_attr( $logo_id ); ?>">
                <input type="hidden" id="rsyi_logo_url" value="<?php echo esc_attr( $logo_url ); ?>">
                <button type="button" id="rsyi-upload-logo" class="button">
                    📷 <?php esc_html_e( 'Choose Logo from Media Library', 'rsyi-sa' ); ?>
                </button>
                <?php if ( $logo_url ) : ?>
                <button type="button" id="rsyi-remove-logo" class="button" style="margin-left:6px;color:#c0392b;">
                    🗑 <?php esc_html_e( 'Remove Logo', 'rsyi-sa' ); ?>
                </button>
                <?php endif; ?>
                <p class="description"><?php esc_html_e( 'Used in PDF reports and expulsion letters.', 'rsyi-sa' ); ?></p>
            </td>
        </tr>
    </table>

    <p class="submit" style="border-top:1px solid #eee;padding-top:14px;margin-top:4px;">
        <button type="button" id="rsyi-save-settings" class="button button-primary button-large">
            <?php esc_html_e( 'Save Settings', 'rsyi-sa' ); ?>
        </button>
        <span id="rsyi-settings-status" style="margin-left:12px;display:none;font-weight:600;"></span>
    </p>
</div>

<!-- ══ Section 2: Violation Types ════════════════════════════════════════ -->
<div class="rsyi-card" style="max-width:700px;margin-bottom:24px;">
    <h2 style="margin-top:0;"><?php esc_html_e( 'Violation Types', 'rsyi-sa' ); ?></h2>
    <p>
        <?php
        printf(
            esc_html__( 'Currently registered violation types: %s', 'rsyi-sa' ),
            '<strong>' . esc_html( $violation_types_count ) . '</strong>'
        );
        ?>
    </p>
    <?php if ( $violation_types_count === 0 ) : ?>
    <div class="notice notice-warning inline"><p>
        <?php esc_html_e( 'Violation types table is empty. Click the button below to add the default types.', 'rsyi-sa' ); ?>
    </p></div>
    <?php endif; ?>
    <p>
        <button type="button" id="rsyi-seed-violations" class="button <?php echo $violation_types_count === 0 ? 'button-primary' : ''; ?>">
            🔄 <?php esc_html_e( 'Re-seed Default Violation Types', 'rsyi-sa' ); ?>
        </button>
        <span id="rsyi-seed-status" style="margin-left:10px;display:none;font-weight:600;"></span>
    </p>
    <p class="description">
        <?php esc_html_e( 'Note: This only adds missing types and does not delete existing ones.', 'rsyi-sa' ); ?>
    </p>
</div>

<!-- ══ Section 3: GitHub Auto-Update ══════════════════════════════════════ -->
<div class="rsyi-card" style="max-width:700px;margin-bottom:24px;">
    <h2 style="margin-top:0;">
        🔄 <?php esc_html_e( 'GitHub Auto-Update', 'rsyi-sa' ); ?>
    </h2>
    <p class="description" style="margin-bottom:16px;">
        <?php esc_html_e( 'When a new Release is published on GitHub, an update notification will appear automatically on the Plugins page.', 'rsyi-sa' ); ?>
    </p>
    <table class="form-table" role="presentation">
        <tr>
            <th><?php esc_html_e( 'Repository', 'rsyi-sa' ); ?></th>
            <td>
                <code style="font-size:13px;">aymanrag1/Institute-Student-Affairs-Management-System</code>
                <p class="description"><?php esc_html_e( 'Repository must be public, or enter a Personal Access Token below for private repos.', 'rsyi-sa' ); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="rsyi_github_token"><?php esc_html_e( 'GitHub Token (optional)', 'rsyi-sa' ); ?></label></th>
            <td>
                <input type="password" id="rsyi_github_token" class="regular-text"
                       value="<?php echo esc_attr( $github_token ? str_repeat( '•', 20 ) : '' ); ?>"
                       placeholder="ghp_xxxxxxxxxxxxxxxxxxxx"
                       autocomplete="new-password">
                <p class="description">
                    <?php esc_html_e( 'Required only for private repositories. Create a token at: GitHub → Settings → Developer settings → Personal access tokens. Required scope: repo (read).', 'rsyi-sa' ); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th><?php esc_html_e( 'Connection Status', 'rsyi-sa' ); ?></th>
            <td>
                <?php
                $cached = get_transient( 'rsyi_sa_update_cache' );
                if ( $cached === false ) :
                ?>
                <span style="color:#888;"><?php esc_html_e( 'Not checked yet. WordPress will check automatically within 12 hours.', 'rsyi-sa' ); ?></span>
                <?php elseif ( ! $cached ) : ?>
                <span style="color:#c0392b;">&#10008; <?php esc_html_e( 'Failed to connect to GitHub API. Make sure the repository is public or enter a valid token.', 'rsyi-sa' ); ?></span>
                <?php else : ?>
                <span style="color:#1a7a4a;">&#10004; <?php printf(
                    esc_html__( 'Connected. Latest GitHub release: %s', 'rsyi-sa' ),
                    '<strong>' . esc_html( $cached->tag_name ?? 'unknown' ) . '</strong>'
                ); ?></span>
                <?php endif; ?>
                <br>
                <button type="button" id="rsyi-check-update" class="button" style="margin-top:8px;">
                    <?php esc_html_e( 'Check for Updates Now', 'rsyi-sa' ); ?>
                </button>
                <span id="rsyi-check-update-status" style="margin-left:8px;display:none;"></span>
            </td>
        </tr>
    </table>

    <p class="submit" style="border-top:1px solid #eee;padding-top:14px;margin-top:4px;">
        <button type="button" id="rsyi-save-github" class="button button-primary">
            <?php esc_html_e( 'Save GitHub Settings', 'rsyi-sa' ); ?>
        </button>
        <span id="rsyi-github-status" style="margin-left:12px;display:none;font-weight:600;"></span>
    </p>
</div>

<!-- ══ Section 4: System Integration ════════════════════════════════════ -->
<div class="rsyi-card" style="max-width:700px;">
    <h2 style="margin-top:0;"><?php esc_html_e( 'System Integration', 'rsyi-sa' ); ?></h2>
    <p class="description">
        <?php esc_html_e( 'This section allows future integration of the Student Affairs system with other institute systems (Warehouse, HR, etc.).', 'rsyi-sa' ); ?>
    </p>
    <table class="form-table" role="presentation">
        <tr>
            <th><?php esc_html_e( 'RSYI HR System', 'rsyi-sa' ); ?></th>
            <td>
                <?php
                $hr_active = rsyi_sa_hr_active();
                if ( $hr_active ) :
                    $emp_count = (int) apply_filters( 'rsyi_hr_count_employees', 0, 'all' );
                ?>
                <span class="rsyi-badge rsyi-status-active">✅ <?php esc_html_e( 'Connected', 'rsyi-sa' ); ?></span>
                <p class="description">
                    <?php
                    printf(
                        /* translators: %1$s: HR version, %2$d: employee count */
                        esc_html__( 'RSYI HR System v%1$s active. Total employees: %2$d', 'rsyi-sa' ),
                        esc_html( defined( 'RSYI_HR_VERSION' ) ? RSYI_HR_VERSION : '—' ),
                        $emp_count
                    );
                    ?>
                </p>
                <?php else : ?>
                <span class="rsyi-badge rsyi-status-pending"><?php esc_html_e( 'Not Connected', 'rsyi-sa' ); ?></span>
                <p class="description">
                    <?php esc_html_e( 'RSYI HR System plugin is not active. Student Affairs requires it to manage roles and departments.', 'rsyi-sa' ); ?>
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
            title   : '<?php echo esc_js( __( 'Choose Institute Logo', 'rsyi-sa' ) ); ?>',
            button  : { text: '<?php echo esc_js( __( 'Use This Image', 'rsyi-sa' ) ); ?>' },
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
        $('#rsyi-logo-preview').html('<span style="color:#888;"><?php echo esc_js( __( 'No logo uploaded yet.', 'rsyi-sa' ) ); ?></span>');
    });

    // ── Create Portal Pages ────────────────────────────────────────────────
    $('#rsyi-create-pages').on('click', function () {
        var btn    = $(this).prop('disabled', true);
        var status = $('#rsyi-pages-status');
        status.text('⏳ <?php echo esc_js( __( 'Creating pages…', 'rsyi-sa' ) ); ?>').css('color', '#666').show();

        $.post(rsyiSA.ajaxUrl, {
            action: 'rsyi_create_portal_pages',
            _nonce: rsyiSA.nonce
        }, function (res) {
            btn.prop('disabled', false);
            if (res.success) {
                status.text('✅ ' + res.data.message).css('color', '#1a7a4a');
                setTimeout(function () { location.reload(); }, 1500);
            } else {
                status.text('❌ ' + (res.data.message || '<?php echo esc_js( __( 'An error occurred.', 'rsyi-sa' ) ); ?>')).css('color', '#c0392b');
            }
        }).fail(function () {
            btn.prop('disabled', false);
            status.text('❌ <?php echo esc_js( __( 'Connection failed.', 'rsyi-sa' ) ); ?>').css('color', '#c0392b');
        });
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
                status.text('❌ ' + (res.data.message || '<?php echo esc_js( __( 'An error occurred.', 'rsyi-sa' ) ); ?>')).css('color', '#c0392b').show();
            }
        }).fail(function () {
            btn.prop('disabled', false);
            status.text('❌ <?php echo esc_js( __( 'Connection failed.', 'rsyi-sa' ) ); ?>').css('color', '#c0392b').show();
        });
    });

    // ── Save GitHub settings ───────────────────────────────────────────────
    $('#rsyi-save-github').on('click', function () {
        var btn    = $(this).prop('disabled', true);
        var status = $('#rsyi-github-status');
        status.hide();

        var tokenVal    = $('#rsyi_github_token').val();
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
                status.text('❌ ' + (res.data.message || '<?php echo esc_js( __( 'Error.', 'rsyi-sa' ) ); ?>')).css('color', '#c0392b').show();
            }
        }).fail(function () {
            btn.prop('disabled', false);
            status.text('❌ <?php echo esc_js( __( 'Connection failed.', 'rsyi-sa' ) ); ?>').css('color', '#c0392b').show();
        });
    });

    // ── Force update check ─────────────────────────────────────────────────
    $('#rsyi-check-update').on('click', function () {
        var btn  = $(this).prop('disabled', true);
        var stat = $('#rsyi-check-update-status');
        stat.html('⏳ <?php echo esc_js( __( 'Checking GitHub…', 'rsyi-sa' ) ); ?>').css('color', '#666').show();

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
                    'auth'        : '<?php echo esc_js( __( 'Hint: Create a Personal Access Token and enter it in the Token field above.', 'rsyi-sa' ) ); ?>',
                    'no_releases' : '<?php echo esc_js( __( 'Hint: No Releases published on GitHub yet. Use: git tag v1.0.0 && git push origin v1.0.0', 'rsyi-sa' ) ); ?>',
                    'network'     : '<?php echo esc_js( __( 'Hint: Make sure the server can reach the internet (github.com).', 'rsyi-sa' ) ); ?>',
                    'not_found'   : '<?php echo esc_js( __( 'Hint: Verify the repository name and that it is Public.', 'rsyi-sa' ) ); ?>'
                };
                var errorType = res.data.error_type || '';
                var hint      = hints[errorType] ? '<br><small style="color:#888;">' + hints[errorType] + '</small>' : '';
                stat.html('❌ ' + (res.data.message || '<?php echo esc_js( __( 'Unknown error.', 'rsyi-sa' ) ); ?>') + hint).css('color', '#c0392b');
            }
        }).fail(function () {
            btn.prop('disabled', false);
            stat.html('❌ <?php echo esc_js( __( 'Connection to server failed.', 'rsyi-sa' ) ); ?>').css('color', '#c0392b');
        });
    });

    // ── Re-seed violation types ────────────────────────────────────────────
    $('#rsyi-seed-violations').on('click', function () {
        var btn  = $(this).prop('disabled', true);
        var stat = $('#rsyi-seed-status');
        stat.text('<?php echo esc_js( __( 'Adding…', 'rsyi-sa' ) ); ?>').css('color', '#666').show();

        $.post(rsyiSA.ajaxUrl, {
            action: 'rsyi_reseed_violation_types',
            _nonce: rsyiSA.nonce
        }, function (res) {
            btn.prop('disabled', false);
            if (res.success) {
                stat.text('✅ ' + res.data.message).css('color', '#1a7a4a');
                setTimeout(function () { location.reload(); }, 1500);
            } else {
                stat.text('❌ ' + (res.data.message || '<?php echo esc_js( __( 'Error.', 'rsyi-sa' ) ); ?>')).css('color', '#c0392b');
            }
        }).fail(function () {
            btn.prop('disabled', false);
            stat.text('❌ <?php echo esc_js( __( 'Connection failed.', 'rsyi-sa' ) ); ?>').css('color', '#c0392b');
        });
    });

}(jQuery));
</script>
