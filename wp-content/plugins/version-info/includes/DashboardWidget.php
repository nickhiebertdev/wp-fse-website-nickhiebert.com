<?php // phpcs:disable WordPress.Files.FileName -- PSR-4 class file naming; renaming would break the autoloader.


namespace GauchoPlugins\VersionInfo;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * "Version Info" dashboard widget.
 */
class DashboardWidget {

	/**
	 * Hooks the dashboard-widget registration.
	 */
	public function register() {
		add_action( 'wp_dashboard_setup', array( $this, 'maybe_add_widget' ) );
	}

	/**
	 * Adds the widget when enabled and the user may view version info.
	 */
	public function maybe_add_widget() {
		if ( ! get_option( 'version_info_show_dashboard_widget', false ) || ! Plugin::current_user_can_view() ) {
			return;
		}

		wp_add_dashboard_widget(
			'version_info_dashboard_widget',
			__( 'Version Info', 'version-info' ),
			array( $this, 'render' )
		);
	}

	/**
	 * Renders the widget body.
	 */
	public function render() {
		global $wpdb;

		$items = array(
			'wordpress' => array(
				'label' => __( 'WordPress Version:', 'version-info' ),
				'value' => apply_filters( 'version_info_wp_version', get_bloginfo( 'version' ) ),
			),
			'php'       => array(
				'label' => __( 'PHP Version:', 'version-info' ),
				'value' => apply_filters( 'version_info_php_version', phpversion() ),
			),
			'server'    => array(
				'label' => __( 'Web Server:', 'version-info' ),
				'value' => apply_filters(
					'version_info_server_software',
					sanitize_text_field( isset( $_SERVER['SERVER_SOFTWARE'] ) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown' )
				),
			),
			'mysql'     => array(
				'label' => __( 'MySQL Version:', 'version-info' ),
				'value' => apply_filters( 'version_info_mysql_version', (string) $wpdb->db_version() ),
			),
		);

		if ( get_option( 'version_info_show_memory', true ) ) {
			$memory_text = SystemInfo::memory_text();
			if ( '' !== $memory_text ) {
				// Key 'memory' — the PRO full HUD unsets this row in favor of
				// its richer memory_usage/php_memory/php_peak rows.
				$items['memory'] = array(
					'label' => __( 'PHP Memory:', 'version-info' ),
					'value' => $memory_text,
				);
			}
			$server_ip = SystemInfo::server_ip();
			if ( '' !== $server_ip ) {
				// Key deliberately matches the PRO widget-extras key so the
				// full HUD overwrites this row in place (no duplicates).
				$items['server_ip'] = array(
					'label' => __( 'Server IP:', 'version-info' ),
					'value' => $server_ip,
				);
			}
		}

		$eol      = SystemInfo::php_eol_info();
		$eol_date = null === $eol['date'] ? '' : date_i18n( get_option( 'date_format' ), strtotime( $eol['date'] ) );
		if ( $eol['is_eol'] ) {
			/* translators: %s: end-of-life date of the running PHP version */
			$eol_value = sprintf( __( 'End of life since %s', 'version-info' ), $eol_date );
		} elseif ( null !== $eol['days_left'] ) {
			/* translators: %1$s: number of days until end of life, %2$s: end-of-life date */
			$eol_value = sprintf( __( 'Supported — %1$s days left (%2$s)', 'version-info' ), number_format_i18n( $eol['days_left'] ), $eol_date );
		} else {
			$eol_value = __( 'No EOL data (current release)', 'version-info' );
		}
		$items['php_eol'] = array(
			'label' => __( 'PHP Lifecycle:', 'version-info' ),
			'value' => $eol_value,
		);

		/**
		 * Filtered dashboard-widget rows.
		 *
		 * @var array<string, array{label: string, value: string, html?: string}> $items
		 */
		$items = apply_filters( 'version_info_dashboard_widget_items', $items );

		$allowed = array(
			'span'     => array(
				'id'    => true,
				'class' => true,
				'style' => true,
			),
			'div'      => array(
				'id'    => true,
				'class' => true,
				'style' => true,
			),
			'strong'   => array( 'style' => true ),
			'code'     => array(),
			'em'       => array(),
			'br'       => array(),
			'svg'      => array(
				'id'      => true,
				'class'   => true,
				'style'   => true,
				'width'   => true,
				'height'  => true,
				'viewbox' => true,
				'xmlns'   => true,
			),
			'polyline' => array(
				'id'           => true,
				'class'        => true,
				'style'        => true,
				'points'       => true,
				'stroke'       => true,
				'stroke-width' => true,
				'fill'         => true,
			),
		);

		// WP's safecss_filter_attr strips `display` by default. Allow it for
		// the duration of this render so our percent-bar markup survives.
		add_filter( 'safe_style_css', array( $this, 'allow_display_css' ) );

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

		// Rendered outside the item loop: the wp_kses whitelist above has no
		// <button>, deliberately. Click handler lives in AdminBar::print_copy_js().
		echo '<p><button type="button" class="button" id="vi-copy-md">' . esc_html__( 'Copy as Markdown', 'version-info' ) . '</button></p>';

		remove_filter( 'safe_style_css', array( $this, 'allow_display_css' ) );
	}

	/**
	 * Allows the `display` CSS property through safecss_filter_attr().
	 *
	 * @param string[] $props Allowed CSS property names.
	 * @return string[]
	 */
	public function allow_display_css( $props ) {
		if ( ! in_array( 'display', $props, true ) ) {
			$props[] = 'display';
		}
		return $props;
	}
}
