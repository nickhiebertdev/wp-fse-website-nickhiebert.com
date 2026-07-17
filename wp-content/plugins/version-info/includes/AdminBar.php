<?php


namespace GauchoPlugins\VersionInfo;

class AdminBar {

    public function register() {
        add_action( 'admin_bar_menu', [ $this, 'add_nodes' ], 100 );
    }

    public function add_nodes( \WP_Admin_Bar $wp_admin_bar ) {
        if ( ! Plugin::current_user_can_view() ) {
            return;
        }

        $nodes = [];

        if ( get_option( 'version_info_show_admin_bar', false ) ) {
            $nodes['version_info_admin_bar'] = [
                'id'     => 'version_info_admin_bar',
                'title'  => $this->get_version_string(),
                'parent' => 'top-secondary',
            ];
        }

        /** @var array<string, array{id: string, title: string, parent?: string, meta?: array}> $nodes */
        $nodes = apply_filters( 'version_info_admin_bar_nodes', $nodes );

        foreach ( $nodes as $node ) {
            $wp_admin_bar->add_node( $node );
        }
    }

    private function get_version_string() {
        global $wpdb;

        $wp_version      = apply_filters( 'version_info_wp_version', get_bloginfo( 'version' ) );
        $php_version     = apply_filters( 'version_info_php_version', phpversion() );
        $server_software = apply_filters(
            'version_info_server_software',
            sanitize_text_field( isset( $_SERVER['SERVER_SOFTWARE'] ) ? $_SERVER['SERVER_SOFTWARE'] : __( 'Unknown', 'version-info' ) )
        );
        $mysql_version   = apply_filters( 'version_info_mysql_version', (string) $wpdb->get_var( 'SELECT VERSION()' ) );

        return sprintf(
            /* translators: %1$s: WP version, %2$s: PHP version, %3$s: server software, %4$s: MySQL version */
            __( 'WordPress %1$s | PHP %2$s | Web Server %3$s | MySQL %4$s', 'version-info' ),
            esc_html( $wp_version ),
            esc_html( $php_version ),
            esc_html( $server_software ),
            esc_html( $mysql_version )
        );
    }
}
