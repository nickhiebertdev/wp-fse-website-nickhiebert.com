<?php


namespace GauchoPlugins\VersionInfo;

class DashboardWidget {

    public function register() {
        add_action( 'wp_dashboard_setup', [ $this, 'maybe_add_widget' ] );
    }

    public function maybe_add_widget() {
        if ( ! get_option( 'version_info_show_dashboard_widget', false ) || ! Plugin::current_user_can_view() ) {
            return;
        }

        wp_add_dashboard_widget(
            'version_info_dashboard_widget',
            __( 'Version Info', 'version-info' ),
            [ $this, 'render' ]
        );
    }

    public function render() {
        global $wpdb;

        $items = [
            'wordpress' => [
                'label' => __( 'WordPress Version:', 'version-info' ),
                'value' => apply_filters( 'version_info_wp_version', get_bloginfo( 'version' ) ),
            ],
            'php' => [
                'label' => __( 'PHP Version:', 'version-info' ),
                'value' => apply_filters( 'version_info_php_version', phpversion() ),
            ],
            'server' => [
                'label' => __( 'Web Server:', 'version-info' ),
                'value' => apply_filters(
                    'version_info_server_software',
                    sanitize_text_field( isset( $_SERVER['SERVER_SOFTWARE'] ) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown' )
                ),
            ],
            'mysql' => [
                'label' => __( 'MySQL Version:', 'version-info' ),
                'value' => apply_filters( 'version_info_mysql_version', (string) $wpdb->db_version() ),
            ],
        ];

        /** @var array<string, array{label: string, value: string, html?: string}> $items */
        $items = apply_filters( 'version_info_dashboard_widget_items', $items );

        $allowed = [
            'span'     => [ 'id' => true, 'class' => true, 'style' => true ],
            'div'      => [ 'id' => true, 'class' => true, 'style' => true ],
            'strong'   => [ 'style' => true ],
            'code'     => [],
            'em'       => [],
            'br'       => [],
            'svg'      => [
                'id'      => true,
                'class'   => true,
                'style'   => true,
                'width'   => true,
                'height'  => true,
                'viewbox' => true,
                'xmlns'   => true,
            ],
            'polyline' => [
                'id'           => true,
                'class'        => true,
                'style'        => true,
                'points'       => true,
                'stroke'       => true,
                'stroke-width' => true,
                'fill'         => true,
            ],
        ];

        // WP's safecss_filter_attr strips `display` by default. Allow it for
        // the duration of this render so our percent-bar markup survives.
        add_filter( 'safe_style_css', [ $this, 'allow_display_css' ] );

        echo '<ul>';
        foreach ( $items as $item ) {
            echo '<li><strong>' . esc_html( $item['label'] ) . '</strong> ';
            if ( isset( $item['html'] ) && '' !== $item['html'] ) {
                echo wp_kses( $item['html'], $allowed );
            } else {
                echo esc_html( $item['value'] );
            }
            echo '</li>';
        }
        echo '</ul>';

        remove_filter( 'safe_style_css', [ $this, 'allow_display_css' ] );
    }

    /**
     * @param string[] $props
     * @return string[]
     */
    public function allow_display_css( $props ) {
        if ( ! in_array( 'display', $props, true ) ) {
            $props[] = 'display';
        }
        return $props;
    }
}
