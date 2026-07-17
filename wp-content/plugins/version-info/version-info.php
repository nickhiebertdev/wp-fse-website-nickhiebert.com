<?php

/**
 * Plugin Name: Version Info
 * Plugin URI: https://versioninfoplugin.com
 * Description: Show current WordPress, PHP, Web Server, and MySQL versions optionally in the admin footer, WP-Admin bar, or dashboard widget.
 * Author: Gaucho Plugins
 * Author URI: https://gauchoplugins.com
 * Version: 2.0.3
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: version-info
 * Requires PHP: 5.6
 * Requires at least: 4.7
 * Tested up to: 7.0
 *
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
if ( function_exists( 'vi_fs' ) ) {
    vi_fs()->set_basename( false, __FILE__ );
} else {
    if ( !function_exists( 'vi_fs' ) ) {
        function vi_fs() {
            global $vi_fs;
            if ( !isset( $vi_fs ) ) {
                // Activate multisite network integration.
                if ( !defined( 'WP_FS__PRODUCT_24628_MULTISITE' ) ) {
                    define( 'WP_FS__PRODUCT_24628_MULTISITE', true );
                }
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/vendor/freemius/start.php';
                $vi_fs = fs_dynamic_init( array(
                    'id'               => '24628',
                    'slug'             => 'version-info',
                    'type'             => 'plugin',
                    'public_key'       => 'pk_0aab3921d653db1046b13586d75f7',
                    'is_premium'       => false,
                    'premium_suffix'   => 'PRO',
                    'has_addons'       => false,
                    'has_paid_plans'   => true,
                    'is_org_compliant' => true,
                    'menu'             => array(
                        'slug'   => 'version-info',
                        'parent' => array(
                            'slug' => 'options-general.php',
                        ),
                    ),
                    'is_live'          => true,
                ) );
            }
            return $vi_fs;
        }

        vi_fs();
        vi_fs()->add_filter( 'pricing/show_annual_in_monthly', '__return_false' );
        vi_fs()->add_filter( 'plugin_icon', function () {
            return dirname( __FILE__ ) . '/assets/plugin-icon.png';
        } );
        do_action( 'vi_fs_loaded' );
    }
    define( 'VERSION_INFO_VERSION', '2.0.1' );
    define( 'VERSION_INFO_FILE', __FILE__ );
    define( 'VERSION_INFO_DIR', plugin_dir_path( __FILE__ ) );
    spl_autoload_register( function ( $class ) {
        $prefix = 'GauchoPlugins\\VersionInfo\\';
        // Replaces str_starts_with() (PHP 8.0+) for PHP 5.6 compatibility.
        if ( 0 !== strpos( $class, $prefix ) ) {
            return;
        }
        $relative = substr( $class, strlen( $prefix ) );
        $pro_prefix = 'Pro\\';
        if ( 0 === strpos( $relative, $pro_prefix ) ) {
            $relative = substr( $relative, strlen( $pro_prefix ) );
            $file = VERSION_INFO_DIR . 'includes/pro/' . str_replace( '\\', '/', $relative ) . '.php';
        } else {
            $file = VERSION_INFO_DIR . 'includes/' . str_replace( '\\', '/', $relative ) . '.php';
        }
        if ( file_exists( $file ) ) {
            require_once $file;
        }
    } );
    ( new GauchoPlugins\VersionInfo\Plugin() )->init();
}