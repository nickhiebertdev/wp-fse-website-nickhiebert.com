<?php // phpcs:disable WordPress.Files.FileName -- PSR-4 class file naming; renaming would break the autoloader.


namespace GauchoPlugins\VersionInfo;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Free-tier system data helpers.
 *
 * Single source of truth for the PHP end-of-life table — the PRO Health
 * Advisor reads it through php_eol_dates(), so free and PRO EOL data can
 * never drift apart.
 *
 * Everything here must stay portable and dependency-free: no /proc reads
 * (system RAM stays PRO territory in Pro\MemoryProvider), no Pro\*
 * references (includes/pro/ is stripped from the wp.org free build), and
 * no PHP 7+ syntax — the plugin still installs on PHP 5.6 / WP 4.7.
 */
class SystemInfo {

	/**
	 * PHP minor versions and their end-of-life (security support end) dates.
	 *
	 * `const` (rather than `private const`) — class constant visibility
	 * modifiers require PHP 7.1+. Dates verified against endoflife.date on
	 * 2026-08-11. PHP 8.6 is unreleased and deliberately absent.
	 *
	 * @var array<string, string>
	 */
	const PHP_EOL_DATES = array(
		'5.6' => '2018-12-31',
		'7.0' => '2019-01-10',
		'7.1' => '2019-12-01',
		'7.2' => '2020-11-30',
		'7.3' => '2021-12-06',
		'7.4' => '2022-11-28',
		'8.0' => '2023-11-26',
		'8.1' => '2025-12-31',
		'8.2' => '2026-12-31',
		'8.3' => '2027-12-31',
		'8.4' => '2028-12-31',
		'8.5' => '2029-12-31',
	);

	/**
	 * Returns the PHP minor-version EOL date table.
	 *
	 * @return array<string, string>
	 */
	public static function php_eol_dates() {
		return self::PHP_EOL_DATES;
	}

	/**
	 * EOL status of the running PHP version.
	 *
	 * @return array{date: string|null, days_left: int|null, is_eol: bool}
	 */
	public static function php_eol_info() {
		$parts = explode( '.', phpversion() );
		$minor = ( isset( $parts[0] ) ? $parts[0] : '' ) . '.' . ( isset( $parts[1] ) ? $parts[1] : '0' );
		if ( ! array_key_exists( $minor, self::PHP_EOL_DATES ) ) {
			return array(
				'date'      => null,
				'days_left' => null,
				'is_eol'    => false,
			);
		}
		$eol_ts = strtotime( self::PHP_EOL_DATES[ $minor ] );
		$is_eol = time() > $eol_ts;
		return array(
			'date'      => self::PHP_EOL_DATES[ $minor ],
			'days_left' => $is_eol ? null : (int) round( ( $eol_ts - time() ) / DAY_IN_SECONDS ),
			'is_eol'    => $is_eol,
		);
	}

	/**
	 * PHP memory limit and current usage. PHP-process memory only — system
	 * RAM (/proc/meminfo, WMI) is the PRO MemoryProvider's job.
	 *
	 * @return array{usage: int|null, limit: int, percent: float|null}
	 */
	public static function memory_usage() {
		$usage = function_exists( 'memory_get_usage' ) ? (int) memory_get_usage( true ) : null;
		$raw   = function_exists( 'ini_get' ) ? ini_get( 'memory_limit' ) : false;
		// ini_get() returns string|false — false is the only non-string case to normalize.
		$limit = self::parse_memory_limit( false === $raw ? '' : (string) $raw );
		return array(
			'usage'   => $usage,
			'limit'   => $limit,
			'percent' => self::memory_percent( $usage, $limit ),
		);
	}

	/**
	 * Pure helper: shorthand ini value to bytes. 0 means unknown/unlimited
	 * ("-1" = unlimited; treated as "no limit", Pro\MemoryProvider precedent).
	 *
	 * @param string $raw Raw ini value, e.g. "256M", "1G", "-1".
	 * @return int
	 */
	public static function parse_memory_limit( $raw ) {
		$raw = trim( (string) $raw );
		if ( '' === $raw || '-1' === $raw ) {
			return 0;
		}
		$bytes = wp_convert_hr_to_bytes( $raw );
		return $bytes > 0 ? (int) $bytes : 0;
	}

	/**
	 * Pure helper: usage percentage of the limit; null when either is unknown.
	 *
	 * @param int|null $usage Bytes currently used, or null when unavailable.
	 * @param int      $limit Limit in bytes; 0 = unknown/unlimited.
	 * @return float|null
	 */
	public static function memory_percent( $usage, $limit ) {
		if ( null === $usage || $limit <= 0 ) {
			return null;
		}
		return round( ( $usage / $limit ) * 100, 1 );
	}

