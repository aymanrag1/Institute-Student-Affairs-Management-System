<?php
/**
 * Plugin Name:       RSYI Student Affairs Management System
 * Plugin URI:        https://redsea-yacht-institute.com
 * Description:       Complete Student Affairs Management System for Red Sea Yacht Institute (El Gouna). Manages student accounts, mandatory documents, exit/overnight permits, behavior violations, cohort governance, and expulsion workflow.
 * Version:           1.2.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            RSYI Dev Team
 * Author URI:        https://redsea-yacht-institute.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       rsyi-sa
 * Domain Path:       /languages
 * Network:           false
 *
 * @package RSYI_StudentAffairs
 */

defined( 'ABSPATH' ) || exit;

// ─── Constants ────────────────────────────────────────────────────────────────
define( 'RSYI_SA_VERSION',     '1.2.0' );
define( 'RSYI_SA_PLUGIN_FILE', __FILE__ );
define( 'RSYI_SA_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'RSYI_SA_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'RSYI_SA_UPLOAD_DIR',  WP_CONTENT_DIR . '/uploads/rsyi-docs' );
define( 'RSYI_SA_UPLOAD_URL',  WP_CONTENT_URL  . '/uploads/rsyi-docs' );

// ─── Autoloader ───────────────────────────────────────────────────────────────
// NOTE: We use substr() to strip the 'RSYI_SA\' prefix and keep the remaining
// namespace path with its original backslashes so map keys match exactly.
spl_autoload_register( function ( string $class ): void {
    // Only handle classes in our namespace
    $prefix = 'RSYI_SA\\';
    if ( strpos( $class, $prefix ) !== 0 ) {
        return;
    }

    // Strip prefix; preserve sub-namespace backslashes for map lookup
    $relative = substr( $class, strlen( $prefix ) );

    $map = [
        'DB_Installer'               => 'includes/class-db-installer.php',
        'Roles'                      => 'includes/class-roles.php',
        'Audit_Log'                  => 'includes/class-audit-log.php',
        'Email_Notifications'        => 'includes/class-email-notifications.php',
        'Secure_Download'            => 'includes/class-secure-download.php',
        'Updater'                    => 'includes/class-updater.php',
        'PDF_Generator'              => 'includes/pdf/class-pdf-generator.php',
        'Modules\\Accounts'          => 'includes/modules/class-accounts.php',
        'Modules\\Documents'         => 'includes/modules/class-documents.php',
        'Modules\\Requests'          => 'includes/modules/class-requests.php',
        'Modules\\Behavior'          => 'includes/modules/class-behavior.php',
        'Modules\\Cohorts'           => 'includes/modules/class-cohorts.php',
        'Modules\\Evaluations'       => 'includes/modules/class-evaluations.php',
        'Admin\\Menu'                => 'includes/admin/class-admin-menu.php',
        'Portal\\Shortcodes'         => 'includes/portal/class-portal-shortcodes.php',
    ];

    if ( isset( $map[ $relative ] ) ) {
        $file = RSYI_SA_PLUGIN_DIR . $map[ $relative ];
        if ( file_exists( $file ) ) {
            require_once $file;
        }
    }
} );

// ─── Activation / Deactivation ───────────────────────────────────────────────
register_activation_hook( __FILE__, [ 'RSYI_SA\\DB_Installer', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'RSYI_SA\\Roles', 'remove_roles' ] );

// ─── RSYI HR System Dependency Check ─────────────────────────────────────────
// HR System is considered active when its version constant is defined OR when
// its primary API function exists. The constant approach is more reliable since
// it is set at file-include time, before any hooks run.
function rsyi_sa_hr_active(): bool {
    return defined( 'RSYI_HR_VERSION' )
        || function_exists( 'rsyi_hr_get_departments' )
        || class_exists( 'RSYI_HR\\Plugin' )
        || class_exists( 'RSYI_HR' );
}

// Show an admin notice if HR System is not active.
add_action( 'admin_notices', 'rsyi_sa_hr_dependency_notice' );
function rsyi_sa_hr_dependency_notice(): void {
    if ( ! rsyi_sa_hr_active() ) {
        echo '<div class="notice notice-error is-dismissible"><p>'
            . '<strong>RSYI Student Affairs</strong> يتطلب تفعيل '
            . '<strong>RSYI HR System</strong> أولاً لكي يعمل بشكل صحيح.'
            . '</p></div>';
    }
}

