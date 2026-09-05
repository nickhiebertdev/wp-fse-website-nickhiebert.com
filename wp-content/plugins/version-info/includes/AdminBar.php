<?php // phpcs:disable WordPress.Files.FileName -- PSR-4 class file naming; renaming would break the autoloader.


namespace GauchoPlugins\VersionInfo;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Admin-bar version nodes plus the shared "Copy as Markdown" clipboard handler.
 */
class AdminBar {

	/**
	 * Hooks the admin-bar nodes and the clipboard-handler output.
	 */
	public function register() {
		add_action( 'admin_bar_menu', array( $this, 'add_nodes' ), 100 );
		// The admin bar also renders on the front-end for logged-in users, so
		// the clipboard handler prints on both footers (it self-guards).
		add_action( 'admin_footer', array( $this, 'print_copy_js' ) );
		add_action( 'wp_footer', array( $this, 'print_copy_js' ) );
	}

	/**
	 * Adds the version-info nodes to the admin bar.
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 */
	public function add_nodes( \WP_Admin_Bar $wp_admin_bar ) {
		if ( ! Plugin::current_user_can_view() ) {
			return;
		}

		$nodes = array();

		if ( get_option( 'version_info_show_admin_bar', false ) ) {
			$nodes['version_info_admin_bar'] = array(
				'id'     => 'version_info_admin_bar',
				'title'  => $this->get_version_string(),
				'parent' => 'top-secondary',
			);
			$nodes['version_info_copy_md']   = array(
				'id'     => 'version_info_copy_md',
				'parent' => 'version_info_admin_bar',
				'title'  => __( 'Copy as Markdown', 'version-info' ),
				'href'   => '#',
			);
		}

		/**
		 * Filtered admin-bar node definitions.
		 *
		 * @var array<string, array{id: string, title: string, parent?: string, meta?: array}> $nodes
		 */
		$nodes = apply_filters( 'version_info_admin_bar_nodes', $nodes );

		foreach ( $nodes as $node ) {
			$wp_admin_bar->add_node( $node );
		}
	}

	/**
	 * Builds the admin-bar version summary string.
	 */
	private function get_version_string() {
		global $wpdb;

		$wp_version      = apply_filters( 'version_info_wp_version', get_bloginfo( 'version' ) );
		$php_version     = apply_filters( 'version_info_php_version', phpversion() );
		$server_software = apply_filters(
			'version_info_server_software',
			sanitize_text_field( isset( $_SERVER['SERVER_SOFTWARE'] ) ? $_SERVER['SERVER_SOFTWARE'] : __( 'Unknown', 'version-info' ) )
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- reading the MySQL server version; no WP API exists and the value is not site data.
		$mysql_version = apply_filters( 'version_info_mysql_version', (string) $wpdb->get_var( 'SELECT VERSION()' ) );

		$details = sprintf(
			/* translators: %1$s: WP version, %2$s: PHP version, %3$s: server software, %4$s: MySQL version */
			__( 'WordPress %1$s | PHP %2$s | Web Server %3$s | MySQL %4$s', 'version-info' ),
			esc_html( $wp_version ),
			esc_html( $php_version ),
			esc_html( $server_software ),
			esc_html( $mysql_version )
		);

		if ( get_option( 'version_info_show_memory', true ) ) {
			$memory_text = SystemInfo::memory_text();
			if ( '' !== $memory_text ) {
				/* translators: %s: PHP memory usage summary, e.g. "24.6 MB of 256 MB (9.6%)" */
				$details .= sprintf( __( ' | Mem %s', 'version-info' ), esc_html( $memory_text ) );
			}
		}

		$eol_alert = SystemInfo::php_eol_alert_text();
		if ( '' !== $eol_alert ) {
			$details .= ' | ' . esc_html( $eol_alert );
		}

		return $details;
	}

	/**
	 * Clipboard handler for the "Copy as Markdown" control (admin-bar node
	 * and dashboard-widget button). Inline vanilla JS — the plugin enqueues
	 * no assets by design; see the General-tab script for the precedent.
	 */
	public function print_copy_js() {
		if ( ! Plugin::current_user_can_view()
			|| ( ! get_option( 'version_info_show_admin_bar', false ) && ! get_option( 'version_info_show_dashboard_widget', false ) ) ) {
			return;
		}
		if ( ! is_admin() && ! is_admin_bar_showing() ) {
			return;
		}

		// JSON with hex flags makes `</script>` breakout impossible and keeps
		// multi-line payloads intact (esc_js() would mangle the newlines).
		$json = wp_json_encode( SystemInfo::markdown_table(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
		if ( false === $json ) {
			// Bad UTF-8 somewhere in the payload — the control becomes a no-op.
			return;
		}
		?>
		<script>
		(function () {
			var payload = <?php echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-encoded with hex flags above. ?>;
			var done = function ( el, ok ) {
				var original = el.textContent;
				el.textContent = ok
					? '<?php echo esc_js( __( 'Copied!', 'version-info' ) ); ?>'
					: '<?php echo esc_js( __( 'Copy failed', 'version-info' ) ); ?>';
				setTimeout( function () { el.textContent = original; }, 2000 );
			};
			// Textarea + execCommand fallback: WP 4.7-era browsers and
			// plain-HTTP admins have no navigator.clipboard.
			var fallbackCopy = function () {
				var area = document.createElement( 'textarea' ), ok = false;
				area.value = payload;
				area.style.cssText = 'position:fixed;left:-9999px;';
				document.body.appendChild( area );
				area.select();
				try { ok = document.execCommand( 'copy' ); } catch ( e ) { ok = false; }
				document.body.removeChild( area );
				return ok;
			};
			var bind = function ( el, labelEl ) {
				if ( ! el ) { return; }
				el.addEventListener( 'click', function ( event ) {
					event.preventDefault();
					var label = labelEl || el;
					if ( window.isSecureContext && navigator.clipboard && navigator.clipboard.writeText ) {
						navigator.clipboard.writeText( payload ).then(
							function () { done( label, true ); },
							function () { done( label, fallbackCopy() ); }
						);
					} else {
						done( label, fallbackCopy() );
					}
				} );
			};
			var node = document.getElementById( 'wp-admin-bar-version_info_copy_md' );
			bind( node, node ? node.querySelector( '.ab-item' ) : null );
			bind( document.getElementById( 'vi-copy-md' ) );
		}());
		</script>
		<?php
	}
}
