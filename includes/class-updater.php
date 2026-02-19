<?php
/**
 * GitHub Update Checker
 *
 * Hooks into WordPress's built-in plugin update mechanism and checks the
 * configured GitHub repository for new releases.
 *
 * How it works:
 *   1. On each WordPress update check, the class calls the GitHub Releases API.
 *   2. If the latest release tag is newer than RSYI_SA_VERSION, WordPress shows
 *      the standard "Update Available" notice in the plugins list.
 *   3. The admin clicks "Update Now" and WordPress downloads & installs the zip
 *      attached to the GitHub release (created by the GitHub Actions workflow).
 *
 * Setup:
 *   - The GitHub repository must be public, OR a Personal Access Token must be
 *     saved in Settings → الإعدادات → GitHub Token.
 *   - Every new version must be published as a GitHub Release with a tag like
 *     v1.0.1, v1.1.0, etc.  The GitHub Actions workflow (.github/workflows/release.yml)
 *     automatically attaches a correctly-structured zip to each release.
 *
 * @package RSYI_StudentAffairs
 */

namespace RSYI_SA;

defined( 'ABSPATH' ) || exit;

class Updater {

    private const GITHUB_USER    = 'aymanrag1';
    private const GITHUB_REPO    = 'Institute-Student-Affairs-Management-System';
    private const PLUGIN_SLUG    = 'rsyi-student-affairs';
    private const TRANSIENT_KEY  = 'rsyi_sa_update_cache';
    private const CACHE_DURATION = 12 * HOUR_IN_SECONDS;

    // ── Bootstrap ─────────────────────────────────────────────────────────────

    public static function init(): void {
        add_filter( 'pre_set_site_transient_update_plugins', [ __CLASS__, 'check_update' ] );
        add_filter( 'plugins_api',                           [ __CLASS__, 'plugin_info' ], 10, 3 );
        add_filter( 'upgrader_source_selection',             [ __CLASS__, 'fix_directory_name' ], 10, 4 );
        add_action( 'upgrader_process_complete',             [ __CLASS__, 'clear_cache' ], 10, 2 );
    }

    // ── GitHub API ────────────────────────────────────────────────────────────