	/**
	 * Shared human-readable memory string for the three display surfaces.
	 *
	 * @return string "24.6 MB of 256 MB (9.6%)", "24.6 MB (no limit)", or ''
	 *                when usage is unavailable (callers omit the row/segment).
	 */
	public static function memory_text() {
		$memory = self::memory_usage();
		if ( null === $memory['usage'] ) {
			return '';
		}
		if ( null === $memory['percent'] ) {
			/* translators: %s: current PHP memory usage */
			return sprintf( __( '%s (no limit)', 'version-info' ), size_format( $memory['usage'] ) );
		}
		return sprintf(
			/* translators: %1$s: current PHP memory usage, %2$s: PHP memory limit, %3$s: percentage of the limit used */
			__( '%1$s of %2$s (%3$s%%)', 'version-info' ),
			size_format( $memory['usage'] ),
			size_format( $memory['limit'] ),
			number_format_i18n( $memory['percent'], 1 )
		);
	}

	/**
	 * Shared EOL alert string for the footer and admin bar. Empty unless the
	 * running PHP is past EOL or within 365 days of it — the countdown is an
	 * alert, not permanent string growth for healthy stacks.
	 *
	 * @return string
	 */
	public static function php_eol_alert_text() {
		$eol = self::php_eol_info();
		if ( $eol['is_eol'] ) {
			return __( 'PHP past EOL', 'version-info' );
		}
		if ( null !== $eol['days_left'] && $eol['days_left'] <= 365 ) {
			/* translators: %s: number of days until the running PHP version reaches end of life */
			return sprintf( __( 'PHP EOL in %s days', 'version-info' ), number_format_i18n( $eol['days_left'] ) );
		}
		return '';
	}

	/**
	 * Server IP, best effort. Mirrors Pro\LocationProvider::server_ip() —
	 * duplicated by design: free code must never reference Pro\* classes
	 * because includes/pro/ is stripped from the wp.org build.
	 *
	 * @return string Empty string when unavailable (callers omit the row).
	 */
	public static function server_ip() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- server-set values (not client input), sanitized by the character whitelist below.
		$ip = isset( $_SERVER['SERVER_ADDR'] ) ? $_SERVER['SERVER_ADDR'] : ( isset( $_SERVER['LOCAL_ADDR'] ) ? $_SERVER['LOCAL_ADDR'] : '' );
		return (string) preg_replace( '/[^0-9a-fA-F:\.]/', '', (string) $ip );
	}

	/**
	 * Compact Markdown table of the core stack, built server-side so the
	 * clipboard JS stays dumb. Deliberately excludes the plugin/theme list —
	 * that is the PRO System Export feature.
	 *
	 * @return string
	 */
	public static function markdown_table() {
		global $wpdb;
		$memory = self::memory_usage();
		$server = sanitize_text_field( isset( $_SERVER['SERVER_SOFTWARE'] ) ? $_SERVER['SERVER_SOFTWARE'] : __( 'Unknown', 'version-info' ) );

		$rows = array(
			array( __( 'WordPress', 'version-info' ), apply_filters( 'version_info_wp_version', get_bloginfo( 'version' ) ) ),
			array( __( 'PHP', 'version-info' ), apply_filters( 'version_info_php_version', phpversion() ) ),
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- reading the MySQL server version; no WP API exists and the value is not site data.
			array( __( 'MySQL', 'version-info' ), apply_filters( 'version_info_mysql_version', (string) $wpdb->get_var( 'SELECT VERSION()' ) ) ),
			array( __( 'Web Server', 'version-info' ), apply_filters( 'version_info_server_software', $server ) ),
			array( __( 'PHP Memory Limit', 'version-info' ), $memory['limit'] > 0 ? size_format( $memory['limit'] ) : __( 'Unlimited', 'version-info' ) ),
			array( __( 'WP Memory Limit', 'version-info' ), defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : __( 'Unknown', 'version-info' ) ),
			array( __( 'Locale', 'version-info' ), get_locale() ),
			array( __( 'Multisite', 'version-info' ), is_multisite() ? __( 'Yes', 'version-info' ) : __( 'No', 'version-info' ) ),
		);

		$lines = array(
			'| ' . __( 'Item', 'version-info' ) . ' | ' . __( 'Value', 'version-info' ) . ' |',
			'| --- | --- |',
		);
		foreach ( $rows as $row ) {
			$lines[] = '| ' . str_replace( '|', '\|', (string) $row[0] ) . ' | ' . str_replace( '|', '\|', (string) $row[1] ) . ' |';
		}
		return implode( "\n", $lines );
	}
}
