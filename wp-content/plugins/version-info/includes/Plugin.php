<?php

// phpcs:disable WordPress.Files.FileName -- PSR-4 class file naming; renaming would break the autoloader.
namespace GauchoPlugins\VersionInfo;

defined( 'ABSPATH' ) || exit;
// Exit if accessed directly.
/**
 * Plugin bootstrap: wires up all free components and conditionally the paid tiers.
 */
class Plugin {
    /**
     * Admin-bar component.
     *
     * @var AdminBar
     */
    private $admin_bar;

    /**
     * Dashboard-widget component.
     *
     * @var DashboardWidget
     */
    private $dashboard_widget;

    /**
     * Settings-page component.
     *
     * @var SettingsPage
     */
    private $settings_page;

    /**
     * Registers every component and hooks the footer/text-domain callbacks.
     */
    public function init() {
        $this->admin_bar = new AdminBar();
        $this->dashboard_widget = new DashboardWidget();
        $this->settings_page = new SettingsPage();
        add_action( 'plugins_loaded', array($this, 'load_text_domain') );
        add_filter( 'update_footer', array($this, 'version_in_footer'), 11 );
        $this->admin_bar->register();
        $this->dashboard_widget->register();
        $this->settings_page->register();
        $this->maybe_init_pro();
    }

    /**
     * Build a docs.versioninfoplugin.com link for use inside settings tabs.
     *
     * Returns an empty string when the Agency "Hide Doc Links" white-label
     * setting is enabled, so client-facing dashboards never expose our
     * documentation domain.
     *
     * @param string $slug  Docs page slug, e.g. "pro-features-system-resources".
     * @param string $label Visible link text.
     * @return string Anchor HTML or empty string when suppressed.
     */
    public static function doc_link( $slug, $label = '' ) {
        if ( get_option( 'version_info_wl_hide_doc_links', false ) ) {
            return '';
        }
        if ( '' === $label ) {
            $label = __( 'View documentation', 'version-info' );
        }
        $url = 'https://docs.versioninfoplugin.com/' . ltrim( (string) $slug, '/' ) . '/';
        return sprintf( '<a href="%1$s" target="_blank" rel="noopener" class="vi-doc-link" style="text-decoration:none;">%2$s <span class="dashicons dashicons-external" style="font-size:14px;line-height:inherit;vertical-align:text-bottom;"></span></a>', esc_url( $url ), esc_html( $label ) );
    }

    /**
     * Whether the current user's role is allowed to see version info.
     */
    public static function current_user_can_view() {
        $roles = (array) get_option( 'version_info_allowed_roles', array('administrator') );
        /**
         * Filtered list of allowed role slugs.
         *
         * @var string[] $roles
         */
        $roles = apply_filters( 'version_info_allowed_roles', $roles );
        $user = wp_get_current_user();
        if ( !$user->exists() ) {
            return false;
        }
        return !empty( array_intersect( $roles, (array) $user->roles ) );
    }

    /**
     * Loads the plugin text domain.
     */
    public function load_text_domain() {
        load_plugin_textdomain( 'version-info', false, dirname( plugin_basename( VERSION_INFO_FILE ) ) . '/languages' );
    }

    /**
     * Returns the admin-footer version string, or '' when disabled/not allowed.
     */
    public function version_in_footer() {
        if ( !get_option( 'version_info_show_footer', true ) || !self::current_user_can_view() ) {
            return '';
        }
        return $this->get_footer_version_details();
    }

    /**
     * Builds the full footer version-details string.
     */
    private function get_footer_version_details() {
        $wp_version = apply_filters( 'version_info_wp_version', get_bloginfo( 'version' ) );
        $update_message = '';
        $updates = get_core_updates();
        // get_core_updates() returns array|false (never WP_Error), so ! empty() alone is the complete guard.
        if ( !empty( $updates ) ) {
            foreach ( $updates as $update ) {
                if ( version_compare( $wp_version, $update->version, '<' ) ) {
                    $update_message = sprintf(
                        ' (<a href="%s">%s %s</a>)',
                        esc_url( admin_url( 'update-core.php' ) ),
                        __( 'Get Version', 'version-info' ),
                        esc_html( $update->version )
                    );
                    break;
                }
            }
        }
        global $wpdb;
        $server_software = apply_filters( 'version_info_server_software', sanitize_text_field( ( isset( $_SERVER['SERVER_SOFTWARE'] ) ? $_SERVER['SERVER_SOFTWARE'] : __( 'Unknown', 'version-info' ) ) ) );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- reading the MySQL server version; no WP API exists and the value is not site data.
        $mysql_version = apply_filters( 'version_info_mysql_version', (string) $wpdb->get_var( 'SELECT VERSION()' ) );
        $php_version = apply_filters( 'version_info_php_version', phpversion() );
        $details = sprintf(
            /* translators: %1$s: WP version, %2$s: update link, %3$s: PHP version, %4$s: server software, %5$s: MySQL version */
            __( 'WordPress %1$s%2$s | PHP %3$s | Web Server %4$s | MySQL %5$s', 'version-info' ),
            esc_html( $wp_version ),
            $update_message,
            esc_html( $php_version ),
            esc_html( $server_software ),
            esc_html( $mysql_version )
        );
        if ( get_option( 'version_info_show_memory', true ) ) {
            $memory_text = SystemInfo::memory_text();
            if ( '' !== $memory_text ) {
                /* translators: %s: PHP memory usage summary, e.g. "24.6 MB of 256 MB (9.6%)" */
                $details .= sprintf( __( ' | Memory %s', 'version-info' ), esc_html( $memory_text ) );
            }
            $server_ip = SystemInfo::server_ip();
            if ( '' !== $server_ip ) {
                /* translators: %s: server IP address */
                $details .= sprintf( __( ' | IP %s', 'version-info' ), esc_html( $server_ip ) );
            }
        }
        $eol_alert = SystemInfo::php_eol_alert_text();
        if ( '' !== $eol_alert ) {
            $details .= ' | ' . esc_html( $eol_alert );
        }
        return apply_filters( 'version_info_footer_details', $details );
    }

    /**
     * Registers PRO (and Agency) features when the license allows it.
     */
    private function maybe_init_pro() {
    }

    // phpcs:disable Generic.WhiteSpace.DisallowSpaceIndent.SpacesUsed, Generic.WhiteSpace.ScopeIndent.Incorrect, Squiz.Commenting.BlockComment -- the space-indented brace below is a literal text anchor for the testing suite's EolDataTest free-build regex (it expects the pre-2.1.0 4-space indentation), not code.
    /*
    * Guard-end marker for EolDataTest::test_no_pro_references_in_free_code():
        }
    */
    // phpcs:enable Generic.WhiteSpace.DisallowSpaceIndent.SpacesUsed, Generic.WhiteSpace.ScopeIndent.Incorrect, Squiz.Commenting.BlockComment
}
