<?php // phpcs:disable WordPress.Files.FileName -- PSR-4 class file naming; renaming would break the autoloader.


namespace GauchoPlugins\VersionInfo;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Tabbed plugin settings page under Settings > Version Info.
 */
class SettingsPage {

	// Note: `private const` requires PHP 7.1+; plain `const` keeps these readable on PHP 5.6.
	const PRO_TABS    = array( 'system_resources', 'environment', 'server_location', 'version_history', 'health_advisor', 'system_export', 'email_alerts' );
	const AGENCY_TABS = array( 'white_label', 'access_control', 'error_log' );

	/**
	 * Hooks the settings page and option registration.
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Adds the options page to the Settings menu.
	 */
	public function add_page() {
		add_options_page(
			__( 'Version Info Settings', 'version-info' ),
			__( 'Version Info', 'version-info' ),
			'manage_options',
			'version-info',
			array( $this, 'render' )
		);
	}

	/**
	 * Registers every plugin option in its per-tab settings group.
	 */
	public function register_settings() {
		// General tab — own group so saving doesn't affect other tabs.
		register_setting(
			'version_info_general_group',
			'version_info_show_footer',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => array( $this, 'sanitize_bool' ),
				'default'           => true,
			) 
		);
		register_setting(
			'version_info_general_group',
			'version_info_show_admin_bar',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => array( $this, 'sanitize_bool' ),
				'default'           => false,
			) 
		);
		register_setting(
			'version_info_general_group',
			'version_info_show_dashboard_widget',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => array( $this, 'sanitize_bool' ),
				'default'           => false,
			) 
		);
		register_setting(
			'version_info_general_group',
			'version_info_widget_show_resources',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => array( $this, 'sanitize_bool' ),
				'default'           => false,
			) 
		);
		// In version_info_general_group, so its <tr> MUST live in the General
		// form below — an option registered to a group whose form doesn't
		// render it gets reset to false on every save (the 2.0.3 bug class).
		register_setting(
			'version_info_general_group',
			'version_info_show_memory',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => array( $this, 'sanitize_bool' ),
				'default'           => true,
			) 
		);
		// Server Location tab — own group so saving doesn't clobber the General-tab
		// checkboxes (which would otherwise get reset to false on every Server Location
		// save because unchecked checkboxes aren't in $_POST). Reported by Steve
		// Guccione 2026-05-27 — saving Server Location was disabling the dashboard widget.
		register_setting(
			'version_info_location_group',
			'version_info_location_enabled',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => array( $this, 'sanitize_bool' ),
				'default'           => true,
			) 
		);
		register_setting(
			'version_info_location_group',
			'version_info_location_provider',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_location_provider' ),
				'default'           => 'vi_anonymous',
			) 
		);
		register_setting(
			'version_info_location_group',
			'version_info_location_maxmind_key',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			) 
		);

		// Environment tab — isolated group.
		register_setting(
			'version_info_environment_group',
			'version_info_show_env_badge',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => array( $this, 'sanitize_bool' ),
				'default'           => false,
			) 
		);
		register_setting(
			'version_info_environment_group',
			'version_info_env_admin_bar_highlight',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => array( $this, 'sanitize_bool' ),
				'default'           => false,
			) 
		);

		// White Label tab — isolated group.
		register_setting(
			'version_info_white_label_group',
			'version_info_wl_plugin_name',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			) 
		);
		register_setting(
			'version_info_white_label_group',
			'version_info_wl_author_name',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			) 
		);
		register_setting(
			'version_info_white_label_group',
			'version_info_wl_hide_branding',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => array( $this, 'sanitize_bool' ),
				'default'           => false,
			) 
		);
		register_setting(
			'version_info_white_label_group',
			'version_info_wl_lock_owner_id',
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_lock_owner' ),
				'default'           => 0,
			) 
		);
		register_setting(
			'version_info_white_label_group',
			'version_info_wl_hide_doc_links',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => array( $this, 'sanitize_bool' ),
				'default'           => false,
			) 
		);

		// Access Control tab — isolated group.
		register_setting(
			'version_info_access_control_group',
			'version_info_allowed_roles',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_roles' ),
				'default'           => array( 'administrator' ),
			) 
		);

		// Email Alerts tab — isolated group.
		register_setting(
			'version_info_email_alerts_group',
			'version_info_alert_enabled',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => array( $this, 'sanitize_bool' ),
				'default'           => false,
			) 
		);
		register_setting(
			'version_info_email_alerts_group',
			'version_info_alert_recipients',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			) 
		);
		register_setting(
			'version_info_email_alerts_group',
			'version_info_alert_types',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_alert_types' ),
				'default'           => array( 'wordpress', 'php', 'mysql' ),
			) 
		);
	}

	/**
	 * Normalizes a checkbox value to a strict boolean.
	 *
	 * @param mixed $input Raw option value.
	 */
	public function sanitize_bool( $input ) {
		return filter_var( $input, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Convert the "lock to me" checkbox state into a user-ID owner.
	 *
	 * Form posts "1" when checked or "0" via the paired hidden field when
	 * unchecked. We map "1" to the current user's ID so the option stores
	 * "who owns the lock". Only the existing owner is allowed to flip the
	 * value — anyone else attempting to edit gets the old value back.
	 *
	 * @param mixed $input Raw option value.
	 * @return int
	 */
	public function sanitize_lock_owner( $input ) {
		$current_owner = (int) get_option( 'version_info_wl_lock_owner_id', 0 );
		$current_user  = (int) get_current_user_id();

		if ( $current_owner > 0 && $current_owner !== $current_user ) {
			return $current_owner;
		}

		$checked = ( '1' === (string) $input || 1 === $input || true === $input );

		if ( $checked ) {
			return $current_user > 0 ? $current_user : 0;
		}

		return 0;
	}

	/**
	 * Restrict the location-provider option to the known set.
	 *
	 * @param mixed $input Raw option value.
	 * @return string
	 */
	public function sanitize_location_provider( $input ) {
		$valid = array(
			'vi_anonymous',
			'cloudflare_trace',
			'ip_api',
			'maxmind',
		);
		$input = is_string( $input ) ? $input : '';
		return in_array( $input, $valid, true ) ? $input : 'vi_anonymous';
	}

	/**
	 * Sanitizes the allowed-roles option into a list of role slugs.
	 *
	 * @param mixed $input Raw option value.
	 * @return string[]
	 */
	public function sanitize_roles( $input ) {
		if ( ! is_array( $input ) ) {
			return array( 'administrator' );
		}
		return array_map( 'sanitize_key', $input );
	}

	/**
	 * Restricts the alert-types option to the known component set.
	 *
	 * @param mixed $input Raw option value.
	 * @return string[]
	 */
	public function sanitize_alert_types( $input ) {
		if ( ! is_array( $input ) ) {
			return array( 'wordpress', 'php', 'mysql' );
		}
		$valid = array( 'wordpress', 'php', 'mysql' );
		return array_values( array_intersect( array_map( 'sanitize_key', $input ), $valid ) );
	}

	/**
	 * Renders the settings page shell and the active tab.
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$tabs = array(
			'general'          => __( 'General', 'version-info' ),
			'system_resources' => __( 'System Resources', 'version-info' ),
			'environment'      => __( 'Environment', 'version-info' ),
			'server_location'  => __( 'Server Location', 'version-info' ),
			'version_history'  => __( 'Version History', 'version-info' ),
			'health_advisor'   => __( 'Health Advisor', 'version-info' ),
			'system_export'    => __( 'System Export', 'version-info' ),
			'white_label'      => __( 'White Label', 'version-info' ),
			'access_control'   => __( 'Access Control', 'version-info' ),
			'email_alerts'     => __( 'Email Alerts', 'version-info' ),
			'error_log'        => __( 'Error Log', 'version-info' ),
		);

		/**
		 * Filtered settings-tab map.
		 *
		 * @var array<string, string> $tabs
		 */
		$tabs = apply_filters( 'version_info_settings_tabs', $tabs );

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
		if ( ! array_key_exists( $current_tab, $tabs ) ) {
			$current_tab = 'general';
		}

		$can_use_premium = vi_fs()->can_use_premium_code();
		// is_plan_or_trial matches the registration gate in Plugin::maybe_init_pro()
		// — Agency-trial users previously saw locked tabs while their hooks ran.
		$is_agency = $can_use_premium && vi_fs()->is_plan_or_trial( 'agency' );

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Version Info Settings', 'version-info' ) . '</h1>';

		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $tabs as $tab_id => $tab_label ) {
			$url   = add_query_arg(
				array(
					'page' => 'version-info',
					'tab'  => $tab_id,
				),
				admin_url( 'options-general.php' )
			);
			$class = ( $current_tab === $tab_id ) ? ' nav-tab-active' : '';

			echo '<a href="' . esc_url( $url ) . '" class="nav-tab' . esc_attr( $class ) . '">';
			echo esc_html( $tab_label );

			if ( in_array( $tab_id, self::AGENCY_TABS, true ) && ! $is_agency ) {
				echo ' <span class="dashicons dashicons-lock" style="font-size:14px;line-height:inherit;vertical-align:text-bottom;"></span>';
			} elseif ( in_array( $tab_id, self::PRO_TABS, true ) && ! $can_use_premium ) {
				echo ' <span class="dashicons dashicons-lock" style="font-size:14px;line-height:inherit;vertical-align:text-bottom;"></span>';
			}

			echo '</a>';
		}
		echo '</h2>';

		$this->render_tab( $current_tab, $can_use_premium, $is_agency );

		echo '</div>';
	}

	/**
	 * Renders the requested tab, or a locked placeholder for unavailable tiers.
	 *
	 * @param string $tab_id          Active tab identifier.
	 * @param bool   $can_use_premium Whether premium code may run.
	 * @param bool   $is_agency       Whether the Agency plan (or trial) is active.
	 */
	private function render_tab( $tab_id, $can_use_premium, $is_agency ) {
		if ( in_array( $tab_id, self::AGENCY_TABS, true ) && ! $is_agency ) {
			$this->render_agency_tab_placeholder( $tab_id );
			return;
		}

		if ( in_array( $tab_id, self::PRO_TABS, true ) && ! $can_use_premium ) {
			$this->render_pro_tab_placeholder( $tab_id );
			return;
		}

		switch ( $tab_id ) {
			case 'general':
				$this->render_general_tab();
				break;
			default:
				do_action( "version_info_render_tab_{$tab_id}" );
				break;
		}
	}

	/**
	 * Renders the General settings tab.
	 */
	private function render_general_tab() {
		$can_use_premium = vi_fs()->can_use_premium_code();
		$widget_enabled  = (bool) get_option( 'version_info_show_dashboard_widget', false );
		$doc_link        = Plugin::doc_link( 'getting-started-installation-and-setup', __( 'Setup & display-locations docs', 'version-info' ) );
		if ( '' !== $doc_link ) {
			echo '<p style="margin-top:12px;">' . $doc_link . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- helper returns safe markup
		}
		?>
		<form method="post" action="options.php">
			<?php settings_fields( 'version_info_general_group' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Show Version Info in Admin Bar', 'version-info' ); ?></th>
					<td>
						<input type="checkbox" name="version_info_show_admin_bar" value="1"
							<?php checked( 1, get_option( 'version_info_show_admin_bar', false ) ); ?> />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="vi_show_dashboard_widget"><?php esc_html_e( 'Show Version Info as Dashboard Widget', 'version-info' ); ?></label>
					</th>
					<td>
						<input type="checkbox" id="vi_show_dashboard_widget" name="version_info_show_dashboard_widget" value="1"
							<?php checked( 1, $widget_enabled ); ?> />
					</td>
				</tr>
				<tr id="vi_widget_resources_row" style="<?php echo $widget_enabled ? '' : 'display:none;'; ?>">
					<th scope="row">
						<?php esc_html_e( 'Show Live System Resources in Dashboard Widget', 'version-info' ); ?>
						<?php if ( ! $can_use_premium ) : ?>
							<span class="dashicons dashicons-lock" style="font-size:14px;line-height:inherit;vertical-align:text-bottom;"></span>
						<?php endif; ?>
					</th>
					<td<?php echo $can_use_premium ? '' : ' style="opacity:0.45;pointer-events:none;"'; ?>>
						<input type="checkbox" name="version_info_widget_show_resources" value="1"
							<?php checked( 1, get_option( 'version_info_widget_show_resources', false ) ); ?>
							<?php disabled( ! $can_use_premium ); ?> />
						<p class="description">
							<?php esc_html_e( 'Adds live CPU, memory, database, and system details to the Version Info dashboard widget.', 'version-info' ); ?>
							<?php if ( ! $can_use_premium ) : ?>
								<br><em><?php esc_html_e( 'PRO feature.', 'version-info' ); ?></em>
								<a href="<?php echo esc_url( vi_fs()->get_upgrade_url() ); ?>"><?php esc_html_e( 'Upgrade to PRO', 'version-info' ); ?></a>
							<?php endif; ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Show Version Info in Footer', 'version-info' ); ?></th>
					<td>
						<input type="checkbox" name="version_info_show_footer" value="1"
							<?php checked( 1, get_option( 'version_info_show_footer', true ) ); ?> />
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Show Memory & Server IP', 'version-info' ); ?></th>
					<td>
						<input type="checkbox" name="version_info_show_memory" value="1"
							<?php checked( 1, get_option( 'version_info_show_memory', true ) ); ?> />
						<p class="description"><?php esc_html_e( 'Adds the PHP memory limit, live usage %, and server IP to the footer, admin bar, and dashboard widget.', 'version-info' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<script>
		(function () {
			var parent = document.getElementById( 'vi_show_dashboard_widget' );
			var row    = document.getElementById( 'vi_widget_resources_row' );
			if ( ! parent || ! row ) { return; }
			var sync = function () { row.style.display = parent.checked ? '' : 'none'; };
			parent.addEventListener( 'change', sync );
			sync();
		}());
		</script>
		<?php
	}

	/**
	 * Renders the locked preview shown to free users on PRO tabs.
	 *
	 * @param string $tab_id Active tab identifier.
	 */
	private function render_pro_tab_placeholder( $tab_id ) {
		$previews = array(
			'system_resources' => array(
				'title'       => __( 'System Resources', 'version-info' ),
				'description' => __( 'Monitor real-time CPU load and memory usage directly from your WordPress dashboard.', 'version-info' ),
				'features'    => array(
					__( 'CPU load percentage with visual bar', 'version-info' ),
					__( 'RAM usage percentage with visual bar', 'version-info' ),
					__( 'Database size tracking (data + index)', 'version-info' ),
					__( 'Live updates via WordPress Heartbeat API', 'version-info' ),
				),
			),
			'environment'      => array(
				'title'       => __( 'Environment Indicators', 'version-info' ),
				'description' => __( 'Instantly identify your environment type with a color-coded badge in the Admin Bar.', 'version-info' ),
				'features'    => array(
					__( 'Auto-detects Production, Staging, Development, and Local', 'version-info' ),
					__( 'Supports WP_ENVIRONMENT_TYPE, Bedrock, Kinsta, WP Engine, and more', 'version-info' ),
					__( 'Color-coded Admin Bar badge', 'version-info' ),
				),
			),
			'server_location'  => array(
				'title'       => __( 'Server Location', 'version-info' ),
				'description' => __( 'Auto-detect where your server lives, with a privacy-first chooser. Anonymous default that logs nothing — or pick your own provider.', 'version-info' ),
				'features'    => array(
					__( 'Four selectable providers (Version Info anonymous, Cloudflare trace, ip-api, MaxMind)', 'version-info' ),
					__( 'Enable/disable auto-detect entirely with one checkbox', 'version-info' ),
					__( '30-day transient cache + one-click Detect Now', 'version-info' ),
					__( 'Reverse-DNS fallback when providers are unreachable', 'version-info' ),
				),
			),
			'version_history'  => array(
				'title'       => __( 'Version History', 'version-info' ),
				'description' => __( 'Track changes in your WordPress, PHP, and MySQL versions over time.', 'version-info' ),
				'features'    => array(
					__( 'Automatic detection of version changes', 'version-info' ),
					__( 'Hooks into WordPress core, plugin, and theme update process', 'version-info' ),
					__( 'Sortable history table with timestamps', 'version-info' ),
				),
			),
			'health_advisor'   => array(
				'title'       => __( 'Health Advisor', 'version-info' ),
				'description' => __( 'Predictive alerts for end-of-life software and critical server issues.', 'version-info' ),
				'features'    => array(
					__( 'PHP end-of-life date tracking', 'version-info' ),
					__( 'MySQL end-of-life date tracking', 'version-info' ),
					__( 'SSL certificate expiry monitoring', 'version-info' ),
					__( 'Integrates with WordPress Site Health screen', 'version-info' ),
				),
			),
			'email_alerts'     => array(
				'title'       => __( 'Email Alerts', 'version-info' ),
				'description' => __( 'Receive priority email notifications whenever a version change is detected on your site.', 'version-info' ),
				'features'    => array(
					__( 'Alerts for WordPress, PHP, and MySQL version changes', 'version-info' ),
					__( 'Configurable recipient list', 'version-info' ),
					__( 'Per-component alert toggles', 'version-info' ),
				),
			),
			'system_export'    => array(
				'title'       => __( 'System Info Export', 'version-info' ),
				'description' => __( 'Download a complete snapshot of your technical stack as a JSON file.', 'version-info' ),
				'features'    => array(
					__( 'One-click JSON download of all system info', 'version-info' ),
					__( 'Includes PHP extensions, active plugins, and theme data', 'version-info' ),
					__( 'Ideal for support ticket attachments', 'version-info' ),
				),
			),
		);

		$preview = isset( $previews[ $tab_id ] ) ? $previews[ $tab_id ] : null;
		if ( ! $preview ) {
			return;
		}

		$this->render_locked_placeholder( $preview, 'pro' );
	}

	/**
	 * Renders the locked preview shown to non-Agency users on Agency tabs.
	 *
	 * @param string $tab_id Active tab identifier.
	 */
	private function render_agency_tab_placeholder( $tab_id ) {
		$previews = array(
			'white_label'    => array(
				'title'       => __( 'White Label', 'version-info' ),
				'description' => __( 'Fully rebrand the plugin with your own name and remove all Gaucho Plugins branding.', 'version-info' ),
				'features'    => array(
					__( 'Custom plugin name throughout the dashboard', 'version-info' ),
					__( 'Custom author name attribution', 'version-info' ),
					__( 'Hide Freemius account and support menu items', 'version-info' ),
					__( 'Hide in-plugin links to docs.versioninfoplugin.com', 'version-info' ),
					__( 'Lock the White Label tab to a single administrator', 'version-info' ),
				),
			),
			'access_control' => array(
				'title'       => __( 'Access Control', 'version-info' ),
				'description' => __( 'Control which WordPress user roles can see version information across your site.', 'version-info' ),
				'features'    => array(
					__( 'Per-role visibility toggles', 'version-info' ),
					__( 'Applies to admin bar, footer, and dashboard widget', 'version-info' ),
					__( 'Default restricted to administrators', 'version-info' ),
				),
			),
			'error_log'      => array(
				'title'       => __( 'PHP Error Log', 'version-info' ),
				'description' => __( 'View and download your PHP error log directly from the WordPress dashboard.', 'version-info' ),
				'features'    => array(
					__( 'Tail last 100 lines without loading full log into memory', 'version-info' ),
					__( 'Sensitive file paths automatically masked', 'version-info' ),
					__( 'Download full log as ZIP for offline analysis', 'version-info' ),
				),
			),
		);

		$preview = isset( $previews[ $tab_id ] ) ? $previews[ $tab_id ] : null;
		if ( ! $preview ) {
			return;
		}

		$this->render_locked_placeholder( $preview, 'agency' );
	}

	/**
	 * Renders the greyed-out feature preview plus the upgrade notice.
	 *
	 * @param array{title: string, description: string, features: string[]} $preview Preview copy for the locked tab.
	 * @param string                                                        $tier    Either 'pro' or 'agency'.
	 */
	private function render_locked_placeholder( array $preview, $tier ) {
		if ( 'agency' === $tier ) {
			$label   = __( 'This is an Agency feature.', 'version-info' );
			$message = __( 'Upgrade to the Agency plan to unlock this feature.', 'version-info' );
			$button  = __( 'Upgrade to Agency', 'version-info' );
		} else {
			$label   = __( 'This is a PRO feature.', 'version-info' );
			$message = __( 'Upgrade to Version Info PRO to unlock this feature.', 'version-info' );
			$button  = __( 'Upgrade to PRO', 'version-info' );
		}

		echo '<div style="position:relative;margin-top:20px;">';

		echo '<div style="opacity:0.45;pointer-events:none;">';
		echo '<h2>' . esc_html( $preview['title'] ) . '</h2>';
		echo '<p>' . esc_html( $preview['description'] ) . '</p>';
		echo '<table class="form-table" role="presentation">';
		foreach ( $preview['features'] as $feature ) {
			echo '<tr><th scope="row">' . esc_html( $feature ) . '</th>';
			echo '<td><span class="dashicons dashicons-yes-alt" style="color:#999;"></span></td></tr>';
		}
		echo '</table>';
		echo '</div>';

		echo '<div class="notice notice-info" style="margin-top:15px;padding:15px;">';
		echo '<p><strong>' . esc_html( $label ) . '</strong> ';
		echo esc_html( $message ) . '</p>';
		echo '<p><a href="' . esc_url( vi_fs()->get_upgrade_url() ) . '" class="button button-primary">';
		echo esc_html( $button );
		echo '</a></p>';
		echo '</div>';

		echo '</div>';
	}
}
