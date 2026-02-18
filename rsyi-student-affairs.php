<?php
/**
 * Plugin Name:       RSYI Student Affairs Management System
 * Plugin URI:        https://redsea-yacht-institute.com
 * Description:       Complete Student Affairs Management System for Red Sea Yacht Institute (El Gouna). Manages student accounts, mandatory documents, exit/overnight permits, behavior violations, cohort governance, and expulsion workflow.
 * Version:           1.0.0
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
define( 'RSYI_SA_VERSION',     '1.0.0' );
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
        'PDF_Generator'              => 'includes/pdf/class-pdf-generator.php',
        'Modules\\Accounts'          => 'includes/modules/class-accounts.php',
        'Modules\\Documents'         => 'includes/modules/class-documents.php',
        'Modules\\Requests'          => 'includes/modules/class-requests.php',
        'Modules\\Behavior'          => 'includes/modules/class-behavior.php',
        'Modules\\Cohorts'           => 'includes/modules/class-cohorts.php',
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

// ─── Bootstrap ────────────────────────────────────────────────────────────────
add_action( 'plugins_loaded', 'rsyi_sa_init' );
function rsyi_sa_init(): void {
    load_plugin_textdomain( 'rsyi-sa', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

    // Secure download endpoint (registered before any output)
    RSYI_SA\Secure_Download::init();

    // Core modules
    RSYI_SA\Modules\Accounts::init();
    RSYI_SA\Modules\Documents::init();
    RSYI_SA\Modules\Requests::init();
    RSYI_SA\Modules\Behavior::init();
    RSYI_SA\Modules\Cohorts::init();

    // PDF generator AJAX (registered here so the class is always loaded)
    RSYI_SA\PDF_Generator::init_ajax();

    if ( is_admin() ) {
        RSYI_SA\Admin\Menu::init();
    }

    // Frontend portal shortcodes
    RSYI_SA\Portal\Shortcodes::init();
}

// ─── Enqueue assets ──────────────────────────────────────────────────────────
add_action( 'admin_enqueue_scripts', 'rsyi_sa_admin_assets' );
function rsyi_sa_admin_assets( string $hook ): void {
    if ( strpos( $hook, 'rsyi' ) === false ) {
        return;
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
            'confirm_approve' => __( 'هل أنت متأكد من الموافقة؟', 'rsyi-sa' ),
            'confirm_reject'  => __( 'هل أنت متأكد من الرفض؟', 'rsyi-sa' ),
        ],
    ] );
}

add_action( 'wp_enqueue_scripts', 'rsyi_sa_portal_assets' );
function rsyi_sa_portal_assets(): void {
    if ( ! is_user_logged_in() ) {
        return;
    }
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