// ─── Bootstrap ────────────────────────────────────────────────────────────────
// Priority 20 ensures we load AFTER RSYI HR System (which loads at priority 10).
add_action( 'plugins_loaded', 'rsyi_sa_init', 20 );
function rsyi_sa_init(): void {
    // Bail out gracefully if RSYI HR System is not active.
    if ( ! rsyi_sa_hr_active() ) {
        return;
    }

    load_plugin_textdomain( 'rsyi-sa', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

    // ── Register HR role-extension hook ──────────────────────────────────────
    // This ensures SA caps are (re-)applied to HR roles on every page load
    // in addition to the direct call inside sync_roles() / add_roles().
    RSYI_SA\Roles::register_hr_extend_hook();

    // ── Permanent role sync ───────────────────────────────────────────────────
    // On every load, if the stored roles-version is behind the current plugin
    // version, sync new roles / caps without requiring a full deactivation cycle.
    $stored_roles_ver = get_option( 'rsyi_sa_roles_version', '0.0.0' );
    if ( version_compare( $stored_roles_ver, RSYI_SA_VERSION, '<' ) ) {
        RSYI_SA\Roles::sync_roles();
        // Also run DB upgrades in case new tables were added
        RSYI_SA\DB_Installer::create_tables();
        update_option( RSYI_SA\DB_Installer::DB_VERSION_OPTION, RSYI_SA\DB_Installer::DB_VERSION );
    }

    // Secure download endpoint (registered before any output)
    RSYI_SA\Secure_Download::init();

    // Core modules
    RSYI_SA\Modules\Accounts::init();
    RSYI_SA\Modules\Documents::init();
    RSYI_SA\Modules\Requests::init();
    RSYI_SA\Modules\Behavior::init();
    RSYI_SA\Modules\Cohorts::init();
    RSYI_SA\Modules\Evaluations::init();

    // GitHub update checker (runs on both front and back end checks)
    RSYI_SA\Updater::init();

    // PDF generator AJAX (registered here so the class is always loaded)
    RSYI_SA\PDF_Generator::init_ajax();

    if ( is_admin() ) {
        RSYI_SA\Admin\Menu::init();
    }

    // Frontend portal shortcodes
    RSYI_SA\Portal\Shortcodes::init();
}

// ─── Login / Registration Redirects ─────────────────────────────────────────
// Redirect students to their portal dashboard after login (non-admin users).
add_filter( 'login_redirect', 'rsyi_sa_login_redirect', 10, 3 );
function rsyi_sa_login_redirect( string $redirect_to, string $requested_redirect_to, $user ): string {
    if ( is_wp_error( $user ) ) {
        return $redirect_to;
    }
    // Only redirect non-admins who have the student or other RSYI roles
    if ( ! empty( $user->roles ) && ! in_array( 'administrator', (array) $user->roles, true ) ) {
        if ( in_array( 'rsyi_student', (array) $user->roles, true ) ) {
            $dashboard_id = get_option( 'rsyi_page_dashboard' );
            if ( $dashboard_id ) {
                return get_permalink( $dashboard_id ) ?: $redirect_to;
            }
        }
        // Staff roles → WP admin
        $staff_roles = [
            'rsyi_dean',
            'rsyi_student_affairs_mgr',
            'rsyi_student_supervisor',
            'rsyi_dorm_supervisor',
            'rsyi_senior_naval_trainer',
            'rsyi_naval_trainer',
            'rsyi_preparatory_lecturer',
        ];
        if ( array_intersect( $staff_roles, (array) $user->roles ) ) {
            return admin_url();
        }
    }
    return $redirect_to;
}

// ─── Enqueue assets ──────────────────────────────────────────────────────────
add_action( 'admin_enqueue_scripts', 'rsyi_sa_admin_assets' );
function rsyi_sa_admin_assets( string $hook ): void {
    if ( strpos( $hook, 'rsyi' ) === false ) {
        return;
    }
    // Enqueue WP Media library on settings page (needed for logo uploader)
    if ( strpos( $hook, 'rsyi-settings' ) !== false ) {
        wp_enqueue_media();
    }
    wp_enqueue_style(
        'rsyi-sa-admin',
        RSYI_SA_PLUGIN_URL . 'assets/css/admin.css',
        [],
        RSYI_SA_VERSION
    );
    wp_enqueue_script(
        'rsyi-sa-admin',
        RSYI_SA_PLUGIN_URL . 'assets/js/admin.js',
        [ 'jquery' ],
        RSYI_SA_VERSION,
        true
    );
    wp_localize_script( 'rsyi-sa-admin', 'rsyiSA', [
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'rsyi_sa_admin' ),
        'i18n'    => [
            'confirm_approve' => __( 'Are you sure you want to approve?', 'rsyi-sa' ),
            'confirm_reject'  => __( 'Are you sure you want to reject?', 'rsyi-sa' ),
        ],
    ] );
}

add_action( 'wp_enqueue_scripts', 'rsyi_sa_portal_assets' );
function rsyi_sa_portal_assets(): void {
    // Load on all front-end pages: registration page needs the nonce even for guests
    wp_enqueue_style(
        'rsyi-sa-portal',
        RSYI_SA_PLUGIN_URL . 'assets/css/portal.css',
        [],
        RSYI_SA_VERSION
    );
    wp_enqueue_script(
        'rsyi-sa-portal',
        RSYI_SA_PLUGIN_URL . 'assets/js/portal.js',
        [ 'jquery' ],
        RSYI_SA_VERSION,
        true
    );
    wp_localize_script( 'rsyi-sa-portal', 'rsyiPortal', [
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'rsyi_sa_portal' ),
    ] );
}