    /**
     * Build the request headers for GitHub API calls.
     */
    private static function build_headers(): array {
        $headers = [
            'Accept'     => 'application/vnd.github.v3+json',
            'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; RSYI-SA/' . RSYI_SA_VERSION,
        ];
        $token = get_option( 'rsyi_github_token', '' );
        if ( $token ) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }
        return $headers;
    }

    /**
     * Fetch the latest GitHub release, with a 12-hour cache.
     */
    private static function get_latest_release(): ?object {
        $cached = get_transient( self::TRANSIENT_KEY );
        if ( $cached !== false ) {
            return $cached ?: null;
        }

        $url      = 'https://api.github.com/repos/' . self::GITHUB_USER . '/' . self::GITHUB_REPO . '/releases/latest';
        $response = wp_remote_get( $url, [ 'timeout' => 15, 'headers' => self::build_headers() ] );

        if ( is_wp_error( $response ) || (int) wp_remote_retrieve_response_code( $response ) !== 200 ) {
            // Cache the failure for 30 minutes to avoid hammering the API
            set_transient( self::TRANSIENT_KEY, 0, 30 * MINUTE_IN_SECONDS );
            return null;
        }

        $release = json_decode( wp_remote_retrieve_body( $response ) );
        set_transient( self::TRANSIENT_KEY, $release, self::CACHE_DURATION );
        return $release;
    }

    /**
     * Directly check GitHub API connectivity and return a detailed status array.
     * Always makes a fresh HTTP request (bypasses the cache).
     * Updates the cache on success.
     *
     * Return keys:
     *   'success'        (bool)
     *   'latest_version' (string)  – present on success
     *   'error'          (string)  – present on failure
     *   'error_type'     (string)  – 'network' | 'not_found' | 'auth' | 'no_releases' | 'api_error'
     *   'http_code'      (int)     – raw HTTP response code
     */
    public static function check_connection(): array {
        $url      = 'https://api.github.com/repos/' . self::GITHUB_USER . '/' . self::GITHUB_REPO . '/releases/latest';
        $response = wp_remote_get( $url, [ 'timeout' => 15, 'headers' => self::build_headers() ] );

        // ── Network / WP_Error ────────────────────────────────────────────────
        if ( is_wp_error( $response ) ) {
            return [
                'success'    => false,
                'error_type' => 'network',
                'http_code'  => 0,
                'error'      => sprintf(
                    /* translators: %s: WP_Error message */
                    __( 'تعذّر الوصول إلى GitHub: %s', 'rsyi-sa' ),
                    $response->get_error_message()
                ),
            ];
        }

        $http_code = (int) wp_remote_retrieve_response_code( $response );
        $body      = json_decode( wp_remote_retrieve_body( $response ) );

        // ── Authentication errors ─────────────────────────────────────────────
        if ( $http_code === 401 || $http_code === 403 ) {
            return [
                'success'    => false,
                'error_type' => 'auth',
                'http_code'  => $http_code,
                'error'      => __( 'GitHub Token غير صحيح أو الصلاحيات غير كافية (repo: read).', 'rsyi-sa' ),
            ];
        }

        // ── Repo not found OR no releases yet ────────────────────────────────
        if ( $http_code === 404 ) {
            $msg = $body->message ?? '';
            $error_type = ( strpos( strtolower( $msg ), 'not found' ) !== false && ! get_option( 'rsyi_github_token', '' ) )
                ? 'not_found'
                : 'no_releases';

            return [
                'success'    => false,
                'error_type' => $error_type,
                'http_code'  => 404,
                'error'      => __( 'المستودع غير موجود، أو لا توجد إصدارات (Releases) منشورة بعد، أو المستودع خاص ويحتاج Token.', 'rsyi-sa' ),
            ];
        }

        // ── Unexpected HTTP code ──────────────────────────────────────────────
        if ( $http_code !== 200 ) {
            return [
                'success'    => false,
                'error_type' => 'api_error',
                'http_code'  => $http_code,
                'error'      => sprintf(
                    /* translators: %d: HTTP status code */
                    __( 'استجابة غير متوقعة من GitHub API (HTTP %d).', 'rsyi-sa' ),
                    $http_code
                ),
            ];
        }

        // ── Success ───────────────────────────────────────────────────────────
        // Refresh the cache with the new data.
        set_transient( self::TRANSIENT_KEY, $body, self::CACHE_DURATION );

        return [
            'success'        => true,
            'http_code'      => 200,
            'latest_version' => ltrim( $body->tag_name ?? '', 'v' ),
            'release'        => $body,
        ];
    }

    /**
     * Resolve the best download URL for the release:
     * prefer an attached .zip asset over GitHub's auto-generated zipball.
     */
    private static function get_package_url( object $release ): string {
        if ( ! empty( $release->assets ) ) {
            foreach ( $release->assets as $asset ) {
                if ( str_ends_with( $asset->name, '.zip' ) ) {
                    return $asset->browser_download_url;
                }
            }
        }
        return $release->zipball_url;
    }

    // ── WordPress Hooks ───────────────────────────────────────────────────────

    /**
     * Inject update data into WordPress's update transient.
     *
     * @param object $transient
     * @return object
     */
    public static function check_update( object $transient ): object {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $release = self::get_latest_release();
        if ( ! $release || empty( $release->tag_name ) ) {
            return $transient;
        }

        $latest_version = ltrim( $release->tag_name, 'v' );
        $plugin_file    = plugin_basename( RSYI_SA_PLUGIN_FILE );

        if ( version_compare( RSYI_SA_VERSION, $latest_version, '<' ) ) {
            $transient->response[ $plugin_file ] = (object) [
                'id'           => 'github/' . self::GITHUB_REPO,
                'slug'         => self::PLUGIN_SLUG,
                'plugin'       => $plugin_file,
                'new_version'  => $latest_version,
                'url'          => 'https://github.com/' . self::GITHUB_USER . '/' . self::GITHUB_REPO,
                'package'      => self::get_package_url( $release ),
                'icons'        => [],
                'banners'      => [],
                'tested'       => '6.7',
                'requires'     => '6.0',
                'requires_php' => '8.0',
            ];
        } else {
            // Current version is up-to-date; remove any stale response entry
            unset( $transient->response[ $plugin_file ] );
        }

        return $transient;
    }

    /**
     * Provide plugin information for the "View version details" popup.
     *
     * @param false|object|array $result
     * @param string             $action
     * @param object             $args
     * @return false|object
     */
    public static function plugin_info( $result, string $action, object $args ) {
        if ( $action !== 'plugin_information' || ( $args->slug ?? '' ) !== self::PLUGIN_SLUG ) {
            return $result;
        }

        $release = self::get_latest_release();
        if ( ! $release ) {
            return $result;
        }

        return (object) [
            'name'          => 'RSYI Student Affairs Management System',
            'slug'          => self::PLUGIN_SLUG,
            'version'       => ltrim( $release->tag_name, 'v' ),
            'author'        => '<a href="https://redsea-yacht-institute.com">RSYI Dev Team</a>',
            'homepage'      => 'https://github.com/' . self::GITHUB_USER . '/' . self::GITHUB_REPO,
            'download_link' => self::get_package_url( $release ),
            'sections'      => [
                'description' => '<p>نظام إدارة شؤون الطلاب الخاص بالمعهد البحري.</p>',
                'changelog'   => '<pre>' . esc_html( $release->body ?? __( 'لا يوجد سجل تغييرات.', 'rsyi-sa' ) ) . '</pre>',
            ],
            'requires'      => '6.0',
            'tested'        => '6.7',
            'requires_php'  => '8.0',
            'last_updated'  => $release->published_at,
        ];
    }

    /**
     * Rename the extracted folder to match the plugin slug.
     *
     * GitHub's auto-generated zipball extracts to a folder named like:
     *   aymanrag1-Institute-Student-Affairs-Management-System-<sha>/
     * WordPress requires the folder to match the plugin slug:
     *   rsyi-student-affairs/
     *
     * @param string      $source
     * @param string      $remote_source
     * @param WP_Upgrader $upgrader
     * @param array       $hook_extra
     * @return string
     */
    public static function fix_directory_name( string $source, string $remote_source, $upgrader, array $hook_extra = [] ): string {
        if ( empty( $hook_extra['plugin'] ) ) {
            return $source;
        }

        $plugin_file = plugin_basename( RSYI_SA_PLUGIN_FILE );
        if ( $hook_extra['plugin'] !== $plugin_file ) {
            return $source;
        }

        // If the folder already has the correct name, nothing to do
        $correct_path = trailingslashit( $remote_source ) . self::PLUGIN_SLUG;
        if ( trailingslashit( $source ) === trailingslashit( $correct_path ) ) {
            return $source;
        }

        global $wp_filesystem;
        if ( $wp_filesystem->move( $source, $correct_path, true ) ) {
            return trailingslashit( $correct_path );
        }

        return $source;
    }

    /**
     * Clear the cached release data after the plugin is updated.
     *
     * @param WP_Upgrader $upgrader
     * @param array       $hook_extra
     */
    public static function clear_cache( $upgrader, array $hook_extra ): void {
        if (
            isset( $hook_extra['action'], $hook_extra['type'] ) &&
            $hook_extra['action'] === 'update' &&
            $hook_extra['type'] === 'plugin'
        ) {
            delete_transient( self::TRANSIENT_KEY );
        }
    }
}
